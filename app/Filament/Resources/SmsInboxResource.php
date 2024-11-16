<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsInboxResource\Pages;
use App\Filament\Resources\SmsInboxResource\RelationManagers;
use App\Models\SmsInbox;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SmsInboxResource extends Resource
{
    protected static ?string $model = SmsInbox::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('message_id')
                    ->required(),
                Forms\Components\TextInput::make('sender')
                    ->required(),
                Forms\Components\TextInput::make('recipient')
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('received_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('processed_at'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('error_message')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('message_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sender')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('received_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
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
            'index' => Pages\ListSmsInboxes::route('/'),
            'create' => Pages\CreateSmsInbox::route('/create'),
            'edit' => Pages\EditSmsInbox::route('/{record}/edit'),
        ];
    }
}
