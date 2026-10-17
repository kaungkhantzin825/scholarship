<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Best-effort plain-text extraction for documents uploaded into the AI chat.
 *
 * Supported without extra Composer packages:
 *   - PDF   : inflates FlateDecode streams and pulls text-showing operators
 *   - DOCX  : reads word/document.xml out of the OOXML zip
 *   - TXT / MD / CSV / JSON : read directly
 *
 * The result is a plain-text approximation. Layout, tables and scanned
 * images are lost — good enough for an AI to summarise a CV, but the
 * original file is always kept so nothing is destroyed.
 */
class DocumentTextExtractor
{
    /** Hard cap so one huge PDF can't blow the prompt budget. */
    private const MAX_CHARS = 30000;

    /**
     * @return array{text: string, ok: bool, note: string|null}
     */
    public function extract(string $absolutePath, ?string $extension = null, ?string $mime = null): array
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return ['text' => '', 'ok' => false, 'note' => 'File could not be read.'];
        }

        $ext = strtolower($extension ?: pathinfo($absolutePath, PATHINFO_EXTENSION));

        try {
            $text = match ($ext) {
                'pdf'          => $this->fromPdf($absolutePath),
                'docx'         => $this->fromDocx($absolutePath),
                'txt', 'md', 'csv', 'json', 'rtf' => $this->fromPlain($absolutePath),
                default        => $this->fromPlain($absolutePath),
            };
        } catch (\Throwable $e) {
            Log::info('AI document extraction failed', [
                'ext'   => $ext,
                'error' => $e->getMessage(),
            ]);

            return ['text' => '', 'ok' => false, 'note' => 'This file type could not be read as text.'];
        }

        $text = $this->clean($text);

        if ($text === '') {
            $note = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic'], true)
                ? 'Image received. I can see the file was attached but cannot read text from images yet — please describe the key details.'
                : 'No readable text was found in this file (it may be a scanned document).';

            return ['text' => '', 'ok' => false, 'note' => $note];
        }

        if (mb_strlen($text) > self::MAX_CHARS) {
            $text = mb_substr($text, 0, self::MAX_CHARS)
                ."\n\n[… document truncated at ".self::MAX_CHARS.' characters …]';
        }

        return ['text' => $text, 'ok' => true, 'note' => null];
    }

    // ─── Format handlers ─────────────────────────────────────────────────────

    private function fromPlain(string $path): string
    {
        $raw = @file_get_contents($path) ?: '';

        // Strip a UTF-8 BOM if present.
        return preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
    }

    /**
     * DOCX is a zip; the body text lives in word/document.xml.
     * We turn paragraph and line breaks into newlines before stripping tags.
     */
    private function fromDocx(string $path): string
    {
        if (! class_exists(\ZipArchive::class)) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === null) {
            return '';
        }

        $xml = str_replace(
            ['</w:p>', '<w:br/>', '<w:tab/>', '</w:tr>'],
            ["\n", "\n", "\t", "\n"],
            $xml
        );

        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $text;
    }

    /**
     * Minimal PDF text extraction.
     *
     * 1. Walk every `stream … endstream` block and inflate FlateDecode ones.
     * 2. Inside the decompressed content stream, pull strings from the
     *    text-showing operators `Tj`, `TJ`, `'` and `"`.
     * 3. Reassemble with newlines on `Td` / `TD` / `T*` movement.
     *
     * This handles the vast majority of text-based PDFs (Word/LaTeX exports,
     * generated resumes). Scanned PDFs have no text layer and return ''.
     */
    private function fromPdf(string $path): string
    {
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return '';
        }

        $chunks = [];
        $offset = 0;

        while (($start = strpos($raw, 'stream', $offset)) !== false) {
            $start += 6;
            // Skip the EOL that follows the `stream` keyword.
            if (substr($raw, $start, 2) === "\r\n") {
                $start += 2;
            } elseif (($raw[$start] ?? '') === "\n" || ($raw[$start] ?? '') === "\r") {
                $start += 1;
            }

            $end = strpos($raw, 'endstream', $start);
            if ($end === false) {
                break;
            }

            $payload = substr($raw, $start, $end - $start);
            $offset = $end + 9;

            // Try to inflate; fall back to the raw payload.
            $inflated = @gzuncompress($payload);
            if ($inflated === false) {
                $inflated = @gzinflate($payload);
            }
            if ($inflated === false) {
                $inflated = $payload;
            }

            if (str_contains($inflated, 'Tj') || str_contains($inflated, 'TJ')) {
                $chunks[] = $inflated;
            }
        }

        if ($chunks === []) {
            return '';
        }

        $out = [];
        foreach ($chunks as $stream) {
            $out[] = $this->textFromContentStream($stream);
        }

        return implode("\n", $out);
    }

    /** Pull literal strings out of a PDF content stream. */
    private function textFromContentStream(string $stream): string
    {
        $buffer = '';

        // Split on text-positioning operators so we can insert line breaks.
        $tokens = preg_split('/(BT|ET|T\*|Td|TD|TJ|Tj|\'|")/', $stream, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return '';
        }

        foreach ($tokens as $token) {
            $trimmed = trim($token);

            if (in_array($trimmed, ['T*', 'Td', 'TD', 'ET'], true)) {
                $buffer .= "\n";
                continue;
            }

            // Collect every literal string in this token slice.
            if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $token, $matches)) {
                foreach ($matches[0] as $literal) {
                    $buffer .= $this->decodePdfString($literal);
                }
            }

            // Hex strings <00480065...> are used for CID fonts.
            if (preg_match_all('/<([0-9A-Fa-f\s]+)>/', $token, $hexMatches)) {
                foreach ($hexMatches[1] as $hex) {
                    $buffer .= $this->decodePdfHex($hex);
                }
            }
        }

        return $buffer;
    }

    private function decodePdfString(string $literal): string
    {
        $inner = substr($literal, 1, -1);

        $map = [
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\b' => '',
            '\\f' => '',
            '\\(' => '(',
            '\\)' => ')',
            '\\\\' => '\\',
        ];

        $inner = strtr($inner, $map);

        // Octal escapes such as \101 → A
        $inner = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]) & 0xFF);
        }, $inner) ?? $inner;

        return $inner;
    }

    private function decodePdfHex(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? '';
        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return '';
        }

        $bin = @hex2bin($hex);
        if ($bin === false) {
            return '';
        }

        // Many PDFs use 2-byte CID codes; keep only the low byte and drop NULs.
        if (strlen($bin) >= 2 && substr_count($bin, "\x00") > strlen($bin) / 3) {
            $out = '';
            for ($i = 0; $i < strlen($bin); $i += 2) {
                $out .= $bin[$i + 1] ?? '';
            }
            $bin = $out;
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $bin) ?? '';
    }

    // ─── Cleanup ─────────────────────────────────────────────────────────────

    private function clean(string $text): string
    {
        // Normalise newlines, collapse runs of blank lines and trailing spaces.
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
