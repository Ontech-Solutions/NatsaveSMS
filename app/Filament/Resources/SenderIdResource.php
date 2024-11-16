<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SenderIdResource\Pages;
use App\Models\Department;
use App\Models\SenderId;
use App\Models\SenderStatus;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SenderIdResource extends Resource
{
    protected static ?string $model = SenderId::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Setup';
    protected static ?string $modelLabel = 'Sender ID';
    protected static ?string $pluralModelLabel = 'Sender IDs';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sender ID Details')
                    ->schema([
                        Forms\Components\TextInput::make('sender_name')
                            ->label('Sender ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(11)
                            ->placeholder('e.g., COMPANYNAME')
                            ->helperText('Maximum 11 characters, no spaces'),

                        Forms\Components\Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Textarea::make('purpose')
                            ->label('Purpose/Description')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('sender_status_id')
                            ->default(fn () => SenderStatus::where('name', 'pending')->first()->id)
                            ->dehydrated(true),

                    ])->columns(2),

                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Select::make('sender_status_id')
                            ->relationship('status', 'name')
                            ->disabled()
                            ->visible(fn (string $operation): bool => $operation === 'edit'),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->disabled()
                            ->visible(fn (string $operation): bool => $operation === 'edit'),

                        Forms\Components\Select::make('approved_by')
                            ->relationship('approver', 'name')
                            ->disabled()
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                    ])
                    ->columns(3)
                    ->collapsed()
                    ->collapsible()
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender_name')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('purpose')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'inactive', 'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('approver.name')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('approve')
                        ->action(function (SenderId $record): void {
                            $activeStatus = SenderStatus::where('name', 'active')->first();
                            $record->update([
                                'sender_status_id' => $activeStatus->id,
                                'approved_at' => now(),
                                'approved_by' => auth()->id(),
                            ]);
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (SenderId $record): bool => $record->status->name === 'pending'),

                    Tables\Actions\Action::make('deactivate')
                        ->action(function (SenderId $record): void {
                            $inactiveStatus = SenderStatus::where('name', 'inactive')->first();
                            $record->update([
                                'sender_status_id' => $inactiveStatus->id,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn (SenderId $record): bool => $record->status->name === 'active'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSenderIds::route('/'),
            'create' => Pages\CreateSenderId::route('/create'),
            'edit' => Pages\EditSenderId::route('/{record}/edit'),
        ];
    }
}