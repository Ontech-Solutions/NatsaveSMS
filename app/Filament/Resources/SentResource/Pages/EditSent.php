<?php

namespace App\Filament\Resources\SentResource\Pages;

use App\Filament\Resources\SentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSent extends EditRecord
{
    protected static string $resource = SentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
