<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/admin/db_connect.php';
require_once __DIR__ . '/geoblock.php';

$site_name = get_setting('site_name') ?? 'SoftMaster';
$download_link = get_setting('download_link') ?? '#';

$plans = [
    [
        'id' => 'basic',
        'name' => 'Basic',
        'desc' => '1 User License',
        'icon' => 'fas fa-user',
        'prices' => [
            'USD' => 9.99, 'EUR' => 9.49, 'GBP' => 7.99,
            'CAD' => 13.49, 'AUD' => 15.49, 'JPY' => 1500,
            'CHF' => 8.99, 'SEK' => 109, 'NOK' => 109,
            'DKK' => 69.99
        ],
        'features' => [
            ['text' => '1 Device', 'included' => true],
            ['text' => 'All Software Access', 'included' => true],
            ['text' => 'Auto Updates', 'included' => true],
            ['text' => 'Email Support', 'included' => true],
            ['text' => 'Priority Support', 'included' => false],
            ['text' => 'Custom Configs', 'included' => false],
            ['text' => 'Team Management', 'included' => false],
        ],
        'popular' => false
    ],
    [
        'id' => 'home',
        'name' => 'Home / Family',
        'desc' => 'Up to 3 Users',
        'icon' => 'fas fa-home',
        'prices' => [
            'USD' => 19.99, 'EUR' => 18.99, 'GBP' => 15.99,
            'CAD' => 26.99, 'AUD' => 30.99, 'JPY' => 2980,
            'CHF' => 17.99, 'SEK' => 209, 'NOK' => 209,
            'DKK' => 139.99
        ],
        'features' => [
            ['text' => 'Up to 3 Devices', 'included' => true],
            ['text' => 'All Software Access', 'included' => true],
            ['text' => 'Auto Updates', 'included' => true],
            ['text' => 'Email Support', 'included' => true],
            ['text' => 'Priority Support', 'included' => true],
            ['text' => 'Custom Configs', 'included' => true],
            ['text' => 'Team Management', 'included' => false],
        ],
        'popular' => true
    ],
    [
        'id' => 'corporate',
        'name' => 'Corporate',
        'desc' => 'Unlimited Users',
        'icon' => 'fas fa-building',
        'prices' => [
            'USD' => 49.99, 'EUR' => 47.99, 'GBP' => 39.99,
            'CAD' => 67.99, 'AUD' => 77.99, 'JPY' => 7500,
            'CHF' => 44.99, 'SEK' => 529, 'NOK' => 529,
            'DKK' => 349.99
        ],
        'features' => [
            ['text' => 'Unlimited Devices', 'included' => true],
            ['text' => 'All Software Access', 'included' => true],
            ['text' => 'Auto Updates', 'included' => true],
            ['text' => 'Email Support', 'included' => true],
            ['text' => 'Priority Support', 'included' => true],
            ['text' => 'Custom Configs', 'included' => true],
            ['text' => 'Team Management', 'included' => true],
        ],
        'popular' => false
    ]
];

$currencies = [
    'USD' => ['symbol' => '$', 'name' => 'US Dollar'],
    'EUR' => ['symbol' => '€', 'name' => 'Euro'],
    'GBP' => ['symbol' => '£', 'name' => 'British Pound'],
    'CAD' => ['symbol' => 'C$', 'name' => 'Canadian Dollar'],
    'AUD' => ['symbol' => 'A$', 'name' => 'Australian Dollar'],
    'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen'],
    'CHF' => ['symbol' => 'Fr', 'name' => 'Swiss Franc'],
    'SEK' => ['symbol' => 'kr', 'name' => 'Swedish Krona'],
    'NOK' => ['symbol' => 'kr', 'name' => 'Norwegian Krone'],
    'DKK' => ['symbol' => 'kr', 'name' => 'Danish Krone'],
];

$payment_methods = [
    ['id' => 'visa', 'name' => 'Visa', 'icon' => 'fab fa-cc-visa'],
    ['id' => 'mastercard', 'name' => 'Mastercard', 'icon' => 'fab fa-cc-mastercard'],
    ['id' => 'amex', 'name' => 'Amex', 'icon' => 'fab fa-cc-amex'],
    ['id' => 'discover', 'name' => 'Discover', 'icon' => 'fab fa-cc-discover'],
    ['id' => 'paypal', 'name' => 'PayPal', 'icon' => 'fab fa-cc-paypal'],
    ['id' => 'apple_pay', 'name' => 'Apple Pay', 'icon' => 'fab fa-cc-apple-pay'],
    ['id' => 'google_pay', 'name' => 'Google Pay', 'icon' => 'fab fa-google-pay'],
    ['id' => 'bitcoin', 'name' => 'Bitcoin', 'icon' => 'fab fa-bitcoin'],
    ['id' => 'ethereum', 'name' => 'Ethereum', 'icon' => 'fab fa-ethereum'],
    ['id' => 'crypto', 'name' => 'Other Crypto', 'icon' => 'fas fa-coins'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing — <?= htmlspecialchars($site_name) ?></title>
    <meta name="description" content="Choose your SoftMaster plan. Get unlimited access to a 50+ premium apps starting from $9.99/month. Multiple payment methods accepted.">
    <meta name="keywords" content="software subscription, cheap software, photoshop license, sony vegas buy, affordable software plans, software pricing, premium apps subscription, SoftMaster pricing">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://softmaster.pro/pricing.php">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://softmaster.pro/pricing.php">
    <meta property="og:title" content="SoftMaster Pricing — Affordable Plans for Premium Software">
    <meta property="og:description" content="Access 50+ full-version premium apps. Plans starting from $9.99/month. Visa, PayPal, Crypto accepted.">
    <meta property="og:image" content="https://softmaster.pro/img/og-cover.png">
    <meta property="og:site_name" content="SoftMaster">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SoftMaster Pricing — Premium Software Plans">
    <meta name="twitter:description" content="Access 50+ premium apps from $9.99/month.">
    <meta name="twitter:image" content="https://softmaster.pro/img/og-cover.png">

    <meta name="theme-color" content="#0a0e1a">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/landing.css">
    <style>
        .pricing-hero {
            padding: 140px 0 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .pricing-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse at 50% 0%, var(--primary-glow) 0%, transparent 70%);
            pointer-events: none;
        }
        .pricing-hero h1 {
            font-size: clamp(28px, 4vw, 48px);
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--text-primary);
            letter-spacing: -1px;
        }
        .pricing-hero > .container > p {
            font-size: 17px;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }
        .currency-switcher {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 40px auto 50px;
            flex-wrap: wrap;
        }
        .currency-switcher label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .currency-select-wrapper {
            position: relative;
        }
        .currency-select {
            appearance: none;
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 48px 12px 20px;
            font-size: 15px;
            font-weight: 600;
            font-family: var(--font-main);
            cursor: pointer;
            transition: all var(--transition-base);
            min-width: 220px;
        }
        .currency-select:hover,
        .currency-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 20px var(--primary-glow);
            outline: none;
        }
        .currency-select option {
            background: var(--bg-card);
            color: var(--text-primary);
        }
        .currency-select-arrow {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
            pointer-events: none;
        }
        .pricing-section {
            padding: 0 0 120px;
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
            align-items: start;
        }
        .pricing-card {
            background: var(--gradient-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px 28px;
            position: relative;
            transition: all var(--transition-base);
            overflow: hidden;
        }
        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity var(--transition-base);
        }
        .pricing-card:hover {
            transform: translateY(-6px);
            border-color: var(--border-hover);
            box-shadow: var(--shadow-md);
        }
        .pricing-card:hover::before {
            opacity: 1;
        }
        .pricing-card.popular {
            border-color: rgba(37, 99, 235, 0.3);
            transform: scale(1.04);
            box-shadow: var(--shadow-md), 0 0 40px rgba(37, 99, 235, 0.1);
        }
        .pricing-card.popular:hover {
            transform: scale(1.04) translateY(-6px);
        }
        .pricing-card.popular::before {
            opacity: 1;
        }
        .popular-badge {
            position: absolute;
            top: 16px;
            right: -32px;
            background: var(--gradient-primary);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 6px 40px;
            transform: rotate(45deg);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .pricing-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
            background: rgba(37, 99, 235, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--primary-light);
            margin-bottom: 20px;
        }
        .pricing-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .pricing-desc {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        .pricing-price {
            margin-bottom: 28px;
        }
        .pricing-amount {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }
        .pricing-currency-symbol {
            font-size: 1.4rem;
            font-weight: 600;
            vertical-align: super;
            color: var(--primary-light);
        }
        .pricing-period {
            font-size: 14px;
            color: var(--text-muted);
            margin-left: 4px;
        }
        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 0 0 28px;
        }
        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            font-size: 14px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }
        .pricing-features li:last-child {
            border-bottom: none;
        }
        .pricing-features li i {
            font-size: 13px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        .pricing-features li i.fa-check {
            color: #22c55e;
        }
        .pricing-features li i.fa-times {
            color: var(--text-muted);
        }
        .pricing-features li.disabled {
            opacity: 0.4;
        }
        .pricing-btn {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 600;
            font-family: var(--font-main);
            cursor: pointer;
            transition: all var(--transition-base);
            text-align: center;
            text-decoration: none;
        }
        .pricing-btn-primary {
            background: var(--gradient-primary);
            color: #fff;
        }
        .pricing-btn-primary:hover {
            box-shadow: var(--shadow-glow);
            transform: translateY(-2px);
        }
        .pricing-btn-secondary {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-light);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }
        .pricing-btn-secondary:hover {
            background: rgba(37, 99, 235, 0.15);
            box-shadow: 0 4px 20px var(--primary-glow);
            transform: translateY(-2px);
        }
        .payment-section {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
            padding: 0 24px 120px;
        }
        .payment-section h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        .payment-section > p {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 15px;
        }
        .payment-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }
        .payment-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            transition: all var(--transition-base);
        }
        .payment-item:hover {
            border-color: var(--border-hover);
            transform: translateY(-3px);
            box-shadow: var(--shadow-sm);
        }
        .payment-item i {
            font-size: 1.5rem;
            color: var(--primary-light);
        }
        .payment-item span {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }
        .purchase-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .purchase-overlay.active {
            display: flex;
        }
        .purchase-modal {
            background: var(--bg-card);
            border: 1px solid var(--border-hover);
            border-radius: var(--radius-xl);
            padding: 40px;
            max-width: 520px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: purchaseIn 0.4s ease;
        }
        @keyframes purchaseIn {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .purchase-modal-close {
            position: absolute;
            top: 16px; right: 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text-muted);
            width: 36px; height: 36px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .purchase-modal-close:hover {
            background: rgba(239,68,68,0.1);
            color: #ef4444;
            border-color: rgba(239,68,68,0.3);
        }
        .purchase-modal h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .purchase-plan-info {
            font-size: 14px;
            color: var(--primary-light);
            font-weight: 600;
            margin-bottom: 24px;
            padding: 12px 16px;
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.15);
            border-radius: var(--radius-sm);
        }
        .purchase-payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 24px;
        }
        .purchase-payment-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-family: var(--font-main);
            font-size: 13px;
        }
        .purchase-payment-btn:hover {
            border-color: var(--border-hover);
            background: rgba(37, 99, 235, 0.05);
        }
        .purchase-payment-btn.selected {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
            color: var(--text-primary);
        }
        .purchase-payment-btn i {
            font-size: 1.2rem;
            color: var(--primary-light);
        }
        .purchase-message-area {
            margin-bottom: 20px;
        }
        .purchase-message-area label {
            display: block;
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 500;
        }
        .purchase-textarea {
            width: 100%;
            min-height: 90px;
            padding: 12px 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-family: var(--font-main);
            font-size: 14px;
            resize: vertical;
            transition: border-color var(--transition-fast);
        }
        .purchase-textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        .purchase-textarea::placeholder {
            color: var(--text-muted);
        }
        .purchase-submit {
            width: 100%;
            padding: 14px;
            background: var(--gradient-primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 600;
            font-family: var(--font-main);
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .purchase-submit:hover {
            box-shadow: var(--shadow-glow);
            transform: translateY(-2px);
        }
        .purchase-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .purchase-success {
            display: none;
            text-align: center;
            padding: 20px 0;
        }
        .purchase-success.active {
            display: block;
        }
        .purchase-success i {
            font-size: 3rem;
            color: #22c55e;
            margin-bottom: 16px;
        }
        .purchase-success h4 {
            font-size: 1.3rem;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .purchase-success p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 420px;
                margin: 0 auto;
            }
            .pricing-card.popular { transform: none; }
            .pricing-card.popular:hover { transform: translateY(-6px); }
            .purchase-payment-methods { grid-template-columns: 1fr; }
            .purchase-modal { padding: 28px 20px; }
        }
        @media (max-width: 480px) {
            .pricing-hero { padding: 120px 0 40px; }
            .pricing-amount { font-size: 2.2rem; }
            .payment-item { padding: 10px 14px; }
        }
    </style>
</head>
<body>

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
                <li><a href="pricing.php" class="nav-link nav-cta" style="background:rgba(37,99,235,0.15);color:var(--primary-light);">Pricing</a></li>
                <li><a href="/#support" class="nav-link">Support</a></li>
                <li><a href="/#download" class="nav-link nav-cta">Download</a></li>
            </ul>
        </nav>
        <button class="mobile-menu-btn" id="mobileToggle">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<!-- Pricing Hero -->
<section class="pricing-hero">
    <div class="container">
        <h1>Choose Your <span class="gradient-text">Plan</span></h1>
        <p>Flexible pricing options to fit every gamer's needs. Cancel anytime.</p>
        <div class="currency-switcher">
            <label for="currencySelect"><i class="fas fa-globe"></i> Currency:</label>
            <div class="currency-select-wrapper">
                <select class="currency-select" id="currencySelect">
                    <?php foreach ($currencies as $code => $info): ?>
                        <option value="<?= $code ?>" <?= $code === 'USD' ? 'selected' : '' ?>>
                            <?= $info['symbol'] ?> <?= $code ?> — <?= $info['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="currency-select-arrow"><i class="fas fa-chevron-down"></i></span>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Cards -->
<section class="pricing-section">
    <div class="container">
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="pricing-card <?= $plan['popular'] ? 'popular' : '' ?>" data-plan="<?= $plan['id'] ?>">
                    <?php if ($plan['popular']): ?>
                        <div class="popular-badge">Most Popular</div>
                    <?php endif; ?>
                    <div class="pricing-icon"><i class="<?= $plan['icon'] ?>"></i></div>
                    <div class="pricing-name"><?= htmlspecialchars($plan['name']) ?></div>
                    <div class="pricing-desc"><?= htmlspecialchars($plan['desc']) ?></div>
                    <div class="pricing-price">
                        <?php foreach ($currencies as $code => $info): ?>
                            <div class="price-value" data-currency="<?= $code ?>" style="<?= $code !== 'USD' ? 'display:none;' : '' ?>">
                                <span class="pricing-currency-symbol"><?= $info['symbol'] ?></span>
                                <span class="pricing-amount"><?= ($code === 'JPY') ? number_format($plan['prices'][$code], 0) : number_format($plan['prices'][$code], 2) ?></span>
                                <span class="pricing-period">/ month</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <ul class="pricing-features">
                        <?php foreach ($plan['features'] as $f): ?>
                            <li class="<?= $f['included'] ? '' : 'disabled' ?>">
                                <i class="fas <?= $f['included'] ? 'fa-check' : 'fa-times' ?>"></i>
                                <?= htmlspecialchars($f['text']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button class="pricing-btn <?= $plan['popular'] ? 'pricing-btn-primary' : 'pricing-btn-secondary' ?>"
                            onclick="openPurchase('<?= $plan['id'] ?>', '<?= htmlspecialchars($plan['name']) ?>')">
                        Get Started
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Payment Methods -->
<div class="payment-section">
    <h3>Accepted <span class="gradient-text">Payment Methods</span></h3>
    <p>We support a wide variety of secure payment options</p>
    <div class="payment-grid">
        <?php foreach ($payment_methods as $pm): ?>
            <div class="payment-item">
                <i class="<?= $pm['icon'] ?>"></i>
                <span><?= htmlspecialchars($pm['name']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Purchase Modal -->
<div class="purchase-overlay" id="purchaseOverlay">
    <div class="purchase-modal">
        <button class="purchase-modal-close" id="purchaseClose"><i class="fas fa-times"></i></button>
        <div id="purchaseForm">
            <h3>Complete Your Purchase</h3>
            <div class="purchase-plan-info" id="purchasePlanInfo"></div>
            <p style="color:var(--text-secondary);font-size:14px;margin-bottom:14px;font-weight:500;">Select Payment Method:</p>
            <div class="purchase-payment-methods">
                <?php foreach ($payment_methods as $pm): ?>
                    <button class="purchase-payment-btn" data-method="<?= $pm['id'] ?>" data-name="<?= htmlspecialchars($pm['name']) ?>">
                        <i class="<?= $pm['icon'] ?>"></i>
                        <?= htmlspecialchars($pm['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="purchase-message-area">
                <label for="purchaseMessage">Additional message (optional):</label>
                <textarea class="purchase-textarea" id="purchaseMessage" placeholder="Any special requests or questions..."></textarea>
            </div>
            <button class="purchase-submit" id="purchaseSubmit" disabled>
                <i class="fas fa-lock"></i> Submit Purchase Request
            </button>
        </div>
        <div class="purchase-success" id="purchaseSuccess">
            <i class="fas fa-check-circle"></i>
            <h4>Request Submitted!</h4>
            <p>Your purchase request has been sent. Our team will contact you shortly via the support chat.</p>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="/" class="logo">
                    <div class="logo-icon"><i class="fas fa-gamepad"></i></div>
                    <span class="logo-text"><b><?= htmlspecialchars($site_name) ?></b></span>
                </a>
                <p>Next generation game enhancement platform.</p>
            </div>
            <div class="footer-links">
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/#features">Features</a></li>
                        <li><a href="/#software">Software</a></li>
                        <li><a href="pricing.php">Pricing</a></li>
                        <li><a href="/#download">Download</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="/#support">Help Center</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All rights reserved.</p>
            <div class="footer-social">
                <a href="#"><i class="fab fa-discord"></i></a>
                <a href="#"><i class="fab fa-telegram"></i></a>
            </div>
        </div>
    </div>
</footer>

<!-- Chat Widget -->
<div class="chat-widget" id="chatWidget">
    <button class="chat-toggle" id="chatToggle">
        <i class="fas fa-comment-dots"></i>
        <span class="chat-badge" id="chatBadge">0</span>
    </button>
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div class="chat-header-info">
                <div class="chat-header-avatar"><i class="fas fa-headset"></i></div>
                <div class="chat-header-text">
                    <h4>Live Support</h4>
                    <span>Online</span>
                </div>
            </div>
            <button class="chat-close" id="chatClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="chat-message admin"><p>Hello! How can we help you today?</p></div>
        </div>
        <div class="chat-input-area">
            <textarea class="chat-input" id="chatInput" placeholder="Type your message..." rows="1"></textarea>
            <button class="chat-send" id="chatSend"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
const currencySelect = document.getElementById('currencySelect');
let currentCurrency = 'USD';
let selectedPlan = { id: '', name: '', price: '', currency: 'USD' };
let selectedPayment = '';

currencySelect.addEventListener('change', function() {
    currentCurrency = this.value;
    document.querySelectorAll('.price-value').forEach(el => {
        el.style.display = el.dataset.currency === currentCurrency ? '' : 'none';
    });
});

window.addEventListener('scroll', () => {
    const h = document.getElementById('header');
    if (h) h.classList.toggle('scrolled', window.scrollY > 50);
});

const mobileToggle = document.getElementById('mobileToggle');
const mainNav = document.getElementById('mainNav');
if (mobileToggle && mainNav) {
    mobileToggle.addEventListener('click', () => {
        mobileToggle.classList.toggle('active');
        mainNav.classList.toggle('open');
    });
}

function openPurchase(planId, planName) {
    const card = document.querySelector('.pricing-card[data-plan="' + planId + '"]');
    const priceEl = card.querySelector('.price-value[data-currency="' + currentCurrency + '"]');
    const symbol = priceEl.querySelector('.pricing-currency-symbol').textContent;
    const amount = priceEl.querySelector('.pricing-amount').textContent;
    selectedPlan = { id: planId, name: planName, price: symbol + amount, currency: currentCurrency };
    document.getElementById('purchasePlanInfo').innerHTML =
        '<i class="fas fa-tag"></i> ' + planName + ' — <strong>' + symbol + amount + '</strong> ' + currentCurrency + '/month';
    document.getElementById('purchaseForm').style.display = '';
    document.getElementById('purchaseSuccess').classList.remove('active');
    document.getElementById('purchaseSubmit').disabled = true;
    selectedPayment = '';
    document.querySelectorAll('.purchase-payment-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('purchaseMessage').value = '';
    document.getElementById('purchaseOverlay').classList.add('active');
}

document.getElementById('purchaseClose').addEventListener('click', () => {
    document.getElementById('purchaseOverlay').classList.remove('active');
});
document.getElementById('purchaseOverlay').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) e.currentTarget.classList.remove('active');
});

document.querySelectorAll('.purchase-payment-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.purchase-payment-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
        selectedPayment = this.dataset.name;
        document.getElementById('purchaseSubmit').disabled = false;
    });
});

document.getElementById('purchaseSubmit').addEventListener('click', async function() {
    if (!selectedPayment) return;
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    let sessionHash = localStorage.getItem('chat_session_hash');
    if (!sessionHash) {
        sessionHash = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
        localStorage.setItem('chat_session_hash', sessionHash);
    }

    const message = document.getElementById('purchaseMessage').value.trim() || 'I would like to purchase this plan.';
    const btn = this;

    try {
        const params = new URLSearchParams();
        params.append('action', 'send_purchase');
        params.append('session_hash', sessionHash);
        params.append('plan_name', selectedPlan.name);
        params.append('plan_price', selectedPlan.price);
        params.append('currency', selectedPlan.currency);
        params.append('payment_method', selectedPayment);
        params.append('message', message);

        const resp = await fetch('/admin/api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });

        const text = await resp.text();
        console.log('Server response:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert('Server error: ' + text.substring(0, 200));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Submit Purchase Request';
            return;
        }

        if (data.success) {
            document.getElementById('purchaseForm').style.display = 'none';
            document.getElementById('purchaseSuccess').classList.add('active');
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Submit Purchase Request';
        }
    } catch (err) {
        console.error('Purchase error:', err);
        alert('Network error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Submit Purchase Request';
    }
});

// Chat toggle
const chatToggle = document.getElementById('chatToggle');
const chatWindow = document.getElementById('chatWindow');
const chatClose = document.getElementById('chatClose');
if (chatToggle && chatWindow) {
    chatToggle.addEventListener('click', () => chatWindow.classList.toggle('open'));
}
if (chatClose && chatWindow) {
    chatClose.addEventListener('click', () => chatWindow.classList.remove('open'));
}
</script>
<script src="js/chat.js"></script>
</body>
</html>