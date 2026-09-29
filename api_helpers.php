<?php
function getDeliveryInfo(
    float $customerLongitude,
    float $customerLatitude,
    string $apiKey,
    float $pharmacyLongitude,
    float $pharmacyLatitude
): array {
    if ($apiKey === '' || $apiKey === '') {
        return ['ok' => false, 'message' => 'API key is not configured.'];
    }

    if ($customerLatitude < -90 || $customerLatitude > 90 ||
        $customerLongitude < -180 || $customerLongitude > 180) {
        return ['ok' => false, 'message' => 'Invalid customer coordinates.'];
    }
    $url = 'https://api.openrouteservice.org/v2/directions/driving-car';
    $payload = json_encode([
        'coordinates' => [
            [$pharmacyLongitude, $pharmacyLatitude],
            [$customerLongitude, $customerLatitude]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        return ['ok' => false,
            'message' => 'Delivery API connection failed: ' . $curlError];
    }
    $json = json_decode($response, true);
    if ($httpCode !== 200) {
        $apiMessage = $json['error']['message'] ?? 'HTTP ' . $httpCode;
        return ['ok' => false,
            'message' => 'Delivery API error: ' . $apiMessage];
    }
    $summary = $json['routes'][0]['summary'] ?? null;
    if (!is_array($summary) ||
        !isset($summary['distance'], $summary['duration'])) {
        return ['ok' => false,
            'message' => 'Delivery distance is unavailable.'];
    }
    return [
        'ok' => true,
        'distance_km' => round($summary['distance'] / 1000, 2),
        'duration_min' => round($summary['duration'] / 60, 1)
    ];
}

function getQrCode(string $qrData): array {
    $url = 'https://api.qrserver.com/v1/create-qr-code/'
        . '?size=120x120&format=png&data=' . rawurlencode($qrData);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10
    ]);
    $image = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($image === false) {
        return ['ok' => false,
            'message' => 'QR API connection failed: ' . $curlError];
    }
    if ($httpCode !== 200 || strpos($contentType, 'image/') !== 0 ||
        $image === '') {
        return ['ok' => false, 'message' => 'QR code is unavailable.'];
    }
    return [
        'ok' => true,
        'data_uri' => 'data:image/png;base64,' . base64_encode($image)
    ];
}
