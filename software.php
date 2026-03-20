<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/admin/db_connect.php';
require_once __DIR__ . '/geoblock.php';

$slug = $_GET['slug'] ?? '';
if ($slug === '') {
    header('Location: /#software');
    exit;
}

$game = get_game_by_slug($slug);
if (!$game) {
    http_response_code(404);
    header('Location: /#software');
    exit;
}

if (function_exists('log_visit')) log_visit();

$site_name     = get_setting('site_name') ?? 'SoftMaster';
$download_link = get_setting('download_link') ?? '#';
$screenshots   = function_exists('get_game_screenshots') ? get_game_screenshots($game['id']) : [];
$categories    = function_exists('get_all_categories') ? get_all_categories() : [];

// Helper: returns correct image src for both URLs and local paths
function img_src($path) {
    if (empty($path)) return '';
    // If it's already a full URL, return as-is
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
        return $path;
    }
    // Local path — prepend /
    return '/' . ltrim($path, '/');
}

$cat_name = 'Uncategorized';
if (!empty($game['category_id'])) {
    foreach ($categories as $c) {
        if ($c['id'] == $game['category_id']) { $cat_name = $c['name']; break; }
    }
}

$meta_title = !empty($game['meta_title'])
    ? $game['meta_title']
    : $game['name'] . ' — Download Free | ' . $site_name;

$meta_desc = !empty($game['meta_description'])
    ? $game['meta_description']
    : 'Download ' . $game['name'] . ' full version from ' . $site_name . '. Free trial available. ' . ($game['description'] ? substr(strip_tags($game['description']), 0, 120) . '...' : '');

// OG image: must be absolute URL
$og_image = '';
if (!empty($game['image'])) {
    if (str_starts_with($game['image'], 'http://') || str_starts_with($game['image'], 'https://')) {
        $og_image = $game['image'];
    } else {
        $og_image = 'https://softmaster.pro/' . ltrim($game['image'], '/');
    }
} else {
    $og_image = 'https://softmaster.pro/img/og-cover.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($meta_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://softmaster.pro/software/<?= htmlspecialchars($game['slug']) ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://softmaster.pro/software/<?= htmlspecialchars($game['slug']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($meta_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($site_name) ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($meta_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "<?= htmlspecialchars($game['name']) ?>",
        "url": "https://softmaster.pro/software/<?= htmlspecialchars($game['slug']) ?>",
        "description": "<?= htmlspecialchars($meta_desc) ?>",
        "applicationCategory": "DesktopEnhancement",
        "operatingSystem": "Windows",
        <?php if (!empty($game['developer'])): ?>"author": {"@type":"Organization","name":"<?= htmlspecialchars($game['developer']) ?>"},<?php endif; ?>
        <?php if (!empty($game['version'])): ?>"softwareVersion": "<?= htmlspecialchars($game['version']) ?>",<?php endif; ?>
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD",
            "description": "Free trial available"
        },
        "image": "<?= htmlspecialchars($og_image) ?>"
    }
    </script>

    <meta name="theme-color" content="#0a0e1a">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/landing.css">
</head>
<body>

<canvas id="particleCanvas"></canvas>

<!-- Header -->
<header class="header" id="header">
    <div class="container">
        <a href="/" class="logo">
            <div class="logo-icon"><i class="fas fa-gamepad"></i></div>
            <span class="logo-text"><b><?= htmlspecialchars($site_name) ?></b></span>
        </a>
        <nav class="nav" id="mainNav">
            <ul>
                <li><a href="/#features" class="nav-link">Features</a></li>
                <li><a href="/#software" class="nav-link">Software</a></li>
                <li><a href="/#how-it-works" class="nav-link">How It Works</a></li>
                <li><a href="/pricing.php" class="nav-link">Pricing</a></li>
                <li><a href="/#support" class="nav-link">Support</a></li>
                <li><a href="/#download" class="nav-link nav-cta">Download</a></li>
            </ul>
        </nav>
        <button class="mobile-menu-btn" id="mobileToggle"><span></span><span></span><span></span></button>
    </div>
</header>

<!-- Software Detail Page -->
<section class="sw-page" style="padding-top: 120px; min-height: 100vh;">
    <div class="container">

        <!-- Breadcrumb -->
        <div class="sw-breadcrumb">
            <a href="/"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="/#software">Software</a>
            <i class="fas fa-chevron-right"></i>
            <span><?= htmlspecialchars($game['name']) ?></span>
        </div>

        <div class="sd-body">
            <!-- Header -->
            <div class="sd-header-section">
                <div class="sd-cover">
                    <?php if (!empty($game['image'])): ?>
                        <img src="<?= htmlspecialchars(img_src($game['image'])) ?>" alt="<?= htmlspecialchars($game['name']) ?>">
                    <?php else: ?>
                        <div class="sd-cover-placeholder"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </div>
                <div class="sd-info">
                    <h1 class="sd-title"><?= htmlspecialchars($game['name']) ?></h1>
                    <div class="sd-meta-row">
                        <span class="sd-meta-tag"><i class="fas fa-folder"></i> <?= htmlspecialchars($cat_name) ?></span>
                        <?php if (!empty($game['version'])): ?>
                            <span class="sd-meta-tag"><i class="fas fa-code-branch"></i> <?= htmlspecialchars($game['version']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($game['developer'])): ?>
                            <span class="sd-meta-tag"><i class="fas fa-building"></i> <?= htmlspecialchars($game['developer']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($game['description'])): ?>
                        <p class="sd-description"><?= nl2br(htmlspecialchars($game['description'])) ?></p>
                    <?php else: ?>
                        <p class="sd-description sd-empty">No description available yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Features -->
            <?php if (!empty($game['features'])): ?>
                <?php $featureLines = array_filter(explode("\n", $game['features']), fn($l) => trim($l)); ?>
                <?php if (count($featureLines)): ?>
                    <div class="sd-features">
                        <h4><i class="fas fa-check-circle"></i> Key Features</h4>
                        <ul>
                            <?php foreach ($featureLines as $line): ?>
                                <li><?= htmlspecialchars(trim($line)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- System Requirements -->
            <?php if (!empty($game['system_requirements'])): ?>
                <div class="sd-sysreq">
                    <h4><i class="fas fa-desktop"></i> System Requirements</h4>
                    <p><?= nl2br(htmlspecialchars($game['system_requirements'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Screenshots -->
            <?php if (!empty($screenshots)): ?>
                <div class="sd-screenshots">
                    <h4><i class="fas fa-images"></i> Screenshots</h4>
                    <div class="sd-slideshow">
                        <button class="sd-slide-btn sd-slide-prev" id="swPagePrev"><i class="fas fa-chevron-left"></i></button>
                        <div class="sd-slide-container" id="swPageSlides">
                            <?php foreach ($screenshots as $i => $s): ?>
                                <img src="<?= htmlspecialchars(img_src($s['image_path'])) ?>" class="sd-slide <?= $i === 0 ? 'active' : '' ?>" alt="Screenshot <?= $i + 1 ?>">
                            <?php endforeach; ?>
                        </div>
                        <button class="sd-slide-btn sd-slide-next" id="swPageNext"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="sd-slide-dots" id="swPageDots">
                        <?php foreach ($screenshots as $i => $s): ?>
                            <span class="sd-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Download CTA -->
            <div class="sd-actions">
                <a href="/#download" class="btn btn-primary btn-lg btn-glow sd-download-btn">
                    <i class="fas fa-download"></i> Download <?= htmlspecialchars($game['name']) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<button class="scroll-top" id="scrollTop"><i class="fas fa-arrow-up"></i></button>

<style>
.sw-breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
    font-size: 14px;
    color: #64748b;
}
.sw-breadcrumb a {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s;
}
.sw-breadcrumb a:hover {
    color: #2563eb;
}
.sw-breadcrumb i.fa-chevron-right {
    font-size: 10px;
    opacity: 0.4;
}
.sw-breadcrumb span {
    color: #f1f5f9;
    font-weight: 600;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Header scroll
    const header = document.getElementById('header');
    if (header) {
        window.addEventListener('scroll', () => header.classList.toggle('scrolled', window.scrollY > 50));
    }

    // Mobile menu
    const mt = document.getElementById('mobileToggle');
    const mn = document.getElementById('mainNav');
    if (mt && mn) {
        mt.addEventListener('click', () => { mt.classList.toggle('active'); mn.classList.toggle('open'); });
    }

    // Scroll top
    const stb = document.getElementById('scrollTop');
    if (stb) {
        window.addEventListener('scroll', () => stb.classList.toggle('visible', window.scrollY > 500));
        stb.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // Slideshow
    const slides = document.querySelectorAll('#swPageSlides .sd-slide');
    const dots = document.querySelectorAll('#swPageDots .sd-dot');
    if (slides.length > 0) {
        let current = 0;
        function show(i) {
            slides.forEach((s, idx) => s.classList.toggle('active', idx === i));
            dots.forEach((d, idx) => d.classList.toggle('active', idx === i));
            current = i;
        }
        const prev = document.getElementById('swPagePrev');
        const next = document.getElementById('swPageNext');
        if (prev) prev.addEventListener('click', () => show((current - 1 + slides.length) % slides.length));
        if (next) next.addEventListener('click', () => show((current + 1) % slides.length));
        dots.forEach(d => d.addEventListener('click', () => show(parseInt(d.dataset.index))));
    }

    // Particle canvas
    const canvas = document.getElementById('particleCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let particles = [];
        function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
        function create() {
            particles = [];
            const c = Math.floor((canvas.width * canvas.height) / 20000);
            for (let i = 0; i < c; i++) {
                particles.push({ x: Math.random()*canvas.width, y: Math.random()*canvas.height, size: Math.random()*1.2+0.3, sx: (Math.random()-0.5)*0.2, sy: (Math.random()-0.5)*0.2, o: Math.random()*0.3+0.05 });
            }
        }
        function draw() {
            ctx.clearRect(0,0,canvas.width,canvas.height);
            particles.forEach(p => {
                ctx.beginPath(); ctx.arc(p.x,p.y,p.size,0,Math.PI*2);
                ctx.fillStyle = `rgba(37,99,235,${p.o})`; ctx.fill();
                p.x += p.sx; p.y += p.sy;
                if(p.x<0)p.x=canvas.width; if(p.x>canvas.width)p.x=0;
                if(p.y<0)p.y=canvas.height; if(p.y>canvas.height)p.y=0;
            });
            requestAnimationFrame(draw);
        }
        resize(); create(); draw();
        window.addEventListener('resize', () => { resize(); create(); });
    }
});
</script>

</body>
</html>