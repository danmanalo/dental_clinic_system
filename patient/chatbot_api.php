<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = trim($_POST['message'] ?? '');

    if (empty($userMessage)) {
        echo "Please enter a message.";
        exit;
    }

    $apiKey = 'validapikey'; // Make sure it's valid

    $postData = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful dental assistant for Tooth Talks Dental Clinic. Answer questions clearly and professionally.'],
            ['role' => 'user', 'content' => $userMessage],
        ]
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo "cURL error: " . curl_error($ch);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    // Decode and check response
    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        echo $result['choices'][0]['message']['content'];
    } else {
        // Debug output for developers
        echo "API Error. Full response:<br><pre>" . print_r($result, true) . "</pre>";
    }
}
?>
