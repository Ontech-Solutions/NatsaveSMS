<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledSmsResource\Pages;
use App\Filament\Resources\ScheduledSmsResource\RelationManagers;
use App\Models\ScheduledSms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScheduledSmsResource extends Resource
{
    protected static ?string $model = ScheduledSms::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('department_id')
                    ->numeric(),
                Forms\Components\TextInput::make('user_id')
                    ->numeric(),
                Forms\Components\TextInput::make('sender_id')
                    ->numeric(),
                Forms\Components\TextInput::make('recipient')
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('schedule_type')
                    ->required(),
                Forms\Components\Textarea::make('schedule_data')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('next_run_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('last_run_at'),
                Forms\Components\TextInput::make('status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('schedule_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('next_run_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_run_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledSms::route('/'),
            'create' => Pages\CreateScheduledSms::route('/create'),
            'edit' => Pages\EditScheduledSms::route('/{record}/edit'),
        ];
    }
}
