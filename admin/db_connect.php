<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/* ============================================
   SOFTMASTER — DATABASE CONNECTION & HELPERS
   ============================================ */

define('DB_HOST', 'localhost');
define('DB_USER', 'vh19747_root');
define('DB_PASS', 'Jwc31rgZ28');
define('DB_NAME', 'vh19747_gamehublauncher');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

/* ------------------------------------------
   GET REAL IP (proxy-aware)
   ------------------------------------------ */
function get_real_ip() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_REAL_IP',            // nginx proxy
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',      // стандартный proxy
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
    ];

    foreach ($headers as $key) {
        if (!empty($_SERVER[$key])) {
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

/* ------------------------------------------
   VISITS
   ------------------------------------------ */
function log_visit() {
    global $conn;
    $ip      = get_real_ip();
    $ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $page    = $_SERVER['REQUEST_URI'] ?? '/';
    $ref     = $_SERVER['HTTP_REFERER'] ?? '';
    $country = function_exists('get_country_from_ip') ? get_country_from_ip($ip) : 'Unknown';
    $stmt = $conn->prepare("INSERT INTO visits (ip_address, user_agent, page_visited, referrer, country) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) return;
    $stmt->bind_param('sssss', $ip, $ua, $page, $ref, $country);
    $stmt->execute();
    $stmt->close();
}

function get_country_from_ip($ip) {
    if (in_array($ip, ['127.0.0.1','::1','localhost'])) return 'Localhost';
    $apis = [
        "http://ip-api.com/json/{$ip}?fields=country",
        "https://ipapi.co/{$ip}/json/",
        "https://ipwhois.app/json/{$ip}"
    ];
    foreach ($apis as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            if (!empty($data['country'])) return $data['country'];
            if (!empty($data['country_name'])) return $data['country_name'];
        }
    }
    return 'Unknown';
}

/* ------------------------------------------
   SITE STATS
   ------------------------------------------ */
function get_site_stats() {
    global $conn;
    $r = $conn->query("SELECT COUNT(*) as cnt FROM visits");
    $visits = ($r) ? ($r->fetch_assoc()['cnt'] ?? 0) : 0;
    $r = $conn->query("SELECT COUNT(*) as cnt FROM downloads");
    $downloads = ($r) ? ($r->fetch_assoc()['cnt'] ?? 0) : 0;
    return ['total_visits' => $visits, 'total_downloads' => $downloads];
}

/* ------------------------------------------
   GAMES (SOFTWARE)
   ------------------------------------------ */
function get_games_list() {
    global $conn;
    $r = $conn->query("SELECT * FROM games WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
    $out = [];
    if ($r) { while ($row = $r->fetch_assoc()) $out[] = $row; }
    return $out;
}

function generate_unique_slug($name, $exclude_id = 0) {
    global $conn;
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    if ($slug === '') $slug = 'software';
    $base = $slug;
    $i = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM games WHERE slug = ? AND id != ? LIMIT 1");
        if (!$stmt) break;
        $stmt->bind_param('si', $slug, $exclude_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$exists) break;
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

function add_game($name, $image = '', $category_id = null, $display_order = 0, $extra = []) {
    global $conn;
    $slug        = generate_unique_slug($name);
    $description = $extra['description'] ?? null;
    $meta_title  = $extra['meta_title'] ?? null;
    $meta_desc   = $extra['meta_description'] ?? null;
    $version     = $extra['version'] ?? null;
    $developer   = $extra['developer'] ?? null;
    $features    = $extra['features'] ?? null;
    $sys_req     = $extra['system_requirements'] ?? null;

    $stmt = $conn->prepare("INSERT INTO games (name, image, category_id, slug, description, meta_title, meta_description, version, developer, features, system_requirements, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
    if (!$stmt) return 0;
    $stmt->bind_param('ssissssssssi', $name, $image, $category_id, $slug, $description, $meta_title, $meta_desc, $version, $developer, $features, $sys_req, $display_order);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    log_admin_action('add_game', "Added: {$name}");
    return $id;
}

function delete_game($id) {
    global $conn;
    $game = get_game_by_id($id);
    $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
    if (!$stmt) return;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    if ($game) log_admin_action('delete_game', "Deleted: {$game['name']}");
}

function update_game($id, $data) {
    global $conn;
    $sets   = [];
    $types  = '';
    $values = [];
    foreach ($data as $k => $v) {
        $sets[]   = "`{$k}` = ?";
        $types   .= is_int($v) ? 'i' : 's';
        $values[] = $v;
    }
    $types   .= 'i';
    $values[] = $id;
    $sql  = "UPDATE games SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return;
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();
    log_admin_action('update_game', "Updated game ID: {$id}");
}

function get_game_by_id($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r;
}

function get_game_by_slug($slug) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM games WHERE slug = ? AND is_active = 1 LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r;
}

/* ------------------------------------------
   SCREENSHOTS
   ------------------------------------------ */
function get_game_screenshots($game_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM game_screenshots WHERE game_id = ? ORDER BY display_order ASC, id ASC");
    if (!$stmt) return [];
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $r = $stmt->get_result();
    $out = [];
    while ($row = $r->fetch_assoc()) $out[] = $row;
    $stmt->close();
    return $out;
}

function add_game_screenshot($game_id, $image_path, $order = 0) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO game_screenshots (game_id, image_path, display_order) VALUES (?, ?, ?)");
    if (!$stmt) return 0;
    $stmt->bind_param('isi', $game_id, $image_path, $order);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function delete_game_screenshot($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM game_screenshots WHERE id = ?");
    if (!$stmt) return;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

/* ------------------------------------------
   CATEGORIES
   ------------------------------------------ */
function get_categories() {
    global $conn;
    $r = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
    $out = [];
    if ($r) { while ($row = $r->fetch_assoc()) $out[] = $row; }
    return $out;
}

function get_all_categories() {
    global $conn;
    $r = $conn->query("SELECT * FROM categories ORDER BY display_order ASC, name ASC");
    $out = [];
    if ($r) { while ($row = $r->fetch_assoc()) $out[] = $row; }
    return $out;
}

function add_category($name, $slug, $order = 0) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, display_order, is_active) VALUES (?, ?, ?, 1)");
    if (!$stmt) return 0;
    $stmt->bind_param('ssi', $name, $slug, $order);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    log_admin_action('add_category', "Added category: {$name}");
    return $id;
}

function update_category($id, $name, $slug, $order = 0) {
    global $conn;
    $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, display_order=? WHERE id=?");
    if (!$stmt) return;
    $stmt->bind_param('ssii', $name, $slug, $order, $id);
    $stmt->execute();
    $stmt->close();
    log_admin_action('update_category', "Updated category: {$name}");
}

function delete_category($id) {
    global $conn;
    $cat = get_category_by_id($id);
    $stmt = $conn->prepare("UPDATE games SET category_id = NULL WHERE category_id = ?");
    if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
    if ($cat) log_admin_action('delete_category', "Deleted category: {$cat['name']}");
}

function get_category_by_id($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r;
}

function get_games_by_categories() {
    global $conn;
    $cats = get_categories();
    $result = [];
    foreach ($cats as $cat) {
        $stmt = $conn->prepare("SELECT * FROM games WHERE category_id = ? AND is_active = 1 ORDER BY display_order ASC, name ASC");
        if (!$stmt) { $cat['games'] = []; $result[] = $cat; continue; }
        $stmt->bind_param('i', $cat['id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $games = [];
        while ($row = $r->fetch_assoc()) $games[] = $row;
        $stmt->close();
        $cat['games'] = $games;
        $result[] = $cat;
    }
    return $result;
}

/* ------------------------------------------
   DOWNLOADS
   ------------------------------------------ */
function log_download($trial_code = '') {
    global $conn;
    $ip = get_real_ip();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $country = get_country_from_ip($ip);

    $stmt = $conn->prepare("SELECT id FROM downloads WHERE ip_address = ? AND DATE(download_time) = CURDATE() LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($exists) return false;

    $stmt = $conn->prepare("INSERT INTO downloads (ip_address, user_agent, trial_code, country) VALUES (?, ?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param('ssss', $ip, $ua, $trial_code, $country);
    $stmt->execute();
    $stmt->close();
    log_admin_action('download', "Download from IP: {$ip}");
    return true;
}

function get_unique_downloads() {
    global $conn;
    $r = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM downloads");
    return ($r) ? ($r->fetch_assoc()['cnt'] ?? 0) : 0;
}

function get_today_unique_downloads() {
    global $conn;
    $r = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM downloads WHERE DATE(download_time) = CURDATE()");
    return ($r) ? ($r->fetch_assoc()['cnt'] ?? 0) : 0;
}

function get_download_link() {
    return get_setting('download_link');
}

function has_downloaded_today() {
    global $conn;
    $ip = get_real_ip();
    $stmt = $conn->prepare("SELECT id FROM downloads WHERE ip_address = ? AND DATE(download_time) = CURDATE() LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $r = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $r;
}

function get_total_downloads() {
    global $conn;
    $r = $conn->query("SELECT COUNT(*) as cnt FROM downloads");
    return ($r) ? ($r->fetch_assoc()['cnt'] ?? 0) : 0;
}

function get_today_downloads() {
    global $conn;
    $r = $conn->query("SELECT COUNT(*) as cnt FROM downloads WHERE DATE(download_time) = CURDATE()");
    return ($r) ? ($r->fetch_assoc()['cnt'] ?? 0) : 0;
}

/* ------------------------------------------
   ADMIN AUTH & LOGGING
   ------------------------------------------ */
function admin_login($username, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $conn->query("UPDATE admins SET last_login = NOW() WHERE id = {$admin['id']}");
        log_admin_action('login', "Admin '{$username}' logged in");
        return $admin;
    }
    return false;
}

function log_admin_action($action, $details = '') {
    global $conn;
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $ip = get_real_ip();
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if (!$stmt) return;
    $stmt->bind_param('isss', $admin_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

function get_admin_stats() {
    global $conn;
    $stats = [
        'total_visits' => 0,
        'today_visits' => 0,
        'unique_visits' => 0,
        'today_unique_visits' => 0,
        'total_downloads' => 0,
        'today_downloads' => 0,
        'unique_downloads' => 0,
        'active_games' => 0,
    ];

    $r = $conn->query("SELECT COUNT(*) as cnt FROM visits");
    if ($r) $stats['total_visits'] = $r->fetch_assoc()['cnt'] ?? 0;

    $r = $conn->query("SELECT COUNT(*) as cnt FROM visits WHERE DATE(visit_time) = CURDATE()");
    if ($r) $stats['today_visits'] = $r->fetch_assoc()['cnt'] ?? 0;

    $r = $conn->query("SELECT COUNT(*) as cnt FROM downloads");
    if ($r) $stats['total_downloads'] = $r->fetch_assoc()['cnt'] ?? 0;

    $r = $conn->query("SELECT COUNT(*) as cnt FROM downloads WHERE DATE(download_time) = CURDATE()");
    if ($r) $stats['today_downloads'] = $r->fetch_assoc()['cnt'] ?? 0;

    $r = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM downloads");
    if ($r) $stats['unique_downloads'] = $r->fetch_assoc()['cnt'] ?? 0;

    $r = $conn->query("SELECT COUNT(*) as cnt FROM games WHERE is_active = 1");
    if ($r) $stats['active_games'] = $r->fetch_assoc()['cnt'] ?? 0;

    $r = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM visits");
    if ($r) $stats['unique_visits'] = $r->fetch_assoc()['cnt'] ?? 0;

    $r = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM visits WHERE DATE(visit_time) = CURDATE()");
    if ($r) $stats['today_unique_visits'] = $r->fetch_assoc()['cnt'] ?? 0;

    return $stats;
}

/* ------------------------------------------
   RECENT DATA
   ------------------------------------------ */
function get_recent_visits($limit = 20) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM visits ORDER BY visit_time DESC LIMIT ?");
    if (!$stmt) return [];
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $r = $stmt->get_result();
    $out = [];
    while ($row = $r->fetch_assoc()) $out[] = $row;
    $stmt->close();
    return $out;
}

function get_recent_downloads($limit = 20) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM downloads ORDER BY download_time DESC LIMIT ?");
    if (!$stmt) return [];
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $r = $stmt->get_result();
    $out = [];
    while ($row = $r->fetch_assoc()) $out[] = $row;
    $stmt->close();
    return $out;
}

function get_country_stats() {
    global $conn;
    $r = $conn->query("SELECT country, COUNT(*) as cnt FROM visits WHERE country IS NOT NULL AND country != '' GROUP BY country ORDER BY cnt DESC LIMIT 20");
    $out = [];
    if ($r) { while ($row = $r->fetch_assoc()) $out[] = $row; }
    return $out;
}

function get_daily_stats($days = 30) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT DATE(visit_time) as date, COUNT(*) as visits FROM visits
        WHERE visit_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(visit_time) ORDER BY date ASC
    ");
    $visits = [];
    if ($stmt) {
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) $visits[$row['date']] = (int)$row['visits'];
        $stmt->close();
    }

    $stmt = $conn->prepare("
        SELECT DATE(download_time) as date, COUNT(*) as downloads FROM downloads
        WHERE download_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(download_time) ORDER BY date ASC
    ");
    $downloads = [];
    if ($stmt) {
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) $downloads[$row['date']] = (int)$row['downloads'];
        $stmt->close();
    }

    $result = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $result[] = [
            'date'      => $date,
            'visits'    => $visits[$date] ?? 0,
            'downloads' => $downloads[$date] ?? 0,
        ];
    }
    return $result;
}

/* ------------------------------------------
   SETTINGS
   ------------------------------------------ */
function get_setting($key) {
    global $conn;
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['value'] : null;
}

function update_setting($key, $value) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
    if (!$stmt) return false;
    $stmt->bind_param('ss', $key, $value);
    $r = $stmt->execute();
    $stmt->close();
    return $r;
}
?>