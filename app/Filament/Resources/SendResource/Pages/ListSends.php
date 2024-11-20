<?php

namespace App\Filament\Resources\SendResource\Pages;

use App\Filament\Resources\SendResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSends extends ListRecords
{
    protected static string $resource = SendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
