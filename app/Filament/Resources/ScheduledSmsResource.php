<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledSmsResource\Pages;
use App\Models\ScheduledSms;
use App\Models\SenderId;
use App\Models\ContactGroup;
use App\Models\Contact;
use App\Models\SenderStatus;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Builder;

class ScheduledSmsResource extends Resource
{
    protected static ?string $model = ScheduledSms::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'SMS Management';
    protected static ?string $modelLabel = 'Scheduled Message';
    protected static ?string $pluralModelLabel = 'Schedule Messages';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Wizard\Step::make('Select Type')
                    ->description('Choose your message type')
                    ->icon('heroicon-o-check')
                    ->schema([
                        Section::make('Message Configuration')
                            ->description('Select your message type and sender ID')
                            ->schema([
                                Select::make('message_type')
                                    ->label('Message Type')
                                    ->options([
                                        'single' => 'Single SMS',
                                        'bulk' => 'Bulk SMS Upload',
                                        'group' => 'Contact Group'
                                    ])
                                    ->required()
                                    ->live(),

                                Select::make('source_addr')
                                    ->label('Sender ID')
                                    ->options(function () {
                                        $activeStatusId = SenderStatus::where('name', 'active')->first()?->id;
                                        return SenderId::query()
                                            ->where('sender_status_id', $activeStatusId)
                                            ->when(auth()->user()->role !== 'Admin', function ($query) {
                                                return $query->whereHas('department', function ($q) {
                                                    $q->where('id', auth()->user()->department_id);
                                                });
                                            })
                                            ->pluck('sender_name', 'sender_name');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->helperText('Select an approved Sender ID')
                                    ->placeholder('Choose a Sender ID'),
                            ])->columns(2)
                    ]),

                Wizard\Step::make('Message Details')
                    ->description('Enter message details')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Section::make('Recipient Details')
                            ->schema([
                                TextInput::make('destination_addr')
                                    ->label('Recipient Number')
                                    ->tel()
                                    ->required()
                                    ->visible(fn (callable $get) => $get('message_type') === 'single')
                                    ->regex('/^[0-9+]+$/')
                                    ->placeholder('+260977123456'),

                                FileUpload::make('excel_file')
                                    ->label('Upload Excel/CSV')
                                    ->acceptedFileTypes([
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'text/csv'
                                    ])
                                    ->visible(fn (callable $get) => $get('message_type') === 'bulk')
                                    ->helperText('First column must contain phone numbers')
                                    ->directory('excel-uploads')
                                    ->preserveFilenames()
                                    ->maxSize(5120)
                                    ->downloadable(),

                                Select::make('contact_group_id')
                                    ->label('Select Contact Group')
                                    ->options(ContactGroup::pluck('name', 'id'))
                                    ->visible(fn (callable $get) => $get('message_type') === 'group')
                                    ->searchable()
                                    ->preload(),

                                Textarea::make('message')
                                    ->label('Message Content')
                                    ->required()
                                    ->maxLength(918)
                                    ->columnSpanFull()
                                    ->live()
                                    ->helperText(fn ($state) => !$state ? '' : sprintf(
                                        'Characters: %d | Remaining: %d | Parts: %d',
                                        strlen($state),
                                        160 - (strlen($state) % 160),
                                        ceil(strlen($state) / 160)
                                    )),

                                // Hidden SMPP fields with default values
                                Hidden::make('data_coding')->default(0),
                                Hidden::make('registered_delivery')->default(1),
                                Hidden::make('priority_flag')->default(0),
                                Hidden::make('service_type')->default('SMS'),
                            ])->columns(2),
                    ]),

                    Wizard\Step::make('Schedule Configuration')
                    ->description('Set up message schedule')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Section::make('Schedule Settings')
                            ->schema([
                                Select::make('schedule_type')
                                    ->label('Schedule Type')
                                    ->options([
                                        'one_time' => 'One Time',
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->required()
                                    ->live()
                                    ->default('one_time')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Reset related fields when schedule type changes
                                        $set('next_run_at', null);
                                        $set('daily_time', null);
                                        $set('weekly_days', null);
                                        $set('monthly_days', null);
                                    }),

                                DateTimePicker::make('next_run_at')
                                    ->label('Send At')
                                    ->required()
                                    ->minDate(now())
                                    ->visible(fn (callable $get) => $get('schedule_type') === 'one_time'),

                                Grid::make(2)
                                    ->schema([
                                        TimePicker::make('daily_time')
                                            ->withoutSeconds()
                                            ->required(fn (callable $get) => $get('schedule_type') === 'daily')
                                            ->visible(fn (callable $get) => $get('schedule_type') === 'daily')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    // Set next run to today at specified time if it's before now,
                                                    // otherwise set to tomorrow at specified time
                                                    $scheduledTime = Carbon::createFromTimeString($state);
                                                    $nextRun = $scheduledTime->copy();
                                                    
                                                    if ($nextRun->isPast()) {
                                                        $nextRun->addDay();
                                                    }
                                                    
                                                    $set('next_run_at', $nextRun);
                                                }
                                            }),

                                        Select::make('weekly_days')
                                            ->multiple()
                                            ->options([
                                                'monday' => 'Monday',
                                                'tuesday' => 'Tuesday',
                                                'wednesday' => 'Wednesday',
                                                'thursday' => 'Thursday',
                                                'friday' => 'Friday',
                                                'saturday' => 'Saturday',
                                                'sunday' => 'Sunday',
                                            ])
                                            ->required(fn (callable $get) => $get('schedule_type') === 'weekly')
                                            ->visible(fn (callable $get) => $get('schedule_type') === 'weekly')
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                if ($state && $get('daily_time')) {
                                                    // Find the next occurrence of selected days
                                                    $time = Carbon::createFromTimeString($get('daily_time'));
                                                    $nextRun = Carbon::now();
                                                    
                                                    while (!in_array(strtolower($nextRun->format('l')), $state)) {
                                                        $nextRun->addDay();
                                                    }
                                                    
                                                    $nextRun->setHour($time->hour)
                                                           ->setMinute($time->minute)
                                                           ->setSecond(0);
                                                    
                                                    if ($nextRun->isPast()) {
                                                        $nextRun->addWeek();
                                                    }
                                                    
                                                    $set('next_run_at', $nextRun);
                                                }
                                            }),

                                        Select::make('monthly_days')
                                            ->multiple()
                                            ->options(array_combine(range(1, 31), range(1, 31)))
                                            ->required(fn (callable $get) => $get('schedule_type') === 'monthly')
                                            ->visible(fn (callable $get) => $get('schedule_type') === 'monthly')
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                if ($state && $get('daily_time')) {
                                                    // Find the next occurrence of selected days
                                                    $time = Carbon::createFromTimeString($get('daily_time'));
                                                    $nextRun = Carbon::now();
                                                    
                                                    while (!in_array($nextRun->day, $state)) {
                                                        $nextRun->addDay();
                                                    }
                                                    
                                                    $nextRun->setHour($time->hour)
                                                           ->setMinute($time->minute)
                                                           ->setSecond(0);
                                                    
                                                    if ($nextRun->isPast()) {
                                                        $nextRun->addMonth();
                                                    }
                                                    
                                                    $set('next_run_at', $nextRun);
                                                }
                                            }),
                                            
                                        TimePicker::make('schedule_time')
                                            ->withoutSeconds()
                                            ->required(fn (callable $get) => in_array($get('schedule_type'), ['weekly', 'monthly']))
                                            ->visible(fn (callable $get) => in_array($get('schedule_type'), ['weekly', 'monthly']))
                                            ->label('Time')
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                $scheduleType = $get('schedule_type');
                                                if ($state && ($scheduleType === 'weekly' || $scheduleType === 'monthly')) {
                                                    $set('daily_time', $state);
                                                }
                                            }),
                                    ]),
                            ])->columns(2),
                    ]),

                    Wizard\Step::make('Preview & Confirm')
                    ->description('Review your scheduled message')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Section::make('Message Summary')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Message Details')
                                            ->schema([
                                                Placeholder::make('sender_summary')
                                                    ->label('From')
                                                    ->content(fn (callable $get) => $get('source_addr')),
                
                                                Placeholder::make('type_summary')
                                                    ->label('Message Type')
                                                    ->content(fn (callable $get) => match($get('message_type')) {
                                                        'single' => '📱 Single SMS',
                                                        'bulk' => '📊 Bulk SMS',
                                                        'group' => '👥 Group SMS',
                                                        default => 'Unknown'
                                                    }),
                
                                                Placeholder::make('schedule_summary')
                                                    ->label('Schedule Type')
                                                    ->content(fn (callable $get) => match($get('schedule_type')) {
                                                        'one_time' => '🕐 One Time',
                                                        'daily' => '📅 Daily',
                                                        'weekly' => '📆 Weekly',
                                                        'monthly' => '📋 Monthly',
                                                        default => 'Unknown'
                                                    }),
                
                                                Placeholder::make('recipient_count')
                                                    ->label('Recipients')
                                                    ->content(function (callable $get) {
                                                        try {
                                                            switch ($get('message_type')) {
                                                                case 'single':
                                                                    $number = $get('destination_addr');
                                                                    return $number ? "1 recipient ($number)" : '1 recipient';
                                                                
                                                                case 'bulk':
                                                                    if (!$get('excel_file')) return 'Upload a file to see recipient count';
                                                                    
                                                                    $filePath = storage_path('app/public/' . $get('excel_file'));
                                                                    if (!file_exists($filePath)) return 'File not found';
                                                                    
                                                                    $spreadsheet = IOFactory::load($filePath);
                                                                    $worksheet = $spreadsheet->getActiveSheet();
                                                                    $rows = $worksheet->toArray();
                                                                    array_shift($rows); // Remove header
                                                                    
                                                                    $validNumbers = array_filter($rows, function($row) {
                                                                        return !empty($row[0]) && preg_match('/^[0-9+]+$/', trim($row[0]));
                                                                    });
                                                                    
                                                                    return sprintf(
                                                                        '%d valid recipient%s found', 
                                                                        count($validNumbers), 
                                                                        count($validNumbers) === 1 ? '' : 's'
                                                                    );
                                                                
                                                                case 'group':
                                                                    if (!$get('contact_group_id')) return 'Select a group to see recipient count';
                                                                    
                                                                    $count = Contact::where('contact_group_id', $get('contact_group_id'))
                                                                        ->whereNotNull('phone_number')
                                                                        ->where('phone_number', '!=', '')
                                                                        ->count();
                                                                    
                                                                    $groupName = ContactGroup::find($get('contact_group_id'))?->name ?? '';
                                                                    
                                                                    return sprintf(
                                                                        '%d recipient%s in group %s',
                                                                        $count,
                                                                        $count === 1 ? '' : 's',
                                                                        $groupName ? "($groupName)" : ''
                                                                    );
                                                                
                                                                default:
                                                                    return 'Select message type to see recipient count';
                                                            }
                                                        } catch (\Exception $e) {
                                                            return 'Error calculating recipients: ' . $e->getMessage();
                                                        }
                                                    }),
                                            ]),
                
                                        Section::make('Message Content')
                                            ->schema([
                                                Placeholder::make('message_preview')
                                                    ->label('Content')
                                                    ->content(fn (callable $get) => $get('message')),
                
                                                Placeholder::make('message_stats')
                                                    ->label('Statistics')
                                                    ->content(function (callable $get) {
                                                        $message = $get('message');
                                                        if (!$message) return 'No content';
                                                        
                                                        $chars = strlen($message);
                                                        $parts = ceil($chars / 160);
                                                        $remaining = 160 - ($chars % 160);
                                                        
                                                        return "Length: {$chars} characters\nParts: {$parts} SMS\nRemaining: {$remaining} chars in current part";
                                                    }),
                                            ]),
                
                                        Section::make('Schedule Details')
                                            ->schema([
                                                Placeholder::make('next_run')
                                                    ->label('Next Run')
                                                    ->content(function (callable $get) {
                                                        $type = $get('schedule_type');
                                                        $nextRun = $get('next_run_at');
                                                        
                                                        return match($type) {
                                                            'one_time' => "📅 " . ($nextRun ? date('Y-m-d H:i', strtotime($nextRun)) : 'Not set'),
                                                            'daily' => "⏰ Daily at " . ($get('daily_time') ?? 'Time not set'),
                                                            'weekly' => "📅 Weekly on " . (implode(', ', $get('weekly_days') ?? [])),
                                                            'monthly' => "📅 Monthly on days " . (implode(', ', $get('monthly_days') ?? [])),
                                                            default => 'Schedule not configured'
                                                        };
                                                    }),
                
                                                Placeholder::make('status_info')
                                                    ->label('Initial Status')
                                                    ->content('Scheduled (Pending)'),
                                            ]),
                                    ]),
                            ])->columns(1),
                    ]),
                    
            ])
            ->skippable()
            ->persistStepInQueryString()
            ->submitAction(new HtmlString('<button type="submit" class="filament-button filament-button-size-md inline-flex items-center justify-center py-2 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 filament-page-button-action">Schedule Message</button>'))
            ->columnSpanFull()
            ->beforeStateUpdated(function (array $state): array {
                // Ensure schedule_data is properly formatted
                $state['schedule_data'] = [
                    'type' => $state['schedule_type'],
                    'time' => $state['daily_time'] ?? null,
                    'weekly_days' => $state['weekly_days'] ?? [],
                    'monthly_days' => $state['monthly_days'] ?? [],
                ];

                return $state;
            })
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('source_addr')
                    ->label('Sender ID')
                    ->searchable()
                    ->toggleable(),

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

                Tables\Columns\TextColumn::make('message_type')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'single' => 'primary',
                        'bulk' => 'success',
                        'group' => 'warning',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('schedule_type')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'one_time' => 'danger',
                        'daily' => 'warning',
                        'weekly' => 'success',
                        'monthly' => 'info',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('next_run_at')
                    ->label('Next Run')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Last Run')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'gray',
                        'active' => 'success',
                        'paused' => 'warning',
                        'completed' => 'primary',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('message_type')
                    ->options([
                        'single' => 'Single',
                        'bulk' => 'Bulk',
                        'group' => 'Group',
                    ]),

                Tables\Filters\SelectFilter::make('schedule_type')
                    ->options([
                        'one_time' => 'One Time',
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\Filter::make('next_run_at')
                    ->form([
                        Forms\Components\DatePicker::make('scheduled_from'),
                        Forms\Components\DatePicker::make('scheduled_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn($q, $date) => $q->whereDate('next_run_at', '>=', $date)
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn($q, $date) => $q->whereDate('next_run_at', '<=', $date)
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('pause')
                    ->action(fn (ScheduledSms $record) => $record->update(['status' => 'paused']))
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledSms $record) => $record->status === 'active')
                    ->icon('heroicon-o-pause')
                    ->color('warning'),
                Tables\Actions\Action::make('activate')
                    ->action(fn (ScheduledSms $record) => $record->update(['status' => 'active']))
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledSms $record) => $record->status === 'paused')
                    ->icon('heroicon-o-play')
                    ->color('success'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('pauseSelected')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status === 'active') {
                                    $record->update(['status' => 'paused']);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('activateSelected')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status === 'paused') {
                                    $record->update(['status' => 'active']);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledSms::route('/'),
            'create' => Pages\CreateScheduledSms::route('/create'),
            'edit' => Pages\EditScheduledSms::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'active')->count() > 0 ? 'success' : 'gray';
    }

    public static function getModel(): string
    {
        return ScheduledSms::class;
    }
}