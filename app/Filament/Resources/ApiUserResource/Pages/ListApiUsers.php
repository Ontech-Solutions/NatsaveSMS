<?php

namespace App\Filament\Resources\ApiUserResource\Pages;

use App\Filament\Resources\ApiUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApiUsers extends ListRecords
{
    protected static string $resource = ApiUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create API User')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTableQuery(): Builder
    {
        return parent::getTableQuery()->withCount([
            'apiLogs as today_requests' => function (Builder $query) {
                $query->whereDate('created_at', now()->toDateString());
            }
        ]);
    }
}