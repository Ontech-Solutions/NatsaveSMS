<?php

namespace App\Filament\Resources\SmsInboxResource\Pages;

use App\Filament\Resources\SmsInboxResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSmsInbox extends EditRecord
{
    protected static string $resource = SmsInboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
