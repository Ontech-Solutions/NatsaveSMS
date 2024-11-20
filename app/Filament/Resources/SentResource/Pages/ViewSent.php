<?php

namespace App\Filament\Resources\SentResource\Pages;

use App\Filament\Resources\SentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSent extends ViewRecord
{
    protected static string $resource = SentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add any additional actions here
        ];
    }
}