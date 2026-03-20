<?php
/**
 * Динамический sitemap
 * Доступ: https://softmaster.pro/sitemap-generator.php
 * Можно вызывать по cron для обновления sitemap.xml
 */
require_once __DIR__ . '/admin/db_connect.php';

$domain = 'https://softmaster.pro';
$today = date('Y-m-d');

$urls = [
    ['loc' => $domain . '/',            'changefreq' => 'daily',  'priority' => '1.0'],
    ['loc' => $domain . '/pricing.php', 'changefreq' => 'weekly', 'priority' => '0.9'],
];

// Добавляем страницы категорий если они есть
$categories = function_exists('get_all_categories') ? get_all_categories() : [];
foreach ($categories as $cat) {
    $urls[] = [
        'loc'        => $domain . '/#software',
        'changefreq' => 'weekly',
        'priority'   => '0.7',
    ];
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$u['changefreq']}</changefreq>\n";
    echo "    <priority>{$u['priority']}</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
?>