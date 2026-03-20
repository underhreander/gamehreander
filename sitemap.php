<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/admin/db_connect.php';

$games = function_exists('get_games_list') ? get_games_list() : [];
$base = 'https://softmaster.pro';
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $base ?>/</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $base ?>/pricing.php</loc>
        <lastmod>2026-03-13</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
<?php foreach ($games as $game): ?>
<?php if (!empty($game['slug'])): ?>
    <url>
        <loc><?= $base ?>/software/<?= htmlspecialchars($game['slug']) ?></loc>
        <lastmod><?= !empty($game['updated_at']) ? date('Y-m-d', strtotime($game['updated_at'])) : $today ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endif; ?>
<?php endforeach; ?>
</urlset>