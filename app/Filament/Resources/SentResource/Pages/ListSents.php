<?php

namespace App\Filament\Resources\SentResource\Pages;

use App\Filament\Resources\SentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSents extends ListRecords
{
    protected static string $resource = SentResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }

    protected function getDefaultTableRecordsPerPage(): int 
    {
        return 25;
    }
}
