<?php
// Получаем заголовки запроса
$headers = apache_request_headers();
$apiKey = isset($headers['API-Key']) ? $headers['API-Key'] : null;

if (!$apiKey) {
    echo json_encode(["success" => false, "error" => "API key is missing"]);
    exit;
}

// Проверяем API ключ (здесь используйте ваш реальный API ключ)
$validApiKey = "YOUR_API_KEY_HERE";  // Замените на свой API ключ
if ($apiKey !== $validApiKey) {
    echo json_encode(["success" => false, "error" => "Invalid API key"]);
    exit;
}

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$licenseKey = isset($data['license_key']) ? $data['license_key'] : null;

// Проверка лицензии (пример)
$licenses = json_decode(file_get_contents('licenses.json'), true);

if (isset($licenses[$licenseKey])) {
    $license = $licenses[$licenseKey];
    // Пример проверки
    if ($license['status'] === 'active') {
        echo json_encode(["success" => true, "valid" => true]);
    } else {
        echo json_encode(["success" => false, "valid" => false]);
    }
} else {
    echo json_encode(["success" => false, "error" => "License not found"]);
}
?>