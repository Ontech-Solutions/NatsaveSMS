<?php

namespace App\Filament\Resources\BulkMessageResource\Pages;

use App\Filament\Resources\BulkMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBulkMessage extends EditRecord
{
    protected static string $resource = BulkMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
