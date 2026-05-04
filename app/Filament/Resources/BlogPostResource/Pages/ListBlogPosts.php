<?php

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;
    
    protected function getHeaderActions(): array 
    { 
        return [
            Actions\Action::make('generate_ai')
                ->label('Generate with AI')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->form([
                    Textarea::make('prompt')
                        ->label('What should the blog post be about?')
                        ->required()
                        ->placeholder('e.g., A guide on how to apply for the fully-funded DAAD scholarship in Germany...')
                        ->rows(4)
                ])
                ->action(function (array $data) {
                    $apiKey = env('GEMINI_API_KEY');
                    if (!$apiKey) {
                        Notification::make()->title('Gemini API key is missing.')->danger()->send();
                        return;
                    }

                    $prompt = $data['prompt'];
                    $systemPrompt = "You are an expert blog writer for a scholarship platform. Create a comprehensive, engaging, and professional blog post based on the following topic. Return ONLY a valid JSON object with strictly these keys: 'title' (string), 'excerpt' (string, short summary), and 'content' (string, well-formatted HTML with proper tags like <h2>, <p>, <ul>, <li>, <strong>. Do NOT use markdown).";
                    
                    try {
                        $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                            'contents' => [
                                ['parts' => [['text' => $systemPrompt . "\n\nTopic: " . $prompt]]]
                            ],
                            'generationConfig' => [
                                'responseMimeType' => 'application/json',
                            ]
                        ]);

                        if ($response->successful()) {
                            $result = $response->json();
                            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                            
                            $aiData = json_decode($text, true);
                            
                            if ($aiData && isset($aiData['title']) && isset($aiData['content'])) {
                                \App\Models\BlogPost::create([
                                    'title' => $aiData['title'],
                                    'slug' => Str::slug($aiData['title']) . '-' . rand(1000, 9999),
                                    'excerpt' => $aiData['excerpt'] ?? '',
                                    'content' => $aiData['content'],
                                    'is_published' => true,
                                    'published_at' => now(),
                                    'author_id' => auth()->id() ?? 1,
                                ]);

                                Notification::make()
                                    ->title('Blog post generated and published successfully!')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()->title('AI returned an invalid format. Try again.')->danger()->send();
                            }
                        } else {
                            Notification::make()->title('Failed to connect to Google Gemini API.')->danger()->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),
            Actions\CreateAction::make(),
        ]; 
    }
}
