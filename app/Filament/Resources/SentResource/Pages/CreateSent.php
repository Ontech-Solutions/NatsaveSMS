<?php

namespace App\Filament\Resources\SentResource\Pages;

use App\Filament\Resources\SentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSent extends CreateRecord
{
    protected static string $resource = SentResource::class;
}
