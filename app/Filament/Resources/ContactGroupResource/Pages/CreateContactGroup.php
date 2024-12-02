<?php

namespace App\Filament\Resources\ContactGroupResource\Pages;

use App\Filament\Resources\ContactGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContactGroup extends CreateRecord
{
    protected static string $resource = ContactGroupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['department_id'] = auth()->user()->department_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
