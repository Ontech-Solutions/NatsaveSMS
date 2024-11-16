<?php

namespace App\Filament\Resources\SmsOutboxResource\Pages;

use App\Filament\Resources\SmsOutboxResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSmsOutbox extends ViewRecord
{
    protected static string $resource = SmsOutboxResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}