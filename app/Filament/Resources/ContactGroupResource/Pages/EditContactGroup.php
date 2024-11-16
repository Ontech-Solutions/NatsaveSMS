<?php

namespace App\Filament\Resources\ContactGroupResource\Pages;

use App\Filament\Resources\ContactGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactGroup extends EditRecord
{
    protected static string $resource = ContactGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure department_id cannot be changed even if somehow submitted
        $data['department_id'] = $this->record->department_id;

        return $data;
    }
}
