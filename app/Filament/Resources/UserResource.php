<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Enter the user\'s basic details')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter full name'),

                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(ignorable: fn ($record) => $record)
                                ->placeholder('Enter email address'),

                            Forms\Components\TextInput::make('password')
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                                ->minLength(8)
                                ->same('passwordConfirmation')
                                ->placeholder('Enter password'),

                            Forms\Components\TextInput::make('passwordConfirmation')
                                ->password()
                                ->label('Confirm Password')
                                ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                                ->minLength(8)
                                ->placeholder('Confirm password')
                                ->dehydrated(false),

                            Forms\Components\Select::make('role')
                                ->required()
                                ->options([
                                    'Admin' => 'Admin',
                                    'Department Head' => 'Department Head',
                                    'Branch User' => 'Branch User',
                                    'API User' => 'API User',
                                ])
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set) => 
                                    $state === 'API User' ? 
                                        $set('api_key', Str::random(32)) && 
                                        $set('api_secret', Str::random(64)) : null
                                ),
                        ]),
                    ]),

                Section::make('Department & Branch')
                    ->description('Assign user to department and branch')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('department_id')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('description')
                                        ->maxLength(65535),
                                ]),

                            Forms\Components\Select::make('branch_id')
                                ->relationship('branch', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('department_id')
                                        ->relationship('department', 'name')
                                        ->required(),
                                ]),
                        ]),
                    ]),

                Section::make('API Configuration')
                    ->description('API access credentials and limits')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('api_key')
                                ->readonly()
                                ->visible(fn ($get) => $get('role') === 'API User'),

                            Forms\Components\TextInput::make('api_secret')
                                ->readonly()
                                ->visible(fn ($get) => $get('role') === 'API User'),

                            Forms\Components\TextInput::make('daily_limit')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(1000000)
                                ->visible(fn ($get) => $get('role') === 'API User'),

                            Forms\Components\TextInput::make('monthly_limit')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(10000000)
                                ->visible(fn ($get) => $get('role') === 'API User'),
                        ]),
                    ])->visible(fn ($get) => $get('role') === 'API User'),

                Section::make('Account Status')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->required()
                                ->default(true)
                                ->helperText('Inactive users cannot log in'),

                            Forms\Components\DateTimePicker::make('email_verified_at')
                                ->readonly()
                                ->label('Email Verified'),
                                
                            Forms\Components\DateTimePicker::make('last_login_at')
                                ->readonly()
                                ->label('Last Login'),

                            Forms\Components\TextInput::make('last_login_ip')
                                ->readonly()
                                ->label('Last Login IP'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'warning' => 'API User',
                        'success' => 'Admin',
                        'primary' => 'Department Head',
                        'secondary' => 'Branch User',
                    ]),

                Tables\Columns\TextColumn::make('department.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'Admin' => 'Admin',
                        'Department Head' => 'Department Head',
                        'Branch User' => 'Branch User',
                        'API User' => 'API User',
                    ]),
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name'),
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resetPassword')
                    ->action(function (User $record) {
                        $record->update([
                            'password' => Hash::make('Password123!')
                        ]);
                    })
                    ->requiresConfirmation()
                    ->color('warning')
                    ->icon('heroicon-o-key'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activateSelected')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check')
                        ->color('success'),
                    Tables\Actions\BulkAction::make('deactivateSelected')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-mark')
                        ->color('danger'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //RelationManagers\SmsOutboxesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}