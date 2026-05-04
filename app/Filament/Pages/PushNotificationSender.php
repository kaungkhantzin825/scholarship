<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Scholarship;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Google\Auth\Credentials\ServiceAccountCredentials;

class PushNotificationSender extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Push Notifications';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $title = 'Send Push Notification';
    
    protected static string $view = 'filament.pages.push-notification-sender';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('target')
                    ->label('Send To')
                    ->options([
                        'all' => 'All Users (Topic: all_users)',
                        'specific' => 'Specific User',
                    ])
                    ->default('all')
                    ->reactive()
                    ->required(),
                
                Select::make('user_id')
                    ->label('Select User')
                    ->options(User::whereNotNull('fcm_token')->pluck('name', 'id'))
                    ->searchable()
                    ->required(fn (callable $get) => $get('target') === 'specific')
                    ->visible(fn (callable $get) => $get('target') === 'specific'),

                Select::make('scholarship_id')
                    ->label('Link to Scholarship Post (Optional)')
                    ->options(Scholarship::pluck('title', 'id'))
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $scholarship = Scholarship::find($state);
                            if ($scholarship) {
                                $set('title', $scholarship->title);
                                $set('body', $scholarship->excerpt ?? substr(strip_tags($scholarship->description), 0, 100) . '...');
                            }
                        }
                    }),

                TextInput::make('title')
                    ->label('Notification Title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('body')
                    ->label('Notification Body')
                    ->required()
                    ->maxLength(500),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Notification')
                ->icon('heroicon-o-paper-airplane')
                ->submit('sendNotification'),
        ];
    }

    public function sendNotification()
    {
        $data = $this->form->getState();

        try {
            $credentialsPath = base_path(env('FIREBASE_CREDENTIALS', 'firebase_credentials.json'));
            
            if (!File::exists($credentialsPath)) {
                throw new \Exception('Firebase credentials file not found. Please download your service account key and save it as firebase_credentials.json in the backend root folder.');
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $credentialsPath
            );

            $token = $credentials->fetchAuthToken();
            $accessToken = $token['access_token'];

            $projectId = json_decode(File::get($credentialsPath))->project_id;

            $message = [
                'message' => [
                    'notification' => [
                        'title' => $data['title'],
                        'body' => $data['body'],
                    ],
                    'data' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                    'android' => [
                        'notification' => [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'sound' => 'default',
                        ],
                    ],
                ]
            ];

            if (!empty($data['scholarship_id'])) {
                $scholarship = Scholarship::find($data['scholarship_id']);
                if ($scholarship) {
                    $message['message']['data']['scholarship_slug'] = (string) $scholarship->slug;
                    $imageUrl = $scholarship->banner_url;
                    if ($imageUrl && strpos($imageUrl, 'http') === 0) {
                        $message['message']['notification']['image'] = $imageUrl;
                        $message['message']['android']['notification']['image'] = $imageUrl;
                    }
                }
            }

            if ($data['target'] === 'all') {
                $message['message']['topic'] = 'all_users';
            } else {
                $user = User::find($data['user_id']);
                if (!$user || !$user->fcm_token) {
                    throw new \Exception('The selected user does not have an FCM token setup yet.');
                }
                $message['message']['token'] = $user->fcm_token;
            }

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

            if ($response->successful()) {
                Notification::make()
                    ->title('Notification Sent')
                    ->body('The push notification was sent successfully.')
                    ->success()
                    ->send();

                $this->form->fill();
            } else {
                throw new \Exception('FCM API Error: ' . $response->body());
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send notification')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
