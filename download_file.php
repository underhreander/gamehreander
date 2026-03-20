<?php
/* ============================================
   SOFTMASTER — FILE DOWNLOAD PROXY
   Validates file existence before delivery.
   ============================================ */
session_start();
require_once 'admin/db_connect.php';

$download_link = get_download_link();

/* ---------- no link configured ---------- */
if (empty($download_link)) {
    show_error('Download link is not configured.', 'The administrator has not set a download file yet.');
    exit;
}

$is_remote = (strpos($download_link, 'http') === 0);

/* ---------- REMOTE FILE ---------- */
if ($is_remote) {
    $ch = curl_init($download_link);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($code < 200 || $code >= 400) {
        show_error("File not found (HTTP {$code}).", 'The remote file could not be reached. Please try again later.');
        exit;
    }
    if ($type && stripos($type, 'text/html') !== false) {
        show_error('Invalid file.', 'The hosting returned an HTML page instead of a binary file.');
        exit;
    }

    /* Redirect to real file */
    header('Location: ' . $download_link);
    exit;
}

/* ---------- LOCAL FILE ---------- */
$local_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($download_link, '/');

if (!file_exists($local_path) || !is_file($local_path)) {
    show_error('File not found.', 'The file does not exist on the server: <code>' . htmlspecialchars($download_link) . '</code>');
    exit;
}

/* Check it's not an HTML placeholder */
$mime = mime_content_type($local_path);
if ($mime === 'text/html' || $mime === 'text/plain') {
    $head = file_get_contents($local_path, false, null, 0, 512);
    if (stripos($head, '<html') !== false || stripos($head, '<!doctype') !== false) {
        show_error('Invalid file.', 'The file appears to be an HTML placeholder, not a real binary.');
        exit;
    }
}

$size     = filesize($local_path);
$filename = basename($local_path);

/* Deliver file */
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $size);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
readfile($local_path);
exit;

/* ============================================
   ERROR PAGE RENDERER
   ============================================ */
function show_error($title, $details = '') {
    $site_name = 'SoftMaster';
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Download Error — <?= $site_name ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{font-family:'Inter',sans-serif;background:#0a0a1a;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;}
            .error-box{background:rgba(30,30,46,0.9);border:1px solid #2d2d3d;border-radius:16px;padding:48px;max-width:500px;text-align:center;backdrop-filter:blur(20px);}
            .error-icon{font-size:3rem;color:#e74c3c;margin-bottom:20px;}
            .error-title{font-size:1.4rem;font-weight:700;margin-bottom:12px;}
            .error-details{font-size:0.95rem;color:#aaa;margin-bottom:24px;line-height:1.6;}
            .error-details code{background:rgba(255,255,255,0.06);padding:2px 8px;border-radius:4px;font-size:0.85rem;}
            .error-btn{display:inline-block;padding:12px 28px;background:#6c5ce7;color:#fff;text-decoration:none;border-radius:10px;font-weight:600;transition:background 0.2s;}
            .error-btn:hover{background:#7c6cf7;}
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h1 class="error-title"><?= htmlspecialchars($title) ?></h1>
            <?php if ($details): ?>
                <p class="error-details"><?= $details ?></p>
            <?php endif; ?>
            <a href="/" class="error-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </body>
    </html>
    <?php
}
?>