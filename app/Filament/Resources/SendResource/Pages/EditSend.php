<?php

namespace App\Filament\Resources\SendResource\Pages;

use App\Filament\Resources\SendResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSend extends EditRecord
{
    protected static string $resource = SendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
