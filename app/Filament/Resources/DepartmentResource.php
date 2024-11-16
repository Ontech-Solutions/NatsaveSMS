<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Filament\Resources\DepartmentResource\RelationManagers\UsersRelationManager;
use App\Filament\Resources\DepartmentResource\RelationManagers\BranchesRelationManager;
use App\Models\Department;
use App\Models\User;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Colors\Color;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Setup';
    protected static ?string $modelLabel = 'Department';
    protected static ?string $pluralModelLabel = 'Departments';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Department Information')
                    ->description('Add basic department details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->placeholder('Enter department name'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->placeholder('Enter department description')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive'
                            ])
                            ->default('active')
                            ->native(false),
                    ]),

                Forms\Components\Section::make('SMS Limits')
                    ->description('Configure messaging limits')
                    ->schema([
                        Forms\Components\TextInput::make('daily_limit')
                            ->label('Daily SMS Limit')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1000000)
                            ->default(1000)
                            ->suffix('messages')
                            ->placeholder('Enter daily limit'),

                        Forms\Components\TextInput::make('monthly_limit')
                            ->label('Monthly SMS Limit')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000000)
                            ->default(30000)
                            ->suffix('messages')
                            ->placeholder('Enter monthly limit'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->words(10)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (str_word_count($state) <= 10) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('daily_limit')
                    ->label('Daily Limit')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('monthly_limit')
                    ->label('Monthly Limit')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'inactive',
                        'success' => 'active',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Departments Yet')
            ->emptyStateDescription('Create your first department by clicking the button below.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Department'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
            RelationManagers\BranchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['users', 'branches']);
    }
}