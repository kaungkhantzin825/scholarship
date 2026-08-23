<?php

namespace App\Filament\Resources\ScholarshipResource\Pages;

use App\Filament\Resources\ScholarshipResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Models\Category;
use App\Models\Scholarship;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class AiScholarshipGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ScholarshipResource::class;

    protected static string $view = 'filament.resources.scholarship-resource.pages.ai-scholarship-generator';

    protected ?string $heading = 'Generate Scholarship with AI';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                    ->label('Category (Optional - AI will suggest if not selected)')
                    ->options(Category::pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Let AI choose the category'),
                Textarea::make('prompt')
                    ->label('Scholarship Information')
                    ->required()
                    ->placeholder("e.g., Generate a listing for the Oxford Pershing Square Graduate Scholarship in UK. Fully funded Master's degree...")
                    ->rows(6)
                    ->helperText('Provide as much detail as possible. AI will extract and structure the information.')
            ])
            ->statePath('data');
    }

    public function generate()
    {
        $data = $this->form->getState();

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            Notification::make()
                ->title('Gemini API key is missing')
                ->body('Please add GEMINI_API_KEY to your .env file')
                ->danger()
                ->send();
            return;
        }

        // Get all categories for AI to choose from
        $categories = Category::pluck('name', 'id')->toArray();
        $categoryList = implode(', ', $categories);

        $systemPrompt = "You are an expert scholarship data curator. Extract and generate a complete scholarship listing based on the following information. If certain details are missing, invent plausible placeholders or generic info. Return ONLY a valid JSON object with STRICTLY these exact keys:\n- 'title' (string)\n- 'excerpt' (string, 2 sentences max)\n- 'description' (string, detailed HTML formatted description)\n- 'amount' (number or null, e.g. 5000, 10000, or null for 'Fully Funded'. NO currency symbols, NO commas)\n- 'amount_type' (string, one of: 'full', 'partial', 'monthly', 'other')\n- 'currency' (string, e.g. 'USD', 'EUR', 'GBP')\n- 'country' (string, e.g. 'United Kingdom')\n- 'level' (string, MUST BE exactly one of: 'bachelor', 'master', 'phd', 'diploma', 'any')\n- 'field_of_study' (string, e.g. 'Computer Science', 'Engineering')\n- 'benefits' (string, HTML formatting)\n- 'eligibility' (string, HTML formatting)\n- 'required_documents' (string, HTML formatting)\n- 'deadline' (string, format: YYYY-MM-DD. Make sure it's in the future)\n- 'official_link' (string, starting with https://. Use a generic valid URL if unknown)\n- 'category_name' (string, choose the most appropriate from: {$categoryList})";
        
        try {
            // Use the correct model name: gemini-2.5-flash
            $response = Http::timeout(60)->post("https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $systemPrompt . "\n\nInformation: " . $data['prompt'] . "\n\nIMPORTANT: Return ONLY valid JSON, no markdown formatting."]]]
                ],
            ]);

            if (!$response->successful()) {
                Notification::make()
                    ->title('Failed to connect to Google Gemini API')
                    ->body('Status: ' . $response->status() . ' - ' . $response->body())
                    ->danger()
                    ->send();
                return;
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            if (empty($text)) {
                Notification::make()
                    ->title('AI returned empty response')
                    ->body('Please try again with more detailed information')
                    ->danger()
                    ->send();
                return;
            }

            // Clean up markdown JSON formatting if present
            $text = preg_replace('/^```json\s*/m', '', $text);
            $text = preg_replace('/\s*```$/m', '', $text);
            $text = trim($text);

            $aiData = json_decode($text, true);
            
            if (!$aiData || !isset($aiData['title'])) {
                Notification::make()
                    ->title('AI returned invalid format')
                    ->body('Response: ' . substr($text, 0, 200))
                    ->danger()
                    ->send();
                return;
            }

            // Determine category
            $categoryId = $data['category_id'] ?? null;
            
            if (!$categoryId && isset($aiData['category_name'])) {
                // Find category by name (case-insensitive)
                $category = Category::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($aiData['category_name']) . '%'])->first();
                $categoryId = $category?->id;
            }
            
            // Fallback to first category if still not found
            if (!$categoryId) {
                $categoryId = Category::first()?->id;
            }

            if (!$categoryId) {
                Notification::make()
                    ->title('No categories available')
                    ->body('Please create at least one category first')
                    ->danger()
                    ->send();
                return;
            }

            // Validate level
            $validLevels = ['bachelor', 'master', 'phd', 'diploma', 'any'];
            $level = strtolower($aiData['level'] ?? 'any');
            if (!in_array($level, $validLevels)) {
                $level = 'any';
            }

            // Validate amount_type
            $validAmountTypes = ['full', 'partial', 'monthly', 'other'];
            $amountType = strtolower($aiData['amount_type'] ?? 'full');
            if (!in_array($amountType, $validAmountTypes)) {
                $amountType = 'full';
            }

            // Clean and extract numeric amount
            $amountRaw = $aiData['amount'] ?? 'Fully Funded';
            $amountNumeric = null;
            
            // Extract numbers from amount string (e.g., "£5,000" -> 5000)
            if (preg_match('/[\d,]+/', $amountRaw, $matches)) {
                $amountNumeric = (float) str_replace(',', '', $matches[0]);
            }

            Scholarship::create([
                'title' => $aiData['title'],
                'slug' => Str::slug($aiData['title']) . '-' . rand(1000, 9999),
                'category_id' => $categoryId,
                'excerpt' => $aiData['excerpt'] ?? '',
                'description' => $aiData['description'] ?? $aiData['excerpt'] ?? '',
                'amount' => $amountNumeric,
                'amount_type' => $amountType,
                'currency' => $aiData['currency'] ?? 'USD',
                'country' => $aiData['country'] ?? 'Global',
                'level' => $level,
                'field_of_study' => $aiData['field_of_study'] ?? null,
                'benefits' => $aiData['benefits'] ?? '',
                'eligibility' => $aiData['eligibility'] ?? '',
                'required_documents' => $aiData['required_documents'] ?? '',
                'deadline' => $aiData['deadline'] ?? now()->addMonths(3)->format('Y-m-d'),
                'official_link' => $aiData['official_link'] ?? 'https://scholarships.com',
                'status' => 'active',
                'is_featured' => false,
            ]);

            Notification::make()
                ->title('Scholarship generated successfully!')
                ->body('The scholarship has been created and is now active.')
                ->success()
                ->send();
            
            return redirect()->to(ScholarshipResource::getUrl('index'));
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error generating scholarship')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
