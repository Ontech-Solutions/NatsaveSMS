<?php

// Create an SMS
$send = \App\Models\Send::create([
    'source_addr' => 'NATSAVE',
    'destination_addr' => '+260977123456',
    'message' => 'Test message ' . date('Y-m-d H:i:s'),
    'message_type' => 'single',
    'sms_type' => 'single',
    'status' => 'pending'
]);

echo "Created Send record: {$send->id}\n";

// Simulate Python script processing
$sent = \App\Models\Sent::create([
    'message_id' => 'MSG-' . \Illuminate\Support\Str::random(12),
    'source_addr' => $send->source_addr,
    'destination_addr' => $send->destination_addr,
    'message' => $send->message,
    'sms_type' => $send->sms_type,
    'status' => 'submitted',
    'sent_at' => now(),
    'submitted_date' => now(),
]);

echo "Created Sent record: {$sent->message_id}\n";

// Simulate SMSC delivery report
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/dlr");  // Adjust URL as needed
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'message_id' => $sent->message_id,
    'status' => 'DELIVRD',
    'received_at' => now()->toDateTimeString()
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "DLR Response (HTTP $httpCode): $response\n";

// Verify final status
$finalStatus = \App\Models\Sent::where('message_id', $sent->message_id)
    ->first()
    ->status;

echo "Final message status: $finalStatus\n";

// Show full message details
echo "\nFinal message details:\n";
print_r(\App\Models\Sent::where('message_id', $sent->message_id)->first()->toArray());