<?php

namespace App\Filament\Resources\SendResource\Pages;

use App\Filament\Resources\SendResource;
use App\Models\Send;
use Filament\Actions;
use Filament\Actions\Action;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Filament\Notifications\Actions\Action as NotificationAction;

class CreateSend extends CreateRecord
{
    protected static string $resource = SendResource::class;

    public function mount(): void
    {
        parent::mount();
    }

    

    protected function handleRecordCreation(array $data): Send
    {
        try {
            DB::beginTransaction();

            // Process based on message type
            $records = match ($data['message_type']) {
                'bulk' => $this->createBulkMessages($data),
                'group' => $this->createGroupMessages($data),
                default => [$this->createSingleMessage($data)],
            };

            if (empty($records)) {
                throw new \Exception('No messages were created.');
            }

            DB::commit();

            // Return the first record
            return $records[0];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function createBulkMessages(array $data): array
    {
        if (empty($data['excel_file'])) {
            throw new \Exception('No file was uploaded.');
        }

        $phoneNumbers = $this->processExcelFile($data['excel_file']);
        return $this->createIndividualMessages($data, $phoneNumbers);
    }

    protected function createGroupMessages(array $data): array
    {
        if (empty($data['contact_group_id'])) {
            throw new \Exception('No contact group was selected.');
        }

        $phoneNumbers = $this->processContactGroup($data['contact_group_id']);
        return $this->createIndividualMessages($data, $phoneNumbers);
    }

    protected function createIndividualMessages(array $data, array $phoneNumbers): array
    {
        $records = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $messageData = [
                'internal_message_id' => 'MSG-' . Str::random(12),
                'message_id' => null,
                'source_addr' => $data['source_addr'],
                'destination_addr' => $phoneNumber,
                'message' => $data['message'],
                'message_type' => $data['message_type'],
                'recipient_count' => 1,
                'status' => 'pending',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'service_type' => $data['service_type'] ?? 'SMS',
                'data_coding' => $data['data_coding'] ?? 0,
                'registered_delivery' => $data['registered_delivery'] ?? 1,
                'priority_flag' => $data['priority_flag'] ?? 0,
                'contact_group_id' => $data['contact_group_id'] ?? null,
                'excel_file' => $data['excel_file'] ?? null,
            ];

            $records[] = Send::create($messageData);
        }

        return $records;
    }

    protected function createSingleMessage(array $data): Send
    {
        if (empty($data['destination_addr'])) {
            throw new \Exception('No recipient number was provided.');
        }

        return Send::create([
            'message_id' => null,
            'internal_message_id' => 'MSG-' . Str::random(12),
            'source_addr' => $data['source_addr'],
            'destination_addr' => $data['destination_addr'],
            'message' => $data['message'],
            'message_type' => $data['message_type'],
            'recipient_count' => 1,
            'status' => 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'service_type' => $data['service_type'] ?? 'SMS',
            'data_coding' => $data['data_coding'] ?? 0,
            'registered_delivery' => $data['registered_delivery'] ?? 1,
            'priority_flag' => $data['priority_flag'] ?? 0,
        ]);
    }

    protected function processExcelFile(string $filePath): array
    {
        try {
            $filePath = storage_path('app/public/' . $filePath);
            
            if (!file_exists($filePath)) {
                throw new \Exception('The uploaded file could not be found.');
            }

            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row if exists
            array_shift($rows);

            // Extract and clean phone numbers
            $phoneNumbers = array_filter(array_map(function($row) {
                $number = trim($row[0] ?? '');
                
                if (empty($number)) {
                    return null;
                }

                // Add country code if not present
                if (!str_starts_with($number, '+')) {
                    $number = '+260' . ltrim($number, '0');
                }

                return $number;
            }, $rows));

            if (empty($phoneNumbers)) {
                throw new \Exception('No valid phone numbers found in the uploaded file.');
            }

            return array_values($phoneNumbers);

        } catch (\Exception $e) {
            throw new \Exception('Error processing Excel file: ' . $e->getMessage());
        }
    }

    protected function processContactGroup(int $groupId): array
    {
        try {
            $phoneNumbers = \App\Models\Contact::where('contact_group_id', $groupId)
                ->pluck('phone_number')
                ->filter()
                ->map(function($number) {
                    if (!str_starts_with($number, '+')) {
                        return '+260' . ltrim($number, '0');
                    }
                    return $number;
                })
                ->toArray();

            if (empty($phoneNumbers)) {
                throw new \Exception('The selected contact group has no valid contacts.');
            }

            return $phoneNumbers;

        } catch (\Exception $e) {
            throw new \Exception('Error processing contact group: ' . $e->getMessage());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $type = $this->record->message_type;
        $count = $type === 'single' ? 1 : Send::where('message_type', $type)
            ->where('created_at', $this->record->created_at)
            ->count();

        return Notification::make()
            ->success()
            ->title('Messages Created Successfully')
            ->body(
                $type === 'single' 
                    ? 'Your message has been queued for sending.'
                    : "{$count} individual messages have been queued for sending."
            )
            ->actions([
                NotificationAction::make('view')
                    ->label('View Messages')
                    ->url($this->getResource()::getUrl('index'))
                    ->button(),
            ]);
    }
}
