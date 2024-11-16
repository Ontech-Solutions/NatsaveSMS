<?php

namespace App\Filament\Resources\SmsOutboxResource\Pages;

use App\Filament\Resources\SmsOutboxResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSmsOutbox extends EditRecord
{
    protected static string $resource = SmsOutboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
