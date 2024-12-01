<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Report';
    protected static ?string $pluralModelLabel = 'Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Report Information')
                    ->description('Basic report details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter report name'),

                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'message_history' => 'Message History',
                                        'usage_summary' => 'Usage Summary',
                                        'delivery_stats' => 'Delivery Statistics',
                                        'user_activity' => 'User Activity',
                                        'custom' => 'Custom Report'
                                    ])
                                    ->default('message_history')
                                    ->reactive(),

                                Forms\Components\Select::make('generated_by')
                                    ->relationship('generatedBy', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('status')
                                    ->required()
                                    ->options([
                                        'pending' => 'Pending',
                                        'generating' => 'Generating',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed'
                                    ])
                                    ->default('pending')
                                    ->disabled(fn ($record) => $record && in_array($record->status, ['completed', 'failed']))
                                    ->dehydrated(),
                            ]),
                    ]),

                Section::make('Report Parameters')
                    ->description('Configure report parameters')
                    ->schema([
                        Forms\Components\KeyValue::make('parameters')
                            ->required()
                            ->keyLabel('Parameter')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Parameter')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('include_charts')
                            ->label('Include Visual Charts')
                            ->helperText('Generate visual representations of the data')
                            ->default(true),

                        Forms\Components\Select::make('file_format')
                            ->label('Export Format')
                            ->options([
                                'pdf' => 'PDF Document',
                                'excel' => 'Excel Spreadsheet',
                                'csv' => 'CSV File'
                            ])
                            ->default('pdf')
                            ->required(),
                    ]),

                Section::make('Report Data')
                    ->description('Report output and file details')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('data')
                            ->label('Report Data')
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record && !empty($record->data)),

                        Forms\Components\TextInput::make('file_path')
                            ->label('File Location')
                            ->disabled()
                            ->helperText('Generated report file location')
                            ->visible(fn ($record) => $record && !empty($record->file_path)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'message_history',
                        'success' => 'usage_summary',
                        'warning' => 'delivery_stats',
                        'info' => 'user_activity',
                        'gray' => 'custom',
                    ]),

                Tables\Columns\TextColumn::make('generatedBy.name')
                    ->label('Generated By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'generating',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Generated At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('include_charts')
                    ->boolean()
                    ->label('Charts')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'message_history' => 'Message History',
                        'usage_summary' => 'Usage Summary',
                        'delivery_stats' => 'Delivery Statistics',
                        'user_activity' => 'User Activity',
                        'custom' => 'Custom Report'
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'generating' => 'Generating',
                        'completed' => 'Completed',
                        'failed' => 'Failed'
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Report $record) => $record->file_path)
                    ->openUrlInNewTab()
                    ->visible(fn (Report $record) => $record->status === 'completed'),
                Tables\Actions\Action::make('regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn (Report $record) => $record->update(['status' => 'pending']))
                    ->requiresConfirmation()
                    ->visible(fn (Report $record) => in_array($record->status, ['failed', 'completed'])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('downloadSelected')
                        ->label('Download Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records): void {
                            // Implementation for downloading multiple reports
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'gray';
    }
}