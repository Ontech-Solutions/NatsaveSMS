<?php

namespace App\Filament\Resources\ApiUserResource\Pages;

use App\Filament\Resources\ApiUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class EditApiUser extends EditRecord
{
    protected static string $resource = ApiUserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure allowed_ips is an array
        $data['allowed_ips'] = empty($data['allowed_ips']) ? ['*'] : $data['allowed_ips'];
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_keys')
                ->label('Generate New Keys')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->action(function (): void {
                    $apiKey = 'key_' . Str::random(32);
                    $apiSecret = 'secret_' . Str::random(32);

                    $this->record->update([
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret,
                        'is_active' => true,
                        'last_key_generated_at' => now(),
                    ]);

                    Notification::make()
                        ->title('New API Keys Generated')
                        ->body("API Key: {$apiKey}\n\nAPI Secret: {$apiSecret}\n\nPlease save these credentials securely!")
                        ->warning()
                        ->persistent()
                        ->send();
                })
                ->requiresConfirmation(),
            
            Actions\Action::make('revoke_keys')
                ->label('Revoke Keys')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    $this->record->update([
                        'api_key' => null,
                        'api_secret' => null,
                        'is_active' => false,
                        'last_key_revoked_at' => now(),
                    ]);

                    Notification::make()
                        ->title('API Keys Revoked')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),

            Actions\DeleteAction::make(),
        ];
    }
}