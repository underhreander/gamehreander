<!DOCTYPE html>
<html>
<head>
    <title>Генератор пароля для админ-панели</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        input[type="password"] { width: 100%; padding: 12px; font-size: 16px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; width: 100%; margin-top: 10px; }
        button:hover { background: #005a87; }
        .hash { background: #f8f9fa; padding: 20px; border-radius: 5px; word-break: break-all; font-family: monospace; font-size: 14px; margin-top: 20px; border-left: 4px solid #007cba; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Генератор Bcrypt пароля для админки</h1>
        
        <div class="warning">
            <strong>⚠️ ВАЖНО:</strong> Скопируй готовый хеш и вставь его в базу данных в поле password. 
            Этот хеш используется только для проверки пароля при логине!
        </div>
        
        <form method="POST">
            <label>Введите пароль администратора:</label>
            <input type="password" name="password" placeholder="MyStrongAdminPass123" required maxlength="72">
            <button type="submit">Сгенерировать хеш ($2y$10$...)</button>
        </form>
        
        <?php if ($_POST['password'] ?? false): ?>
            <?php
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            ?>
            
            <div class="hash">
                <strong>Готовый хеш для БД:</strong><br>
                <code><?php echo htmlspecialchars($hash); ?></code>
            </div>
            
            <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px; border-left: 4px solid #28a745;">
                <strong>✅ Для проверки в коде используй:</strong><br>
                <code>if (password_verify($input_password, '<?php echo addslashes($hash); ?>')) { login(); }</code>
            </div>
            
            <details style="margin-top: 20px;">
                <summary>Показать PHP код (для понимания)</summary>
                <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
$hash = password_hash('<?php echo htmlspecialchars($password); ?>', PASSWORD_BCRYPT);
echo $hash; // <?php echo htmlspecialchars($hash); ?>
                </pre>
            </details>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        <small>Формат хеша: <code>$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi</code></small>
    </div>
</body>
</html>
