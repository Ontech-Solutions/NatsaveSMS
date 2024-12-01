<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BulkMessageResource\Pages;
use App\Filament\Resources\BulkMessageResource\RelationManagers;
use App\Models\BulkMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BulkMessageResource extends Resource
{
    protected static ?string $model = BulkMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
                Forms\Components\Textarea::make('message_template')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_recipients')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('processed_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('success_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('failed_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\DateTimePicker::make('scheduled_at'),
                Forms\Components\DateTimePicker::make('completed_at'),
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
                Tables\Columns\TextColumn::make('total_recipients')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('success_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('failed_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListBulkMessages::route('/'),
            'create' => Pages\CreateBulkMessage::route('/create'),
            'edit' => Pages\EditBulkMessage::route('/{record}/edit'),
        ];
    }
}
