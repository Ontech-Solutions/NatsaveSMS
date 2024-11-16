<?php

namespace App\Filament\Resources\DepartmentSenderIdResource\Pages;

use App\Filament\Resources\DepartmentSenderIdResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartmentSenderIds extends ListRecords
{
    protected static string $resource = DepartmentSenderIdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
