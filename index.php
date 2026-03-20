<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/admin/db_connect.php';
require_once __DIR__ . '/geoblock.php';

if (function_exists('log_visit')) {
    log_visit();
}

$stats = function_exists('get_site_stats') ? get_site_stats() : ['total_downloads' => 0, 'total_visits' => 0];
$games = function_exists('get_games_list') ? get_games_list() : [];
$categories = function_exists('get_all_categories') ? get_all_categories() : [];

$site_name = get_setting('site_name') ?? 'SoftMaster';
$hero_title = get_setting('hero_title') ?? 'Next-Gen Game Enhancement Platform';
$hero_subtitle = get_setting('hero_subtitle') ?? 'Premium tools for serious gamers';
$download_link = get_setting('download_link') ?? '#';

// Counter offsets from admin settings
$downloads_offset = (int)(get_setting('downloads_offset') ?? 0);
$users_offset = (int)(get_setting('users_offset') ?? 0);

// Displayed values = offset + real stats
$display_downloads = $downloads_offset + (int)($stats['total_downloads'] ?? 0);
$display_users = $users_offset + (int)($stats['total_visits'] ?? 0);

$promo_enabled = get_setting('promo_enabled') ?? '0';
$promo_title = get_setting('promo_title') ?? 'Special Offer!';
$promo_text = get_setting('promo_text') ?? 'Get 50% off on all plans.';
$promo_countdown = (int)(get_setting('promo_countdown') ?? 300);
$promo_btn_text = get_setting('promo_btn_text') ?? 'Get Deal Now';
$promo_btn_link = get_setting('promo_btn_link') ?? '#pricing';
$promo_animation = get_setting('promo_animation') ?? 'fadeIn';
$promo_delay = (int)(get_setting('promo_delay') ?? 5);

$cat_games = [];
foreach ($games as $g) {
    $cid = (int)($g['category_id'] ?? 0);
    $cat_games[$cid][] = $g;
}
$uncategorized = $cat_games[0] ?? [];

// Prepare games JSON for modal
$games_json = [];
foreach ($games as $g) {
    $cat_name = 'Uncategorized';
    if (!empty($g['category_id'])) {
        foreach ($categories as $c) {
            if ($c['id'] == $g['category_id']) { $cat_name = $c['name']; break; }
        }
    }
    $screenshots = function_exists('get_game_screenshots') ? get_game_screenshots($g['id']) : [];
    $screens_arr = [];
    foreach ($screenshots as $s) {
        $screens_arr[] = $s['image_path'];
    }
    $games_json[] = [
        'id'          => (int)$g['id'],
        'name'        => $g['name'],
        'slug'        => $g['slug'] ?? '',
        'image'       => $g['image'] ?? '',
        'description' => $g['description'] ?? '',
        'version'     => $g['version'] ?? '',
        'developer'   => $g['developer'] ?? '',
        'features'    => $g['features'] ?? '',
        'system_requirements' => $g['system_requirements'] ?? '',
        'category'    => $cat_name,
        'screenshots' => $screens_arr,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($site_name) ?> — All-in-One Software Launcher | Free Trial</title>
    <meta name="description" content="SoftMaster is an all-in-one software launcher with full versions of Photoshop, Sony Vegas Pro, FL Studio, Office and 50+ premium apps. Download free trial today.">
    <meta name="keywords" content="software launcher, all in one software, free software download, photoshop free, sony vegas pro free, video editing software, graphic design tools, premium software launcher, software bundle, free trial software, SoftMaster">
    <meta name="author" content="SoftMaster">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="https://softmaster.pro/">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://softmaster.pro/">
    <meta property="og:title" content="SoftMaster — All-in-One Software Launcher">
    <meta property="og:description" content="Get full versions of Photoshop, Sony Vegas, FL Studio & 50+ premium apps in one launcher. Free trial available.">
    <meta property="og:image" content="https://softmaster.pro/img/og-cover.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="SoftMaster">
    <meta property="og:locale" content="en_US">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SoftMaster — All-in-One Software Launcher">
    <meta name="twitter:description" content="50+ full-version premium apps in one launcher. Photoshop, Vegas Pro, FL Studio & more. Free trial.">
    <meta name="twitter:image" content="https://softmaster.pro/img/og-cover.png">

    <meta name="theme-color" content="#0a0e1a">
    <meta name="msapplication-TileColor" content="#0a0e1a">
    <meta name="apple-mobile-web-app-title" content="SoftMaster">
    <meta name="application-name" content="SoftMaster">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "SoftMaster",
        "url": "https://softmaster.pro",
        "description": "All-in-one software launcher with full versions of premium applications including Photoshop, Sony Vegas Pro, FL Studio and 50+ more.",
        "applicationCategory": "UtilitiesApplication",
        "operatingSystem": "Windows",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD",
            "description": "Free trial available"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "<?= $display_downloads ?>",
            "bestRating": "5"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "SoftMaster",
        "url": "https://softmaster.pro",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://softmaster.pro/?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "SoftMaster",
        "url": "https://softmaster.pro",
        "logo": "https://softmaster.pro/img/logo.png",
        "sameAs": []
    }
    </script>

    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/landing.css">
</head>
<body class="preloader-active">

<!-- Preloader -->
<div id="preloader">
    <div class="preloader-inner">
        <div class="preloader-icon">
            <div class="gear gear-large"><i class="fas fa-cog"></i></div>
            <div class="gear gear-small"><i class="fas fa-cog"></i></div>
        </div>
        <span class="preloader-title"><?= htmlspecialchars($site_name) ?></span>
        <div class="preloader-code">
            <span class="code-line" data-delay="0">$ initializing modules...</span>
            <span class="code-line" data-delay="1">$ loading components...</span>
            <span class="code-line" data-delay="2">$ system ready ✓</span>
        </div>
        <div class="preloader-bar">
            <div class="preloader-progress"></div>
        </div>
    </div>
</div>

<canvas id="particleCanvas"></canvas>

<!-- Header -->
<header class="header" id="header">
    <div class="container">
        <a href="#" class="logo">
            <div class="logo-icon"><i class="fas fa-gamepad"></i></div>
            <span class="logo-text"><b><?= htmlspecialchars($site_name) ?></b></span>
        </a>
        <nav class="nav" id="mainNav">
            <ul>
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#software" class="nav-link">Software</a></li>
                <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                <li><a href="pricing.php" class="nav-link">Pricing</a></li>
                <li><a href="#support" class="nav-link">Support</a></li>
                <li><a href="#download" class="nav-link nav-cta">Download</a></li>
            </ul>
        </nav>
        <button class="mobile-menu-btn" id="mobileToggle">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<!-- Hero -->
<section class="hero" id="hero">
    <div class="hero-bg-grid"></div>
    <div class="hero-glow hero-glow-1"></div>
    <div class="hero-glow hero-glow-2"></div>
    <div class="container">
        <div class="hero-text">
            <div class="hero-badge"><i class="fas fa-bolt"></i> Next Generation Platform</div>
            <h1 class="hero-title"><?= htmlspecialchars($hero_title) ?></h1>
            <p class="hero-subtitle"><?= htmlspecialchars($hero_subtitle) ?></p>
            <div class="hero-actions">
                <a href="#download" class="btn btn-primary btn-lg btn-glow"><i class="fas fa-download"></i> Download Now</a>
                <a href="#software" class="btn btn-outline btn-lg"><i class="fas fa-th-large"></i> Browse Software</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-value" data-target="<?= $display_downloads ?>">0</span>
                    <span class="hero-stat-suffix">+</span>
                    <span class="hero-stat-label">Downloads</span>
                </div>
                <div class="hero-stat-divider"></div>
                <div class="hero-stat">
                    <span class="hero-stat-value" data-target="<?= count($games) ?>">0</span>
                    <span class="hero-stat-label">Products</span>
                </div>
                <div class="hero-stat-divider"></div>
                <div class="hero-stat">
                    <span class="hero-stat-value" data-target="<?= $display_users ?>">0</span>
                    <span class="hero-stat-suffix">+</span>
                    <span class="hero-stat-label">Users</span>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-dashboard">
                <div class="dashboard-header">
                    <div class="dashboard-dots"><span></span><span></span><span></span></div>
                    <span class="dashboard-title"><?= htmlspecialchars($site_name) ?> — Control Panel</span>
                </div>
                <div class="dashboard-body">
                    <div class="dashboard-sidebar">
                        <div class="dash-nav-item active"><i class="fas fa-home"></i></div>
                        <div class="dash-nav-item"><i class="fas fa-gamepad"></i></div>
                        <div class="dash-nav-item"><i class="fas fa-chart-bar"></i></div>
                        <div class="dash-nav-item"><i class="fas fa-cog"></i></div>
                    </div>
                    <div class="dashboard-content">
                        <?php
                        $dashGames = array_slice($games, 0, 3);
                        $statuses = ['active', 'ready', 'update'];
                        foreach ($dashGames as $i => $dg):
                            $st = $statuses[$i % 3];
                        ?>
                        <div class="dash-card dash-card-<?= $i + 1 ?>">
                            <div class="dash-card-icon"><i class="fas fa-gamepad"></i></div>
                            <div class="dash-card-info">
                                <span><?= htmlspecialchars($dg['name']) ?></span>
                                <small><?= ucfirst($st) ?></small>
                            </div>
                            <div class="dash-card-status <?= $st ?>"></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($dashGames) < 3): ?>
                            <?php for ($i = count($dashGames); $i < 3; $i++): ?>
                            <div class="dash-card dash-card-<?= $i + 1 ?>">
                                <div class="dash-card-icon"><i class="fas fa-gamepad"></i></div>
                                <div class="dash-card-info"><span>Software <?= $i + 1 ?></span><small>Ready</small></div>
                                <div class="dash-card-status ready"></div>
                            </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                        <div class="dash-chart">
                            <div class="chart-bar" style="--height: 40%"></div>
                            <div class="chart-bar" style="--height: 65%"></div>
                            <div class="chart-bar" style="--height: 45%"></div>
                            <div class="chart-bar" style="--height: 80%"></div>
                            <div class="chart-bar" style="--height: 55%"></div>
                            <div class="chart-bar" style="--height: 90%"></div>
                            <div class="chart-bar" style="--height: 70%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features" id="features">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Features</span>
            <h2 class="section-title">Why Choose <span class="gradient-text"><?= htmlspecialchars($site_name) ?></span></h2>
            <p class="section-subtitle">Experience the next level of game enhancement with our cutting-edge platform</p>
        </div>
        <div class="features-grid">
            <div class="feature-card animate-on-scroll"><div class="feature-icon-wrap"><div class="feature-icon"><i class="fas fa-shield-alt"></i></div></div><h3>Undetected</h3><p>Advanced protection systems keep you safe with real-time signature updates and kernel-level stealth technology.</p></div>
            <div class="feature-card animate-on-scroll"><div class="feature-icon-wrap"><div class="feature-icon"><i class="fas fa-bolt"></i></div></div><h3>Lightning Fast</h3><p>Optimized for minimal impact on game performance. Zero lag, maximum efficiency with our proprietary engine.</p></div>
            <div class="feature-card animate-on-scroll"><div class="feature-icon-wrap"><div class="feature-icon"><i class="fas fa-sync-alt"></i></div></div><h3>Auto Updates</h3><p>Always stay current with automatic updates that keep your tools working after every game patch.</p></div>
            <div class="feature-card animate-on-scroll"><div class="feature-icon-wrap"><div class="feature-icon"><i class="fas fa-headset"></i></div></div><h3>24/7 Support</h3><p>Our dedicated team is available around the clock to help with any issues or questions you may have.</p></div>
            <div class="feature-card animate-on-scroll"><div class="feature-icon-wrap"><div class="feature-icon"><i class="fas fa-code"></i></div></div><h3>Custom Configs</h3><p>Fully customizable settings and configurations to match your playstyle and preferences perfectly.</p></div>
            <div class="feature-card animate-on-scroll"><div class="feature-icon-wrap"><div class="feature-icon"><i class="fas fa-users"></i></div></div><h3>Active Community</h3><p>Join thousands of satisfied users in our vibrant community with guides, tips, and shared configurations.</p></div>
        </div>
    </div>
</section>

<!-- Software Catalog -->
<section class="software" id="software">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Catalog</span>
            <h2 class="section-title">Software <span class="gradient-text">Catalog</span></h2>
            <p class="section-subtitle">Browse our collection of premium software tools</p>
        </div>

        <div class="software-search-wrapper">
            <div class="software-search-box">
                <i class="fas fa-search software-search-icon"></i>
                <input type="text" id="softwareSearch" class="software-search-input" placeholder="Search software..." autocomplete="off">
                <button type="button" id="softwareSearchClear" class="software-search-clear" style="display:none;"><i class="fas fa-times"></i></button>
            </div>
        </div>

        <div class="software-tabs">
            <button class="software-tab active" data-tab="all">All (<?= count($games) ?>)</button>
            <?php if (!empty($uncategorized)): ?>
                <button class="software-tab" data-tab="cat-0">Uncategorized (<?= count($uncategorized) ?>)</button>
            <?php endif; ?>
            <?php foreach ($categories as $cat): ?>
                <?php $cnt = count($cat_games[$cat['id']] ?? []); if ($cnt === 0 && !($cat['is_active'] ?? 1)) continue; ?>
                <button class="software-tab" data-tab="cat-<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= $cnt ?>)</button>
            <?php endforeach; ?>
        </div>

        <div class="software-panel" id="searchResultsPanel" style="display:none;">
            <div class="software-grid" id="searchResultsGrid"></div>
            <div class="pagination" id="searchPagination"></div>
        </div>

        <div class="software-panel active" data-panel="all">
            <div class="software-grid" id="allGamesGrid">
                <?php if (empty($games)): ?>
                    <div class="software-empty"><i class="fas fa-box-open"></i><p>No software available yet</p></div>
                <?php else: ?>
                    <?php foreach ($games as $game): ?>
                        <div class="software-card" data-game-name="<?= htmlspecialchars(strtolower($game['name'])) ?>" data-category-id="<?= (int)($game['category_id'] ?? 0) ?>" data-game-id="<?= (int)$game['id'] ?>">
                            <div class="software-image">
                                <img src="<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['name']) ?>" loading="lazy">
                                <div class="software-overlay">
                                    <span class="software-badge"><?= ($game['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></span>
                                </div>
                            </div>
                            <div class="software-info">
                                <div class="software-name-wrap"><h3><?= htmlspecialchars($game['name']) ?></h3></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="pagination" id="allGamesPagination"></div>
        </div>

        <?php if (!empty($uncategorized)): ?>
        <div class="software-panel" data-panel="cat-0">
            <div class="software-grid">
                <?php foreach ($uncategorized as $game): ?>
                    <div class="software-card" data-game-name="<?= htmlspecialchars(strtolower($game['name'])) ?>" data-game-id="<?= (int)$game['id'] ?>">
                        <div class="software-image">
                            <img src="<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['name']) ?>" loading="lazy">
                            <div class="software-overlay"><span class="software-badge"><?= ($game['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></span></div>
                        </div>
                        <div class="software-info"><div class="software-name-wrap"><h3><?= htmlspecialchars($game['name']) ?></h3></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="pagination"></div>
        </div>
        <?php endif; ?>

        <?php foreach ($categories as $cat): ?>
            <?php $cGames = $cat_games[$cat['id']] ?? []; ?>
            <div class="software-panel" data-panel="cat-<?= (int)$cat['id'] ?>">
                <div class="software-grid">
                    <?php if (empty($cGames)): ?>
                        <div class="software-empty"><i class="fas fa-box-open"></i><p>No software in this category</p></div>
                    <?php else: ?>
                        <?php foreach ($cGames as $game): ?>
                            <div class="software-card" data-game-name="<?= htmlspecialchars(strtolower($game['name'])) ?>" data-game-id="<?= (int)$game['id'] ?>">
                                <div class="software-image">
                                    <img src="<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['name']) ?>" loading="lazy">
                                    <div class="software-overlay"><span class="software-badge"><?= ($game['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></span></div>
                                </div>
                                <div class="software-info"><div class="software-name-wrap"><h3><?= htmlspecialchars($game['name']) ?></h3></div></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="pagination"></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- How It Works -->
<section class="how-it-works" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Process</span>
            <h2 class="section-title">How It <span class="gradient-text">Works</span></h2>
            <p class="section-subtitle">Get started in just a few simple steps</p>
        </div>
        <div class="steps-timeline">
            <div class="timeline-line"><div class="timeline-progress" id="timelineProgress"></div></div>
            <div class="step-item animate-on-scroll"><div class="step-number"><span>1</span><div class="step-pulse"></div></div><div class="step-content"><div class="step-icon"><i class="fas fa-download"></i></div><h3>Download</h3><p>Get the latest version of our launcher. Quick and secure installation.</p></div></div>
            <div class="step-item animate-on-scroll"><div class="step-number"><span>2</span><div class="step-pulse"></div></div><div class="step-content"><div class="step-icon"><i class="fas fa-key"></i></div><h3>Generate Key</h3><p>Create your unique trial key. Instant generation, no waiting.</p></div></div>
            <div class="step-item animate-on-scroll"><div class="step-number"><span>3</span><div class="step-pulse"></div></div><div class="step-content"><div class="step-icon"><i class="fas fa-check-circle"></i></div><h3>Activate</h3><p>Enter your key in the launcher to unlock all features.</p></div></div>
            <div class="step-item animate-on-scroll"><div class="step-number"><span>4</span><div class="step-pulse"></div></div><div class="step-content"><div class="step-icon"><i class="fas fa-play"></i></div><h3>Enjoy</h3><p>Launch games with enhanced features enabled.</p></div></div>
        </div>
    </div>
</section>

<!-- Why Us -->
<section class="why-us" id="why-us">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Advantages</span>
            <h2 class="section-title">Why <span class="gradient-text">Us</span></h2>
            <p class="section-subtitle">What sets us apart from the competition</p>
        </div>
        <div class="why-us-grid">
            <div class="why-card why-card-large animate-on-scroll">
                <div class="why-card-bg"><div class="floating-icons"><i class="fas fa-shield-alt floating-icon fi-1"></i><i class="fas fa-lock floating-icon fi-2"></i><i class="fas fa-check-circle floating-icon fi-3"></i></div></div>
                <div class="why-card-content">
                    <div class="why-icon"><i class="fas fa-trophy"></i></div>
                    <h3>Trusted by Thousands</h3>
                    <p>Over <?= number_format($display_downloads) ?> downloads and growing. Our track record speaks for itself with industry-leading reliability.</p>
                    <div class="perf-stats">
                        <div class="perf-stat"><div class="perf-circle"><svg viewBox="0 0 36 36"><circle class="perf-bg" cx="18" cy="18" r="15.9"/><circle class="perf-fill" cx="18" cy="18" r="15.9" stroke-dasharray="99, 100"/></svg><span>99%</span></div><small>Uptime</small></div>
                        <div class="perf-stat"><div class="perf-circle"><svg viewBox="0 0 36 36"><circle class="perf-bg" cx="18" cy="18" r="15.9"/><circle class="perf-fill" cx="18" cy="18" r="15.9" stroke-dasharray="95, 100"/></svg><span>95%</span></div><small>Satisfaction</small></div>
                    </div>
                </div>
            </div>
            <div class="why-card animate-on-scroll"><div class="why-card-content"><div class="why-icon"><i class="fas fa-sync-alt"></i></div><h3>Regular Updates</h3><p>We push updates within hours of game patches to ensure continuous, uninterrupted service.</p><div class="why-tags"><span class="tag">Fast Patches</span><span class="tag">Auto Update</span><span class="tag">24h Response</span></div></div></div>
            <div class="why-card animate-on-scroll"><div class="why-card-content"><div class="why-icon"><i class="fas fa-dollar-sign"></i></div><h3>Fair Pricing</h3><p>Competitive pricing with flexible plans. Choose what works best for your needs and budget.</p><div class="why-tags"><span class="tag">Flexible Plans</span><span class="tag">No Hidden Fees</span></div></div></div>
        </div>
    </div>
</section>

<!-- Download Section -->
<section class="download" id="download">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Get Started</span>
            <h2 class="section-title">Download <span class="gradient-text">Now</span></h2>
            <p class="section-subtitle">Follow the steps below to get started</p>
        </div>
        <div class="download-widget">
            <div class="download-steps-indicator">
                <div class="dsi-step active" id="dsi1"><div class="dsi-number">1</div><span>Generate</span></div>
                <div class="dsi-connector"><div class="dsi-connector-fill" id="dsiFill1"></div></div>
                <div class="dsi-step" id="dsi2"><div class="dsi-number">2</div><span>Copy Key</span></div>
                <div class="dsi-connector"><div class="dsi-connector-fill" id="dsiFill2"></div></div>
                <div class="dsi-step" id="dsi3"><div class="dsi-number">3</div><span>Download</span></div>
            </div>
            <div class="step-content active" id="step1"><div class="step-card"><div class="step-card-icon"><i class="fas fa-key"></i></div><h3>Generate Your Trial Key</h3><p>Click the button below to generate your unique trial activation key.</p><button class="btn btn-primary btn-lg btn-glow" id="generateKey"><i class="fas fa-key"></i> Generate Key</button></div></div>
            <div class="step-content" id="step2"><div class="step-card"><div class="step-card-icon"><i class="fas fa-copy"></i></div><h3>Copy Your Key</h3><p>Your unique trial key has been generated. Copy it and proceed to download.</p><div class="license-display"><div class="license-label"><i class="fas fa-key"></i> Your Trial Key</div><div class="license-code" id="trialKey">XXXX-XXXX-XXXX-XXXX</div><button class="copy-btn" id="copyKey" title="Copy to clipboard"><i class="fas fa-copy"></i></button></div><div class="license-notice"><i class="fas fa-info-circle"></i> Save this key — you'll need it to activate the launcher</div><button class="btn btn-primary btn-lg btn-glow" id="goStep3">Continue <i class="fas fa-arrow-right"></i></button></div></div>
            <div class="step-content" id="step3"><div class="step-card"><div class="step-card-icon"><i class="fas fa-download"></i></div><h3>Download Launcher</h3><p>Your key has been generated. Click below to download the launcher.</p><a href="<?= htmlspecialchars($download_link) ?>" class="download-btn-main" id="downloadBtn" target="_blank"><i class="fas fa-download"></i> Download Launcher</a><div class="download-meta"><span><i class="fas fa-shield-alt"></i> Verified Safe</span><span><i class="fas fa-clock"></i> Quick Setup</span><span><i class="fas fa-sync"></i> Auto Updates</span></div></div></div>
        </div>
    </div>
</section>

<!-- Support -->
<section class="support" id="support">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Help</span>
            <h2 class="section-title">Need <span class="gradient-text">Help?</span></h2>
            <p class="section-subtitle">We're here to assist you</p>
        </div>
        <div class="support-grid">
            <div class="support-card animate-on-scroll"><div class="support-icon"><i class="fas fa-comment-dots"></i></div><h3>Live Chat</h3><p>Get instant help through our live chat support system available 24/7.</p><button class="btn btn-outline btn-sm" onclick="document.getElementById('chatWidget').classList.add('chat-open')">Start Chat</button></div>
            <div class="support-card animate-on-scroll"><div class="support-icon"><i class="fas fa-book"></i></div><h3>Documentation</h3><p>Browse our comprehensive guides and tutorials for quick answers.</p><button class="btn btn-outline btn-sm">View Docs</button></div>
            <div class="support-card animate-on-scroll"><div class="support-icon"><i class="fas fa-envelope"></i></div><h3>Email Support</h3><p>Send us a detailed message and we'll get back to you within 24 hours.</p><button class="btn btn-outline btn-sm">Contact Us</button></div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="#" class="logo"><div class="logo-icon"><i class="fas fa-gamepad"></i></div><span class="logo-text"><b><?= htmlspecialchars($site_name) ?></b></span></a>
                <p>Next generation game enhancement platform. Premium tools for serious gamers.</p>
            </div>
            <div class="footer-links">
                <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="#features">Features</a></li><li><a href="#software">Software</a></li><li><a href="pricing.php">Pricing</a></li><li><a href="#download">Download</a></li></ul></div>
                <div class="footer-col"><h4>Support</h4><ul><li><a href="#support">Help Center</a></li><li><a href="#" onclick="document.getElementById('chatWidget').classList.add('chat-open'); return false;">Live Chat</a></li><li><a href="#">FAQ</a></li></ul></div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All rights reserved.</p>
            <div class="footer-social"><a href="#"><i class="fab fa-discord"></i></a><a href="#"><i class="fab fa-telegram"></i></a><a href="#"><i class="fab fa-github"></i></a></div>
        </div>
    </div>
</footer>

<button class="scroll-top" id="scrollTop"><i class="fas fa-arrow-up"></i></button>

<!-- Chat Widget -->
<div class="chat-widget" id="chatWidget">
    <button class="chat-toggle" id="chatToggle"><i class="fas fa-comment-dots"></i><span class="chat-badge" id="chatBadge">0</span></button>
    <div class="chat-window" id="chatWindow">
        <div class="chat-header"><div class="chat-header-info"><div class="chat-header-avatar"><i class="fas fa-headset"></i></div><div class="chat-header-text"><h4>Live Support</h4><span>Online</span></div></div><button class="chat-close" id="chatClose"><i class="fas fa-times"></i></button></div>
        <div class="chat-messages" id="chatMessages"><div class="chat-message admin"><p>Hello! How can we help you today?</p></div></div>
        <div class="chat-input-area"><textarea class="chat-input" id="chatInput" placeholder="Type your message..." rows="1"></textarea><button class="chat-send" id="chatSend"><i class="fas fa-paper-plane"></i></button></div>
    </div>
</div>

<!-- Software Detail Modal -->
<div id="sdOverlay" class="sd-overlay">
    <div class="sd-modal">
        <button class="sd-close">&times;</button>
        <div class="sd-body"></div>
    </div>
</div>

<!-- Archive Password Modal -->
<div id="archivePasswordModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:99999;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);">
    <div style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);border-radius:20px;padding:40px;max-width:480px;width:100%;text-align:center;border:1px solid rgba(108,92,231,0.3);box-shadow:0 25px 60px rgba(0,0,0,0.5);animation:apmBounceIn 0.5s cubic-bezier(0.68,-0.55,0.27,1.55);">
        <div style="width:70px;height:70px;background:linear-gradient(135deg,#6c5ce7,#a29bfe);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:30px;color:#fff;box-shadow:0 8px 25px rgba(108,92,231,0.4);"><i class="fas fa-lock"></i></div>
        <h3 style="color:#fff;font-size:22px;font-weight:700;margin-bottom:8px;">Archive Password</h3>
        <p style="color:rgba(255,255,255,0.6);font-size:14px;margin-bottom:24px;">Use this password to extract the downloaded archive</p>
        <div id="archivePasswordDisplay" style="background:rgba(108,92,231,0.15);border:2px solid rgba(108,92,231,0.4);border-radius:12px;padding:16px 20px;margin-bottom:20px;position:relative;cursor:pointer;transition:all 0.3s;" onclick="window._copyArchivePass()">
            <span id="archivePasswordText" style="font-family:'JetBrains Mono',monospace;font-size:20px;font-weight:600;color:#a29bfe;letter-spacing:2px;user-select:all;"></span>
            <div style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.4);font-size:14px;"><i class="fas fa-copy"></i></div>
        </div>
        <p id="archiveCopyHint" style="color:rgba(255,255,255,0.4);font-size:12px;margin-bottom:24px;transition:all 0.3s;"><i class="fas fa-info-circle"></i> Click password to copy</p>
        <button id="archivePasswordOkBtn" style="padding:14px 40px;background:linear-gradient(135deg,#6c5ce7,#a29bfe);color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;font-family:'Inter',sans-serif;box-shadow:0 4px 15px rgba(108,92,231,0.3);">OK, Got It!</button>
    </div>
</div>
<style>
@keyframes apmBounceIn{0%{opacity:0;transform:scale(0.3)}50%{opacity:1;transform:scale(1.05)}70%{transform:scale(0.95)}100%{transform:scale(1)}}
#archivePasswordOkBtn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(108,92,231,0.4);}
</style>

<!-- Promo Modal -->
<?php if ($promo_enabled === '1'): ?>
<div class="promo-overlay" id="promoOverlay">
    <div class="promo-modal <?= htmlspecialchars($promo_animation) ?>" id="promoModal">
        <button class="promo-close" id="promoClose"><i class="fas fa-times"></i></button>
        <div class="promo-particles" id="promoParticles"></div>
        <div class="promo-content">
            <div class="promo-icon"><i class="fas fa-fire"></i></div>
            <h2 class="promo-title"><?= htmlspecialchars($promo_title) ?></h2>
            <p class="promo-text"><?= htmlspecialchars($promo_text) ?></p>
            <div class="promo-countdown" id="promoCountdown">
                <div class="countdown-item"><span class="countdown-number" id="countMinutes">00</span><span class="countdown-label">Min</span></div>
                <div class="countdown-sep">:</div>
                <div class="countdown-item"><span class="countdown-number" id="countSeconds">00</span><span class="countdown-label">Sec</span></div>
            </div>
            <a href="<?= htmlspecialchars($promo_btn_link) ?>" class="promo-btn" id="promoBtn"><?= htmlspecialchars($promo_btn_text) ?></a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    window.SITE_CONFIG = {
        promoEnabled: <?= $promo_enabled === '1' ? 'true' : 'false' ?>,
        promoDelay: <?= (int)$promo_delay ?>,
        promoCountdown: <?= (int)$promo_countdown ?>,
        gamesPerPage: 12,
        archivePassword: <?= json_encode(get_setting('archive_password') ?? '') ?>
    };
    try {
        window.GAMES_DATA = JSON.parse(<?= json_encode(json_encode($games_json, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>);
    } catch(e) {
        console.error('GAMES_DATA parse error:', e);
        window.GAMES_DATA = [];
    }
</script>

<script src="js/landing.js"></script>
<script src="js/chat.js"></script>
</body>
</html>