<?php

namespace App\Filament\Resources\ApiUserResource\Pages;

use App\Filament\Resources\ApiUserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class CreateApiUser extends CreateRecord
{
    protected static string $resource = ApiUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate initial API credentials
        $data['api_key'] = 'key_' . Str::random(32);
        $data['api_secret'] = 'secret_' . Str::random(32);
        $data['last_key_generated_at'] = now();
        
        // Ensure allowed_ips is an array
        $data['allowed_ips'] = empty($data['allowed_ips']) ? ['*'] : $data['allowed_ips'];
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Show credentials notification
        Notification::make()
            ->title('API User Created Successfully')
            ->body("API Key: {$this->record->api_key}\n\nAPI Secret: {$this->record->api_secret}\n\nPlease save these credentials securely!")
            ->warning()
            ->persistent()
            ->send();
    }
}