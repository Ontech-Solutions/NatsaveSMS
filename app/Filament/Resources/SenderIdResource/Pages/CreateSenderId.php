<?php

namespace App\Filament\Resources\SenderIdResource\Pages;

use App\Filament\Resources\SenderIdResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSenderId extends CreateRecord
{
    protected static string $resource = SenderIdResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
