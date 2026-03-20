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
    
    // Удаляем активацию для указанного HWID
    $activations = $license['activations'];
    $newActivations = array_filter($activations, function($activation) use ($hwid) {
        return $activation['hwid'] !== $hwid;
    });

    // Если активация найдена, обновляем данные
    if (count($newActivations) < count($activations)) {
        $license['activations'] = $newActivations;
        $license['current_activations'] = count($newActivations);
        
        // Сохраняем обновленную лицензию
        $licenses[$licenseKey] = $license;
        file_put_contents('licenses.json', json_encode($licenses, JSON_PRETTY_PRINT));

        echo json_encode(["success" => true, "message" => "License deactivated"]);
    } else {
        echo json_encode(["success" => false, "message" => "HWID not found"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "License not found"]);
}
?>
