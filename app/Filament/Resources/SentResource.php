<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SentResource\Pages;
use App\Models\Sent;
use App\Models\SenderId;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Models\Send;
use App\Models\SenderStatus;

class SentResource extends Resource
{
    protected static ?string $model = Sent::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationGroup = 'SMS Management';
    protected static ?string $modelLabel = 'Sent Message';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('source_addr')
                    ->label('Sender ID')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('destination_addr')
                    ->label('Recipient')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('sms_type')
                    ->colors([
                        'primary' => 'single',
                        'success' => 'bulk',
                        'warning' => 'group',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'submitted',
                        'success' => 'delivered',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->visible(fn ($record) => $record && $record->status === 'failed')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('retry_count')
                    ->label('Retries')
                    ->visible(fn ($record) => $record && $record->retry_count > 0),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                // Sender ID Filter
                Tables\Filters\SelectFilter::make('source_addr')
                    ->label('Sender ID')
                    ->options(function () {
                        // If user is admin, show all sender IDs
                        // If not, show only department's sender IDs
                       // Get the active status ID
                       $activeStatusId = SenderStatus::where('name', 'active')->first()?->id;
                        
                       // Build the query
                       $query = SenderId::query();
                       
                       if ($activeStatusId) {
                           $query->where('sender_status_id', $activeStatusId);
                       }
                       
                       if (auth()->user()->role !== 'Admin') {
                           $query->whereHas('departments', function ($q) {
                               $q->where('departments.id', auth()->user()->department_id);
                           });
                       }
                       
                       return $query->pluck('sender_name', 'sender_name')
                           ->toArray();
                   })
                   ->multiple()
                   ->preload(),

                Tables\Filters\SelectFilter::make('sms_type')
                    ->options([
                        'single' => 'Single',
                        'bulk' => 'Bulk',
                        'group' => 'Group',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'submitted' => 'Submitted',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                    ]),

                // Date Range Filter
                Tables\Filters\Filter::make('sent_at')
                    ->form([
                        Forms\Components\DatePicker::make('sent_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('sent_until')
                            ->label('To Date'),
                    ])
                    ->indicator(function (array $data): ?string {
                        if (!$data['sent_from'] && !$data['sent_until']) {
                            return null;
                        }
                        
                        if ($data['sent_from'] && $data['sent_until']) {
                            return 'Sent between ' . $data['sent_from'] . ' and ' . $data['sent_until'];
                        }
                        
                        return $data['sent_from'] 
                            ? 'Sent from ' . $data['sent_from']
                            : 'Sent until ' . $data['sent_until'];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sent_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '>=', $date),
                            )
                            ->when(
                                $data['sent_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '<=', $date),
                            );
                    })
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (?Sent $record) => $record && $record->status === 'failed' && $record->retry_count < 3)
                    ->action(function (Sent $record) {
                        try {
                            // Create new record in sends table
                            Send::create([
                                'message_id' => 'RESEND-' . $record->message_id,
                                'source_addr' => $record->source_addr,
                                'destination_addr' => $record->destination_addr,
                                'message' => $record->message,
                                'sms_type' => $record->sms_type,
                                'message_type' => $record->message_type,
                                'recipient_count' => 1,
                                'status' => 'pending',
                                'service_type' => $record->service_type,
                                'data_coding' => $record->data_coding,
                                'registered_delivery' => $record->registered_delivery,
                                'priority_flag' => $record->priority_flag,
                                'user_id' => auth()->id(),
                            ]);

                            // Update retry count
                            $record->increment('retry_count');
                            $record->last_retry_at = now();
                            $record->save();

                            Notification::make()
                                ->title('Message Queued for Resending')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Resend Message')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->modifyQueryUsing(function (Builder $query) {
                // Filter by department if user is not admin
                if (auth()->user()->role !== 'Admin') {
                    $query->where('user_id', auth()->id());
                }
                return $query;
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSents::route('/'),
            'view' => Pages\ViewSent::route('/{record}'),
        ];
    }
}