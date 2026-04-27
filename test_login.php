<?php
$data = json_encode(['username' => 'admin', 'password' => 'admin123', 'role' => 'admin']);
$ch = curl_init('http://127.0.0.1:8000/api/auth/login');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json', 'Content-Length: ' . strlen($data)]);
$result = curl_exec($ch);
echo 'HTTP CODE: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo 'RESPONSE: ' . $result . "\n";
