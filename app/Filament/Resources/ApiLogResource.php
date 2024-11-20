<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiUserResource\Pages;
use App\Models\ApiUser;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Illuminate\Database\Eloquent\Builder;

class ApiUserResource extends Resource
{
    protected static ?string $model = ApiUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'API Management';

    protected static ?string $modelLabel = 'API User';

    protected static ?string $navigationLabel = 'API Users';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('usage_count', '>', 5000)->exists() 
            ? 'warning'
            : 'success';
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Tabs::make('API User Management')->tabs([
                    Tabs\Tab::make('Basic Information')
                        ->schema([
                            Section::make('User Details')->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter user name')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('user@example.com')
                                    ->columnSpan(1),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535)
                                    ->placeholder('Enter description')
                                    ->columnSpan(2),
                            ])->columns(2),
                        ]),
                    
                    Tabs\Tab::make('API Settings')
                        ->schema([
                            Section::make('Usage Limits')->schema([
                                Forms\Components\TextInput::make('daily_limit')
                                    ->numeric()
                                    ->default(1000)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(100000)
                                    ->suffix('requests/day')
                                    ->helperText('Maximum number of API requests allowed per day'),
                                Forms\Components\Select::make('rate_limit')
                                    ->options([
                                        60 => '60 requests per minute',
                                        120 => '120 requests per minute',
                                        300 => '300 requests per minute',
                                        600 => '600 requests per minute',
                                    ])
                                    ->default(60)
                                    ->required(),
                            ])->columns(2),

                            Section::make('Access Control')->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->default(true)
                                    ->helperText('Toggle to enable/disable API access'),
                                Forms\Components\Select::make('access_level')
                                    ->options([
                                        'read' => 'Read Only',
                                        'write' => 'Read & Write',
                                        'admin' => 'Full Access',
                                    ])
                                    ->default('read')
                                    ->required()
                                    ->helperText('Set the API access level'),
                                Forms\Components\TagsInput::make('allowed_ips')
                                    ->placeholder('Add IP addresses')
                                    ->helperText('Leave empty to allow all IPs')
                                    ->separator(','),
                            ])->columns(2),
                        ]),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('api_key')
                    ->copyable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? Str::mask($state, '*', 4) : '-')
                    ->copyMessage('API key copied')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('daily_limit')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('usage_count')
                    ->numeric()
                    ->sortable()
                    ->label('Total Usage')
                    ->badge()
                    ->color(fn ($state) => 
                        $state > 5000 ? 'warning' : 
                        ($state > 1000 ? 'info' : 'success')
                    ),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->indicator('Active Users'),
                Tables\Filters\SelectFilter::make('access_level')
                    ->options([
                        'read' => 'Read Only',
                        'write' => 'Read & Write',
                        'admin' => 'Full Access',
                    ]),
                Tables\Filters\Filter::make('high_usage')
                    ->query(fn (Builder $query) => $query->where('usage_count', '>', 5000))
                    ->label('High Usage')
                    ->indicator('High Usage Users'),
            ])
            ->actions([
                Action::make('generate')
                    ->label('Generate Keys')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->action(function (ApiUser $record): void {
                        $apiKey = 'key_' . Str::random(32);
                        $apiSecret = 'secret_' . Str::random(32);

                        $record->update([
                            'api_key' => $apiKey,
                            'api_secret' => $apiSecret,
                            'is_active' => true,
                            'last_key_generated_at' => now(),
                        ]);

                        Notification::make()
                            ->title('API Keys Generated Successfully')
                            ->body("API Key: {$apiKey}\n\nAPI Secret: {$apiSecret}\n\nPlease save these credentials securely!")
                            ->warning()
                            ->persistent()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Action::make('revoke')
                    ->label('Revoke Keys')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function (ApiUser $record): void {
                        $record->update([
                            'api_key' => null,
                            'api_secret' => null,
                            'is_active' => false,
                            'last_key_revoked_at' => now(),
                        ]);

                        Notification::make()
                            ->title('API Keys Revoked')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('revoke')
                    ->label('Revoke Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function (Collection $records): void {
                        $records->each->update([
                            'api_key' => null,
                            'api_secret' => null,
                            'is_active' => false,
                            'last_key_revoked_at' => now(),
                        ]);

                        Notification::make()
                            ->title('API Keys Revoked')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiUsers::route('/'),
            'create' => Pages\CreateApiUser::route('/create'),
            'edit' => Pages\EditApiUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'api_key'];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'API Management';
    }
}