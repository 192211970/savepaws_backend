<?php

function getGoogleAccessToken($KEY_FILE_PATH) {
    if (!file_exists($KEY_FILE_PATH)) {
        error_log("FCM Error: Service Account JSON not found at " . $KEY_FILE_PATH);
        return false;
    }

    $client_email = '';
    $private_key = '';
    $data = json_decode(file_get_contents($KEY_FILE_PATH), true);

    if (isset($data['client_email'])) $client_email = $data['client_email'];
    if (isset($data['private_key'])) $private_key = $data['private_key'];

    if (empty($client_email) || empty($private_key)) {
        error_log("FCM Error: Invalid Service Account JSON");
        return false;
    }

    // Header
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $header_base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Claim set
    $now = time();
    $claims = json_encode([
        'iss' => $client_email,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);
    $claims_base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claims));

    // Sign
    $signature = '';
    openssl_sign($header_base64 . '.' . $claims_base64, $signature, $private_key, 'SHA256');
    $signature_base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $header_base64 . '.' . $claims_base64 . '.' . $signature_base64;

    // Exchange JWT for Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    if (isset($json['access_token'])) {
        return $json['access_token'];
    }

    error_log("FCM Error: Failed to get access token. Response: " . $response);
    return false;
}

function sendNotification($fcm_token, $title, $body) {
    // 1. PATH TO YOUR SERVICE ACCOUNT JSON
    // Make sure to upload this file to the same directory
    $KEY_FILE_PATH = __DIR__ . '/service-account.json';

    // 2. GET ACCESS TOKEN
    $access_token = getGoogleAccessToken($KEY_FILE_PATH);
    if (!$access_token) {
        return false;
    }

    // 3. READ PROJECT ID
    $data = json_decode(file_get_contents($KEY_FILE_PATH), true);
    $project_id = $data['project_id'];

    // 4. API URL
    $url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

    // 5. PAYLOAD (FCM v1 Format)
    $payload = [
        'message' => [
            'token' => $fcm_token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => [
                'title' => $title,
                'body' => $body,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
            'android' => [
                'priority' => 'high'
            ]
        ]
    ];

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $result = curl_exec($ch);

    if ($result === FALSE) {
        error_log('FCM Send Error: ' . curl_error($ch));
    }

    curl_close($ch);
    return $result;
}
?>
