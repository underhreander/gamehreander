Copy<?php
session_start();
require_once 'admin/db_connect.php';

// Логирование посещения
log_visit();

// Получение ссылки для скачивания из базы данных
$download_link = get_download_link();

// ─── Проверка существования файла ───
$file_exists = false;
$file_error = '';

if (!empty($download_link)) {
    // Если это локальный путь (не http/https)
    if (strpos($download_link, 'http') !== 0) {
        $local_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($download_link, '/');
        if (file_exists($local_path) && is_file($local_path)) {
            $file_exists = true;
        } else {
            $file_error = 'The download file is temporarily unavailable. Please try again later or contact support.';
        }
    } else {
        // Если это внешняя ссылка — проверяем доступность через HEAD-запрос
        $ch = curl_init($download_link);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 400) {
            $file_exists = true;
        } else {
            $file_error = 'The download file is temporarily unavailable (HTTP ' . $http_code . '). Please try again later or contact support.';
        }
    }
} else {
    $file_error = 'Download link is not configured. Please contact the administrator.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download — SoftMaster Launcher</title>
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ─── Стили для страницы download.php ─── */
        .download-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 60px;
            position: relative;
            z-index: 1;
        }

        .download-page .container {
            max-width: 960px;
            width: 100%;
        }

        .download-page h1 {
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 12px;
        }

        .download-page > .container > p {
            text-align: center;
            color: var(--text-secondary);
            font-size: 1.05rem;
            margin-bottom: 40px;
        }

        /* Шаги */
        .download-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0;
            margin-bottom: 48px;
        }

        .download-steps .step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 12px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            cursor: default;
            opacity: 0.5;
        }

        .download-steps .step.active {
            opacity: 1;
            border-color: var(--accent);
            background: rgba(99, 102, 241, 0.08);
        }

        .download-steps .step.completed {
            opacity: 1;
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.08);
        }

        .download-steps .step span {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .download-steps .step.completed span {
            background: #10b981;
        }

        .download-steps .step p {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
        }

        .step-connector {
            width: 60px;
            height: 2px;
            background: var(--border-color);
            flex-shrink: 0;
        }

        .step-connector.active {
            background: var(--accent);
        }

        /* Карточка контента */
        .download-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 52px 60px;
            backdrop-filter: blur(20px);
        }

        /* Шаг 1: Генерация */
        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            text-align: center;
        }

        .step-content .subtitle {
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 32px;
            font-size: 0.95rem;
        }

        /* Форма */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .generate-btn {
            width: 100%;
            padding: 16px;
            background: var(--gradient-accent);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        }

        /* Шаг 2: Код и скачивание */
        .license-display {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
            position: relative;
        }

        .license-display .label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .license-display .code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: 2px;
        }

        .license-display .copy-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: var(--transition);
        }

        .license-display .copy-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .download-btn-main {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .download-btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }

        .download-btn-main.disabled {
            background: #4b5563;
            cursor: not-allowed;
            opacity: 0.6;
            pointer-events: none;
        }

        .file-meta {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 16px;
        }

        .file-meta span {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .file-meta span i {
            margin-right: 4px;
            color: var(--text-secondary);
        }

        /* Шаг 3: Инструкция */
        .install-steps {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .install-steps li {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .install-steps li:last-child {
            border-bottom: none;
        }

        .install-steps li .num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.1);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .install-steps li div p {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .install-steps li div small {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .install-password {
            display: inline-block;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 2px 10px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent);
            font-weight: 500;
        }

        /* Ошибка файла */
        .file-error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .file-error i {
            font-size: 2rem;
            color: #ef4444;
            margin-bottom: 10px;
            display: block;
        }

        .file-error p {
            color: #fca5a5;
            font-size: 0.95rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .download-card {
                padding: 32px 24px;
            }

            .download-steps .step p {
                display: none;
            }

            .step-connector {
                width: 30px;
            }

            .license-display .code {
                font-size: 1.1rem;
            }

            .file-meta {
                flex-direction: column;
                gap: 8px;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- ═══ HEADER ═══ -->
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <span class="logo-text">Soft<span class="logo-accent">Master</span></span>
            </a>
            <nav class="nav">
                <ul>
                    <li><a href="index.php#features">Features</a></li>
                    <li><a href="index.php#software">Software</a></li>
                    <li><a href="download.php" class="active">Download</a></li>
                    <li><a href="index.php#support">Support</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- ═══ PARTICLE CANVAS ═══ -->
    <canvas id="particleCanvas"></canvas>

    <!-- ═══ DOWNLOAD PAGE ═══ -->
    <section class="download-page">
        <div class="container">
            <h1>Download SoftMaster Launcher</h1>
            <p>Complete the steps below to get your license key and download the launcher</p>

            <!-- Шаги -->
            <div class="download-steps">
                <div class="step active" id="stepIndicator1">
                    <span>1</span>
                    <p>License Key</p>
                </div>
                <div class="step-connector" id="connector1"></div>
                <div class="step" id="stepIndicator2">
                    <span>2</span>
                    <p>Download</p>
                </div>
                <div class="step-connector" id="connector2"></div>
                <div class="step" id="stepIndicator3">
                    <span>3</span>
                    <p>Install</p>
                </div>
            </div>

            <!-- Карточка -->
            <div class="download-card">

                <!-- ШАГ 1: Генерация ключа -->
                <div class="step-content active" id="stepContent1">
                    <h2><i class="fas fa-key"></i> Generate License Key</h2>
                    <p class="subtitle">Enter your email to receive a 7-day trial license key</p>
                    <form id="trialForm">
                        <div class="form-group">
                            <input type="email" id="userEmail" placeholder="Enter your business email" required>
                        </div>
                        <button type="submit" class="generate-btn">
                            <i class="fas fa-bolt"></i> Generate License Key
                        </button>
                    </form>
                </div>

 <!-- ШАГ 2: Скачивание -->
                <div class="step-content" id="stepContent2">
                    <h2><i class="fas fa-download"></i> Download Launcher</h2>
                    <p class="subtitle">Your license key has been generated — save it and download the launcher</p>

                    <div class="license-display">
                        <div class="label">Your License Key</div>
                        <div class="code" id="trialCode">XXXX-XXXX-XXXX-XXXX</div>
                        <button class="copy-btn" id="copyBtn" title="Copy to clipboard">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>

                    <!-- Ссылка теперь ведёт на download_file.php, а не напрямую на файл -->
                    <a href="download_file.php" class="download-btn-main" id="downloadBtn">
                        <i class="fas fa-download"></i> Download SoftMaster Launcher
                    </a>
                    <div class="file-meta">
                        <span><i class="fas fa-windows"></i> Windows 10/11</span>
                        <span><i class="fas fa-hdd"></i> 45 MB</span>
                        <span><i class="fas fa-shield-alt"></i> Secure & Verified</span>
                    </div>
                </div>

                    <?php if ($file_exists): ?>
                        <a href="<?php echo htmlspecialchars($download_link); ?>" class="download-btn-main" id="downloadBtn" download>
                            <i class="fas fa-download"></i> Download SoftMaster Launcher
                        </a>
                        <div class="file-meta">
                            <span><i class="fas fa-windows"></i> Windows 10/11</span>
                            <span><i class="fas fa-hdd"></i> 45 MB</span>
                            <span><i class="fas fa-shield-alt"></i> Secure & Verified</span>
                        </div>
                    <?php else: ?>
                        <div class="file-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p><?php echo htmlspecialchars($file_error); ?></p>
                        </div>
                        <button class="download-btn-main disabled" disabled>
                            <i class="fas fa-download"></i> Download Unavailable
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ШАГ 3: Установка -->
                <div class="step-content" id="stepContent3">
                    <h2><i class="fas fa-check-circle"></i> Install & Get Started</h2>
                    <p class="subtitle">Follow these steps to complete the installation</p>

                    <ul class="install-steps">
                        <li>
                            <div class="num">1</div>
                            <div>
                                <p>Run the installer</p>
                                <small>Open the downloaded <strong>SoftMasterLauncher.exe</strong> file</small>
                            </div>
                        </li>
                        <li>
                            <div class="num">2</div>
                            <div>
                                <p>Enter the archive password</p>
                                <small>Password: <span class="install-password">2025</span></small>
                            </div>
                        </li>
                        <li>
                            <div class="num">3</div>
                            <div>
                                <p>Activate with your license key</p>
                                <small>Paste the license key you received in Step 1 into the launcher</small>
                            </div>
                        </li>
                        <li>
                            <div class="num">4</div>
                            <div>
                                <p>You're all set!</p>
                                <small>Enjoy all enterprise software tools for 7 days free</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ FOOTER ═══ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> SoftMaster. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileExists = <?php echo $file_exists ? 'true' : 'false'; ?>;

        // ─── Переключение шагов ───
        function goToStep(num) {
            // Контент
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            const target = document.getElementById('stepContent' + num);
            if (target) target.classList.add('active');

            // Индикаторы
            for (let i = 1; i <= 3; i++) {
                const indicator = document.getElementById('stepIndicator' + i);
                const connector = document.getElementById('connector' + (i - 1));
                indicator.classList.remove('active', 'completed');
                if (connector) connector.classList.remove('active');

                if (i < num) {
                    indicator.classList.add('completed');
                    if (connector) connector.classList.add('active');
                } else if (i === num) {
                    indicator.classList.add('active');
                    if (connector) connector.classList.add('active');
                }
            }
        }

        // ─── Генерация ключа ───
        function generateCode() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let code = '';
            for (let i = 0; i < 16; i++) {
                if (i > 0 && i % 4 === 0) code += '-';
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return code;
        }

        // ─── Форма ───
        const form = document.getElementById('trialForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const email = document.getElementById('userEmail').value;
                const code = generateCode();

                document.getElementById('trialCode').textContent = code;

                // Логирование
                fetch('admin/api/log_download.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: code, email: email })
                }).then(r => r.json()).then(d => console.log('Logged:', d)).catch(console.error);

                goToStep(2);
            });
        }

        // ─── Копирование ключа ───
        const copyBtn = document.getElementById('copyBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                const code = document.getElementById('trialCode').textContent;
                navigator.clipboard.writeText(code).then(() => {
                    copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    }, 2000);
                });
            });
        }

        // ─── Кнопка скачивания → переход на шаг 3 ───
        const dlBtn = document.getElementById('downloadBtn');
        if (dlBtn) {
            dlBtn.addEventListener('click', function() {
                setTimeout(() => goToStep(3), 500);
            });
        }

        // ─── Particle Canvas (фон) ───
        const canvas = document.getElementById('particleCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let particles = [];

            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            resize();
            window.addEventListener('resize', resize);

            for (let i = 0; i < 50; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 2 + 0.5,
                    dx: (Math.random() - 0.5) * 0.4,
                    dy: (Math.random() - 0.5) * 0.4,
                    o: Math.random() * 0.3 + 0.1
                });
            }

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => {
                    p.x += p.dx;
                    p.y += p.dy;
                    if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
                    if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(99,102,241,${p.o})`;
                    ctx.fill();
                });
                requestAnimationFrame(animate);
            }
            animate();
        }
    });
    </script>
</body>
</html>   