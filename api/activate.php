<?php
// Получаем заголовки запроса
$headers = apache_request_headers();
$apiKey = isset($headers['API-Key']) ? $headers['API-Key'] : null;

if (!$apiKey) {
    echo json_encode(["success" => false, "error" => "API key is missing"]);
    exit;
}

// Проверяем API ключ
$validApiKey = "YOUR_API_KEY_HERE";  // Замените на свой API ключ
if ($apiKey !== $validApiKey) {
    echo json_encode(["success" => false, "error" => "Invalid API key"]);
    exit;
}

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$licenseKey = isset($data['license_key']) ? $data['license_key'] : null;
$hwid = isset($data['hwid']) ? $data['hwid'] : null;

// Загружаем файл с лицензиями
$licenses = json_decode(file_get_contents('licenses.json'), true);

// Проверяем лицензию
if (isset($licenses[$licenseKey])) {
    $license = $licenses[$licenseKey];
    if ($license['current_activations'] < $license['max_activations']) {
        // Добавляем активацию
        $license['activations'][] = [
            'hwid' => $hwid,
            'activated_at' => time(),
            'last_seen' => time()
        ];
        $license['current_activations']++;

        // Сохраняем обновленную лицензию
        $licenses[$licenseKey] = $license;
        file_put_contents('licenses.json', json_encode($licenses, JSON_PRETTY_PRINT));

        echo json_encode(["success" => true, "message" => "License activated"]);
    } else {
        echo json_encode(["success" => false, "message" => "Activation limit reached"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "License not found"]);
}
?>
