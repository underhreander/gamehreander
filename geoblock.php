<?php
/**
 * Геоблок для СНГ стран
 * Подключите в начале index.php и pricing.php:
 * require_once __DIR__ . '/geoblock.php';
 */

class GeoBlock {

    private static $cis_countries = [
        'RU','BY','KZ','KG','TJ','UZ','AM','AZ','MD','TM','UA','GE'
    ];

    // Папка для кэша (создастся автоматически)
    private static $cache_dir = __DIR__ . '/cache/geo/';
    private static $cache_ttl = 86400; // 24 часа

    /**
     * Получить IP адрес пользователя
     */
    private static function getUserIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $key) {
            if (!empty($_SERVER[$key])) {
                // Берём первый IP из списка (может быть через запятую)
                $ips = explode(',', $_SERVER[$key]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Проверить кэш для IP
     */
    private static function getCache($ip) {
        $file = self::$cache_dir . md5($ip) . '.txt';
        if (file_exists($file) && (time() - filemtime($file)) < self::$cache_ttl) {
            return trim(file_get_contents($file));
        }
        return null;
    }

    /**
     * Сохранить в кэш
     */
    private static function setCache($ip, $country) {
        if (!is_dir(self::$cache_dir)) {
            @mkdir(self::$cache_dir, 0755, true);
        }
        $file = self::$cache_dir . md5($ip) . '.txt';
        @file_put_contents($file, $country);
    }

    /**
     * Выполнить запрос к API
     */
    private static function apiRequest($url, $timeout = 4) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; GeoCheck/2.0)',
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_errno($ch);
        curl_close($ch);

        if ($err === 0 && $code === 200 && $resp) {
            return $resp;
        }
        return null;
    }

    /**
     * Определить страну по IP через несколько API
     */
    private static function getCountryByIP($ip) {
        // 1. ip-api.com
        $resp = self::apiRequest("http://ip-api.com/json/{$ip}?fields=status,countryCode");
        if ($resp) {
            $data = json_decode($resp, true);
            if ($data && ($data['status'] ?? '') === 'success' && !empty($data['countryCode'])) {
                return strtoupper($data['countryCode']);
            }
        }

        // 2. ipapi.co
        $resp = self::apiRequest("https://ipapi.co/{$ip}/json/");
        if ($resp) {
            $data = json_decode($resp, true);
            if ($data && !empty($data['country_code']) && empty($data['error'])) {
                return strtoupper($data['country_code']);
            }
        }

        // 3. ipwhois.app
        $resp = self::apiRequest("https://ipwhois.app/json/{$ip}");
        if ($resp) {
            $data = json_decode($resp, true);
            if ($data && ($data['success'] ?? true) !== false && !empty($data['country_code'])) {
                return strtoupper($data['country_code']);
            }
        }

        // 4. ipinfo.io
        $resp = self::apiRequest("https://ipinfo.io/{$ip}/country");
        if ($resp) {
            $code = trim($resp);
            if (strlen($code) === 2 && ctype_alpha($code)) {
                return strtoupper($code);
            }
        }

        // 5. Все API упали — возвращаем null
        return null;
    }

    /**
     * Проверить, нужно ли блокировать
     */
    public static function shouldBlock() {
    $ip = self::getUserIP();

    // Пропускаем поисковых и соцсеть-ботов
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bots = [
        'googlebot', 'bingbot', 'yandex', 'facebookexternalhit',
        'telegrambot', 'twitterbot', 'discordbot', 'linkedinbot',
        'slackbot', 'whatsapp', 'pinterest', 'applebot',
        'semrushbot', 'ahrefsbot', 'mj12bot', 'duckduckbot',
        'bytespider', 'crawl', 'spider', 'bot'
    ];
    foreach ($bots as $bot) {
        if (strpos($ua, $bot) !== false) {
            return false;
        }
    }
        // Локальные IP — не блокируем
        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // Проверяем кэш
        $country = self::getCache($ip);

        if ($country === null) {
            // Нет кэша — запрашиваем API
            $country = self::getCountryByIP($ip);

            if ($country === null) {
                // ВСЕ API не ответили — блокируем на всякий случай
                // (лучше заблокировать лишнего, чем пропустить)
                return true;
            }

            // Сохраняем в кэш
            self::setCache($ip, $country);
        }

        // Специальная метка — если кэширован "ALLOW"
        if ($country === 'ALLOW') {
            return false;
        }

        $blocked = in_array($country, self::$cis_countries);

        // Если не заблокирован — кэшируем как ALLOW чтобы не дёргать API
        if (!$blocked && self::getCache($ip) === null) {
            self::setCache($ip, $country);
        }

        return $blocked;
    }

        /**
     * Страница блокировки (404 с анимацией и мини-игрой)
     */
    public static function showBlockPage() {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 — Page Not Found</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
            <style>
                :root {
                    --bg: #0a0e1a;
                    --card: rgba(17, 24, 39, 0.85);
                    --primary: #2563eb;
                    --danger: #ef4444;
                    --text: #f1f5f9;
                    --text-sec: #94a3b8;
                    --text-muted: #64748b;
                    --border: rgba(148, 163, 184, 0.08);
                    --font: 'Inter', -apple-system, sans-serif;
                }
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: var(--font);
                    background: var(--bg);
                    color: var(--text);
                    min-height: 100vh;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    overflow-x: hidden;
                    -webkit-font-smoothing: antialiased;
                }

                /* BG effects */
                .bg-grid {
                    position: fixed; inset: 0;
                    background-image:
                        linear-gradient(rgba(37,99,235,0.03) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(37,99,235,0.03) 1px, transparent 1px);
                    background-size: 60px 60px;
                    z-index: 0;
                }
                canvas#particles {
                    position: fixed; inset: 0; z-index: 1; pointer-events: none;
                }

                /* Main container */
                .page-wrap {
                    position: relative; z-index: 10;
                    display: flex; flex-direction: column;
                    align-items: center; gap: 24px;
                    padding: 20px;
                    max-width: 600px; width: 95%;
                }

                /* === CLOWN ANIMATION === */
                .clown-stage {
                    position: relative;
                    width: 180px; height: 200px;
                    margin-bottom: 8px;
                }

                /* Clown pops up from below */
                .clown-character {
                    position: absolute;
                    bottom: 0; left: 50%;
                    transform: translateX(-50%) translateY(100%);
                    font-size: 6rem;
                    animation: clownPopUp 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards;
                    filter: drop-shadow(0 0 20px rgba(239,68,68,0.2));
                    cursor: pointer;
                    transition: transform 0.2s;
                    user-select: none;
                }
                .clown-character:hover {
                    transform: translateX(-50%) rotate(10deg) scale(1.1);
                }
                .clown-character:active {
                    animation: clownSpin 0.5s ease;
                }

                @keyframes clownPopUp {
                    0% { transform: translateX(-50%) translateY(100%) scale(0.3) rotate(-20deg); opacity: 0; }
                    60% { transform: translateX(-50%) translateY(-20px) scale(1.1) rotate(5deg); opacity: 1; }
                    80% { transform: translateX(-50%) translateY(5px) scale(0.95) rotate(-3deg); }
                    100% { transform: translateX(-50%) translateY(0) scale(1) rotate(0deg); opacity: 1; }
                }
                @keyframes clownSpin {
                    0% { transform: translateX(-50%) rotate(0deg) scale(1); }
                    50% { transform: translateX(-50%) rotate(180deg) scale(1.2); }
                    100% { transform: translateX(-50%) rotate(360deg) scale(1); }
                }

                /* Sad wobble loop */
                .clown-character.sad {
                    animation: clownPopUp 1.2s cubic-bezier(0.34,1.56,0.64,1) 0.3s forwards,
                               clownSad 3s ease-in-out 2s infinite;
                }
                @keyframes clownSad {
                    0%, 100% { transform: translateX(-50%) rotate(0deg); }
                    25% { transform: translateX(-50%) rotate(-8deg) scale(0.95); }
                    50% { transform: translateX(-50%) rotate(0deg) scale(1); }
                    75% { transform: translateX(-50%) rotate(8deg) scale(0.95); }
                }

                /* Falling tears */
                .tears-container {
                    position: absolute;
                    top: 50%; left: 0; right: 0;
                    height: 100px;
                    pointer-events: none;
                    overflow: hidden;
                }
                .tear {
                    position: absolute;
                    font-size: 1.2rem;
                    opacity: 0;
                    animation: tearFall 2s ease-in infinite;
                }
                @keyframes tearFall {
                    0% { opacity: 0; transform: translateY(0) scale(0.5); }
                    10% { opacity: 1; transform: translateY(0) scale(1); }
                    100% { opacity: 0; transform: translateY(80px) scale(0.3); }
                }

                /* === ERROR TEXT === */
                .error-section {
                    text-align: center;
                    animation: fadeUp 0.8s ease 0.8s both;
                }
                @keyframes fadeUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .error-code {
                    font-size: 5.5rem;
                    font-weight: 900;
                    letter-spacing: -3px;
                    line-height: 1;
                    margin-bottom: 10px;
                    background: linear-gradient(135deg, var(--danger), #f97316, var(--danger));
                    background-size: 200% 200%;
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    animation: gradientShift 4s ease infinite;
                }
                @keyframes gradientShift {
                    0%, 100% { background-position: 0% 50%; }
                    50% { background-position: 100% 50%; }
                }

                .error-title {
                    font-size: 1.3rem;
                    font-weight: 700;
                    margin-bottom: 8px;
                }
                .error-desc {
                    font-size: 0.9rem;
                    color: var(--text-sec);
                    line-height: 1.6;
                    max-width: 420px;
                    margin: 0 auto;
                }

                /* === GAME SECTION === */
                .game-section {
                    width: 100%;
                    background: var(--card);
                    border: 1px solid var(--border);
                    border-radius: 20px;
                    padding: 24px;
                    backdrop-filter: blur(20px);
                    animation: fadeUp 0.8s ease 1.4s both;
                }
                .game-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 16px;
                }
                .game-header h3 {
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .game-header h3 span {
                    font-size: 1.1rem;
                }
                .game-score {
                    font-size: 0.85rem;
                    color: var(--text-muted);
                    font-weight: 500;
                }
                .game-score b {
                    color: var(--primary);
                    font-size: 1.1rem;
                }

                .game-canvas-wrap {
                    position: relative;
                    width: 100%;
                    aspect-ratio: 2 / 1;
                    background: rgba(0,0,0,0.4);
                    border-radius: 12px;
                    overflow: hidden;
                    border: 1px solid var(--border);
                }
                .game-canvas-wrap canvas {
                    position: absolute;
                    inset: 0;
                    width: 100%;
                    height: 100%;
                }

                .game-overlay {
                    position: absolute;
                    inset: 0;
                    background: rgba(0,0,0,0.6);
                    backdrop-filter: blur(4px);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                    z-index: 5;
                    transition: opacity 0.3s;
                }
                .game-overlay.hidden {
                    opacity: 0;
                    pointer-events: none;
                }
                .game-overlay p {
                    font-size: 0.9rem;
                    color: var(--text-sec);
                }
                .game-overlay .final-score {
                    font-size: 1.4rem;
                    font-weight: 700;
                    color: var(--primary);
                }

                .play-btn {
                    padding: 12px 28px;
                    background: var(--primary);
                    color: #fff;
                    border: none;
                    border-radius: 10px;
                    font-size: 0.9rem;
                    font-weight: 600;
                    font-family: var(--font);
                    cursor: pointer;
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .play-btn:hover {
                    background: #1d4ed8;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 15px rgba(37,99,235,0.3);
                }

                .game-controls {
                    display: flex;
                    justify-content: center;
                    gap: 6px;
                    margin-top: 12px;
                }
                .ctrl-btn {
                    width: 44px; height: 44px;
                    background: rgba(255,255,255,0.05);
                    border: 1px solid var(--border);
                    border-radius: 10px;
                    color: var(--text-sec);
                    font-size: 1rem;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.15s;
                    font-family: var(--font);
                }
                .ctrl-btn:hover {
                    background: rgba(37,99,235,0.1);
                    border-color: rgba(37,99,235,0.3);
                    color: var(--text);
                }
                .ctrl-btn:active {
                    transform: scale(0.9);
                }

                .game-hint {
                    text-align: center;
                    font-size: 0.75rem;
                    color: var(--text-muted);
                    margin-top: 8px;
                }

                /* Footer */
                .page-footer {
                    font-size: 0.75rem;
                    color: var(--text-muted);
                    margin-top: 8px;
                    animation: fadeUp 0.8s ease 1.8s both;
                }

                @media (max-width: 480px) {
                    .error-code { font-size: 3.8rem; }
                    .error-title { font-size: 1.1rem; }
                    .clown-character { font-size: 4.5rem; }
                    .clown-stage { width: 140px; height: 160px; }
                    .game-section { padding: 16px; }
                    .ctrl-btn { width: 38px; height: 38px; }
                }
            </style>
        </head>
        <body>
            <div class="bg-grid"></div>
            <canvas id="particles"></canvas>

            <div class="page-wrap">
                <!-- Clown Animation -->
                <div class="clown-stage">
                    <div class="clown-character sad" id="clown">🤡</div>
                    <div class="tears-container" id="tears"></div>
                </div>

                <!-- Error Text -->
                <div class="error-section">
                    <div class="error-code">404</div>
                    <h1 class="error-title">Oops! Nothing Here...</h1>
                    <p class="error-desc">The page you're looking for doesn't exist or is unavailable in your region. But hey, at least there's a game!</p>
                </div>

                <!-- Snake Game -->
                <div class="game-section">
                    <div class="game-header">
                        <h3><span>🐍</span> Snake Game</h3>
                        <div class="game-score">Score: <b id="scoreDisplay">0</b></div>
                    </div>
                    <div class="game-canvas-wrap">
                        <canvas id="snakeGame"></canvas>
                        <div class="game-overlay" id="gameOverlay">
                            <p id="overlayText">Bored? Play a quick game!</p>
                            <div class="final-score" id="finalScore" style="display:none;"></div>
                            <button class="play-btn" id="playBtn">&#9654; Play</button>
                        </div>
                    </div>
                    <div class="game-controls">
                        <div></div>
                        <button class="ctrl-btn" data-dir="up">&#9650;</button>
                        <div></div>
                        <button class="ctrl-btn" data-dir="left">&#9664;</button>
                        <button class="ctrl-btn" data-dir="down">&#9660;</button>
                        <button class="ctrl-btn" data-dir="right">&#9654;</button>
                    </div>
                    <div class="game-hint">Use arrow keys or buttons to control</div>
                </div>

                <div class="page-footer">🔒 Protected by GeoShield</div>
            </div>

            <script>
            (function() {
                // === PARTICLES ===
                const pc = document.getElementById('particles');
                if (pc) {
                    const ctx = pc.getContext('2d');
                    let pts = [];
                    function resize() { pc.width = innerWidth; pc.height = innerHeight; }
                    function create() {
                        pts = [];
                        const n = Math.floor((pc.width * pc.height) / 20000);
                        for (let i = 0; i < n; i++) pts.push({ x: Math.random()*pc.width, y: Math.random()*pc.height, s: Math.random()*1.2+0.4, sx: (Math.random()-0.5)*0.2, sy: (Math.random()-0.5)*0.2, o: Math.random()*0.25+0.05 });
                    }
                    function draw() {
                        ctx.clearRect(0,0,pc.width,pc.height);
                        pts.forEach(p => {
                            ctx.beginPath(); ctx.arc(p.x,p.y,p.s,0,Math.PI*2);
                            ctx.fillStyle='rgba(37,99,235,'+p.o+')'; ctx.fill();
                            p.x+=p.sx; p.y+=p.sy;
                            if(p.x<0)p.x=pc.width; if(p.x>pc.width)p.x=0;
                            if(p.y<0)p.y=pc.height; if(p.y>pc.height)p.y=0;
                        });
                        requestAnimationFrame(draw);
                    }
                    resize(); create(); draw();
                    addEventListener('resize', ()=>{ resize(); create(); });
                }

                // === TEARS ===
                const tearsEl = document.getElementById('tears');
                if (tearsEl) {
                    setInterval(() => {
                        const t = document.createElement('div');
                        t.className = 'tear';
                        t.textContent = '💧';
                        t.style.left = (30 + Math.random() * 40) + '%';
                        t.style.animationDelay = Math.random() * 0.5 + 's';
                        t.style.animationDuration = (1.5 + Math.random()) + 's';
                        tearsEl.appendChild(t);
                        setTimeout(() => t.remove(), 3000);
                    }, 600);
                }

                // === CLOWN CLICK ===
                const clown = document.getElementById('clown');
                if (clown) {
                    const faces = ['🤡','😢','😭','🥺','😿','💀','👻','🤡'];
                    let fi = 0;
                    clown.addEventListener('click', () => {
                        fi = (fi + 1) % faces.length;
                        clown.textContent = faces[fi];
                        clown.style.animation = 'none';
                        clown.offsetHeight;
                        clown.style.animation = 'clownSpin 0.5s ease';
                        setTimeout(() => {
                            clown.style.animation = '';
                            clown.classList.add('sad');
                        }, 500);
                    });
                }

                // === SNAKE GAME ===
                const gameCanvas = document.getElementById('snakeGame');
                const overlay = document.getElementById('gameOverlay');
                const playBtn = document.getElementById('playBtn');
                const scoreDisplay = document.getElementById('scoreDisplay');
                const overlayText = document.getElementById('overlayText');
                const finalScoreEl = document.getElementById('finalScore');

                if (!gameCanvas) return;
                const gctx = gameCanvas.getContext('2d');
                let gameLoop = null;
                let snake, food, dir, nextDir, score, gridSize, cols, rows, gameRunning;

                function initGame() {
                    const rect = gameCanvas.parentElement.getBoundingClientRect();
                    gameCanvas.width = Math.floor(rect.width);
                    gameCanvas.height = Math.floor(rect.height);
                    gridSize = Math.max(12, Math.floor(Math.min(gameCanvas.width, gameCanvas.height) / 25));
                    cols = Math.floor(gameCanvas.width / gridSize);
                    rows = Math.floor(gameCanvas.height / gridSize);
                    snake = [{ x: Math.floor(cols/2), y: Math.floor(rows/2) }];
                    dir = { x: 1, y: 0 };
                    nextDir = { x: 1, y: 0 };
                    score = 0;
                    scoreDisplay.textContent = '0';
                    gameRunning = true;
                    spawnFood();
                }

                function spawnFood() {
                    do {
                        food = { x: Math.floor(Math.random()*cols), y: Math.floor(Math.random()*rows) };
                    } while (snake.some(s => s.x === food.x && s.y === food.y));
                }

                function update() {
                    if (!gameRunning) return;
                    dir = { ...nextDir };
                    const head = { x: snake[0].x + dir.x, y: snake[0].y + dir.y };

                    // Wall wrap
                    if (head.x < 0) head.x = cols - 1;
                    if (head.x >= cols) head.x = 0;
                    if (head.y < 0) head.y = rows - 1;
                    if (head.y >= rows) head.y = 0;

                    // Self collision
                    if (snake.some(s => s.x === head.x && s.y === head.y)) {
                        gameOver();
                        return;
                    }

                    snake.unshift(head);

                    if (head.x === food.x && head.y === food.y) {
                        score++;
                        scoreDisplay.textContent = score;
                        spawnFood();
                    } else {
                        snake.pop();
                    }
                }

                function render() {
                    gctx.clearRect(0, 0, gameCanvas.width, gameCanvas.height);

                    // Grid dots
                    gctx.fillStyle = 'rgba(37, 99, 235, 0.04)';
                    for (let x = 0; x < cols; x++) {
                        for (let y = 0; y < rows; y++) {
                            gctx.beginPath();
                            gctx.arc(x * gridSize + gridSize/2, y * gridSize + gridSize/2, 1, 0, Math.PI*2);
                            gctx.fill();
                        }
                    }

                    // Food
                    const fx = food.x * gridSize + gridSize/2;
                    const fy = food.y * gridSize + gridSize/2;
                    gctx.shadowBlur = 15;
                    gctx.shadowColor = 'rgba(239, 68, 68, 0.6)';
                    gctx.fillStyle = '#ef4444';
                    gctx.beginPath();
                    gctx.arc(fx, fy, gridSize/2 - 2, 0, Math.PI*2);
                    gctx.fill();
                    gctx.shadowBlur = 0;

                    // Snake
                    snake.forEach((s, i) => {
                        const ratio = 1 - (i / snake.length) * 0.5;
                        const r = gridSize/2 - 1;
                        gctx.fillStyle = i === 0
                            ? '#2563eb'
                            : 'rgba(37, 99, 235, ' + (0.3 + ratio * 0.5) + ')';
                        gctx.shadowBlur = i === 0 ? 10 : 0;
                        gctx.shadowColor = 'rgba(37, 99, 235, 0.5)';
                        gctx.beginPath();
                        gctx.roundRect(s.x * gridSize + 1, s.y * gridSize + 1, gridSize - 2, gridSize - 2, 3);
                        gctx.fill();
                        gctx.shadowBlur = 0;

                        // Eyes on head
                        if (i === 0) {
                            gctx.fillStyle = '#fff';
                            const ex1 = s.x * gridSize + gridSize * 0.3;
                            const ex2 = s.x * gridSize + gridSize * 0.7;
                            const ey = s.y * gridSize + gridSize * 0.35;
                            gctx.beginPath(); gctx.arc(ex1, ey, 2, 0, Math.PI*2); gctx.fill();
                            gctx.beginPath(); gctx.arc(ex2, ey, 2, 0, Math.PI*2); gctx.fill();
                        }
                    });
                }

                function tick() {
                    update();
                    render();
                }

                function startGame() {
                    if (gameLoop) clearInterval(gameLoop);
                    initGame();
                    overlay.classList.add('hidden');
                    finalScoreEl.style.display = 'none';
                    gameLoop = setInterval(tick, 110);
                }

                function gameOver() {
                    gameRunning = false;
                    if (gameLoop) clearInterval(gameLoop);
                    overlayText.textContent = 'Game Over!';
                    finalScoreEl.textContent = 'Score: ' + score;
                    finalScoreEl.style.display = 'block';
                    playBtn.innerHTML = '&#9654; Play Again';
                    overlay.classList.remove('hidden');
                }

                playBtn.addEventListener('click', startGame);

                // Keyboard
                document.addEventListener('keydown', e => {
                    if (!gameRunning) return;
                    switch(e.key) {
                        case 'ArrowUp':    case 'w': case 'W': if(dir.y!==1)  nextDir={x:0,y:-1}; e.preventDefault(); break;
                        case 'ArrowDown':  case 's': case 'S': if(dir.y!==-1) nextDir={x:0,y:1};  e.preventDefault(); break;
                        case 'ArrowLeft':  case 'a': case 'A': if(dir.x!==1)  nextDir={x:-1,y:0}; e.preventDefault(); break;
                        case 'ArrowRight': case 'd': case 'D': if(dir.x!==-1) nextDir={x:1,y:0};  e.preventDefault(); break;
                    }
                });

                // Mobile buttons
                document.querySelectorAll('.ctrl-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (!gameRunning) return;
                        switch(btn.dataset.dir) {
                            case 'up':    if(dir.y!==1)  nextDir={x:0,y:-1}; break;
                            case 'down':  if(dir.y!==-1) nextDir={x:0,y:1};  break;
                            case 'left':  if(dir.x!==1)  nextDir={x:-1,y:0}; break;
                            case 'right': if(dir.x!==-1) nextDir={x:1,y:0};  break;
                        }
                    });
                });

                // Initial render
                initGame();
                gameRunning = false;
                render();

            })();
            </script>
        </body>
        </html>
        <?php
        exit;
    }

        /**
     * Очистить устаревший кэш (вызывайте по cron раз в сутки)
     */
    public static function cleanCache() {
        if (!is_dir(self::$cache_dir)) return;
        foreach (glob(self::$cache_dir . '*.txt') as $file) {
            if ((time() - filemtime($file)) > self::$cache_ttl) {
                @unlink($file);
            }
        }
    }

    /**
     * Основная проверка
     */
    public static function check() {
        if (self::shouldBlock()) {
            self::showBlockPage();
        }
    }

} // end class GeoBlock
// === АВТОЗАПУСК ===
GeoBlock::check();
?>