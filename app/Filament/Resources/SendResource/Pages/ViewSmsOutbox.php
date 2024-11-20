<?php

namespace App\Filament\Resources\SendResource\Pages;

use App\Filament\Resources\SendResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSend extends ViewRecord
{
    protected static string $resource = SendResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}