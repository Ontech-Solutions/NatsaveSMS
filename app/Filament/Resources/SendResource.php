<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SendResource\Pages;
use App\Models\Send;
use App\Models\SenderId;
use App\Models\ContactGroup;
use App\Models\Contact;
use App\Models\SenderStatus;
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
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Support\Colors\Color;

class SendResource extends Resource
{
    protected static ?string $model = Send::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'SMS Management';
    protected static ?string $modelLabel = 'Message';
    protected static ?string $pluralModelLabel = 'Send Message';
    protected static ?int $navigationSort = 2;

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

                Wizard\Step::make('Preview & Confirm')
                    ->description('Review your message before sending')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Section::make('Message Summary')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make()
                                            ->heading('Message Details')
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

                                                Placeholder::make('recipient_count')
                                                    ->label('Recipients')
                                                    ->content(function (callable $get) {
                                                        try {
                                                            switch ($get('message_type')) {
                                                                case 'single':
                                                                    $number = $get('destination_addr');
                                                                    return $number ? "1 recipient ($number)" : '1 recipient';
                                                                
                                                                case 'bulk':
                                                                    if (!$get('excel_file')) {
                                                                        return 'Upload a file to see recipient count';
                                                                    }
                                                                    
                                                                    $filePath = storage_path('app/public/' . $get('excel_file'));
                                                                    if (!file_exists($filePath)) {
                                                                        return 'File not found';
                                                                    }
                                                                    
                                                                    $spreadsheet = IOFactory::load($filePath);
                                                                    $worksheet = $spreadsheet->getActiveSheet();
                                                                    $rows = $worksheet->toArray();
                                                                    array_shift($rows); // Remove header
                                                                    
                                                                    $validNumbers = array_filter($rows, function($row) {
                                                                        return !empty($row[0]) && preg_match('/^[0-9+]+$/', trim($row[0]));
                                                                    });
                                                                    
                                                                    $count = count($validNumbers);
                                                                    return sprintf('%d valid recipient%s found', 
                                                                        $count, 
                                                                        $count === 1 ? '' : 's'
                                                                    );
                                                                
                                                                case 'group':
                                                                    if (!$get('contact_group_id')) {
                                                                        return 'Select a group to see recipient count';
                                                                    }
                                                                    
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

                                        Section::make()
                                            ->heading('Message Content')
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

                                        Section::make()
                                            ->heading('Delivery Information')
                                            ->schema([
                                                Placeholder::make('delivery_type')
                                                    ->label('Delivery')
                                                    ->content('Immediate delivery'),

                                                Placeholder::make('encoding')
                                                    ->label('Encoding')
                                                    ->content('GSM 7-bit'),
                                            ]),
                                    ]),
                            ])->columns(1),
                    ]),
            ])
            ->skippable()
            ->persistStepInQueryString()
            ->submitAction(new HtmlString('<button type="submit" class="filament-button filament-button-size-md inline-flex items-center justify-center py-2 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 filament-page-button-action">Send Message</button>'))
            ->columnSpanFull()
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

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'gray',
                        'submitted' => 'warning',
                        'delivered' => 'success',
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

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'submitted' => 'Submitted',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn($q, $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn($q, $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSends::route('/'),
            'create' => Pages\CreateSend::route('/create'),
            'edit' => Pages\EditSend::route('/{record}/edit'),
        ];
    }
}
