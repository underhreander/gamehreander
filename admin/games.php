<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db_connect.php';

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ---------- helpers ---------- */
function get_all_games() {
    global $conn;
    $r = $conn->query("SELECT * FROM games ORDER BY display_order ASC, name ASC");
    $out = [];
    if ($r) { while ($row = $r->fetch_assoc()) $out[] = $row; }
    return $out;
}

function handle_image_upload($field = 'game_image', $url_field = 'game_image_url') {
    $image = '';
    if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/games/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','svg'];
        if (in_array($ext, $allowed)) {
            $fname = uniqid('game_') . '.' . $ext;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $fname)) {
                $image = 'images/games/' . $fname;
            }
        }
    }
    if (empty($image) && !empty($_POST[$url_field])) {
        $image = trim($_POST[$url_field]);
    }
    return $image;
}

function handle_screenshots_upload($game_id) {
    if (empty($_FILES['screenshots']['name'][0])) return;
    $upload_dir = '../images/games/screenshots/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $existing = get_game_screenshots($game_id);
    $order = count($existing);

    foreach ($_FILES['screenshots']['name'] as $i => $name) {
        if ($_FILES['screenshots']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $fname = uniqid('screen_') . '.' . $ext;
        if (move_uploaded_file($_FILES['screenshots']['tmp_name'][$i], $upload_dir . $fname)) {
            add_game_screenshot($game_id, 'images/games/screenshots/' . $fname, $order);
            $order++;
        }
    }
}

/* ---------- POST handlers ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF validation failed');
    }

    $action = $_POST['action'] ?? '';

    /* ===== CATEGORIES ===== */
    if ($action === 'add_category') {
        $name  = trim($_POST['category_name'] ?? '');
        $order = (int)($_POST['category_order'] ?? 0);
        if ($name !== '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
            add_category($name, $slug, $order);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => "Category «{$name}» created."];
        }
        header('Location: games.php'); exit;
    }

    if ($action === 'update_category') {
        $id    = (int)($_POST['category_id'] ?? 0);
        $name  = trim($_POST['category_name'] ?? '');
        $order = (int)($_POST['category_order'] ?? 0);
        if ($id && $name !== '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
            update_category($id, $name, $slug, $order);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => "Category «{$name}» updated."];
        }
        header('Location: games.php'); exit;
    }

    if ($action === 'delete_category') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id) {
            delete_category($id);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Category deleted.'];
        }
        header('Location: games.php'); exit;
    }

    /* ===== ADD SOFTWARE ===== */
    if ($action === 'add_game') {
        $name        = trim($_POST['game_name'] ?? '');
        $category_id = !empty($_POST['game_category']) ? (int)$_POST['game_category'] : null;
        $order       = (int)($_POST['display_order'] ?? 0);
        $image       = handle_image_upload();

        $extra = [
            'description'         => trim($_POST['game_description'] ?? '') ?: null,
            'meta_title'          => trim($_POST['game_meta_title'] ?? '') ?: null,
            'meta_description'    => trim($_POST['game_meta_desc'] ?? '') ?: null,
            'version'             => trim($_POST['game_version'] ?? '') ?: null,
            'developer'           => trim($_POST['game_developer'] ?? '') ?: null,
            'features'            => trim($_POST['game_features'] ?? '') ?: null,
            'system_requirements' => trim($_POST['game_sysreq'] ?? '') ?: null,
        ];

        if ($name !== '') {
            $game_id = add_game($name, $image, $category_id, $order, $extra);
            if ($game_id) {
                handle_screenshots_upload($game_id);
            }
            $_SESSION['alert'] = ['type' => 'success', 'msg' => "Software «{$name}» added."];
        }
        header('Location: games.php'); exit;
    }

    /* ===== DELETE SOFTWARE ===== */
    if ($action === 'delete_game') {
        $id = (int)($_POST['game_id'] ?? 0);
        if ($id) {
            delete_game($id);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Software deleted.'];
        }
        header('Location: games.php'); exit;
    }

    /* ===== DELETE SCREENSHOT ===== */
    if ($action === 'delete_screenshot') {
        $sid = (int)($_POST['screenshot_id'] ?? 0);
        if ($sid) {
            delete_game_screenshot($sid);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Screenshot deleted.'];
        }
        header('Location: games.php'); exit;
    }

    /* ===== UPDATE SOFTWARE ===== */
    if ($action === 'update_game') {
        $id          = (int)($_POST['game_id'] ?? 0);
        $name        = trim($_POST['game_name'] ?? '');
        $category_id = !empty($_POST['game_category']) ? (int)$_POST['game_category'] : null;
        $is_active   = isset($_POST['game_active']) ? 1 : 0;
        $order       = (int)($_POST['game_order'] ?? 0);
        $image       = handle_image_upload();

        if ($id && $name !== '') {
            $slug = generate_unique_slug($name, $id);
            $data = [
                'name'                => $name,
                'category_id'         => $category_id,
                'slug'                => $slug,
                'is_active'           => $is_active,
                'display_order'       => $order,
                'description'         => trim($_POST['game_description'] ?? '') ?: null,
                'meta_title'          => trim($_POST['game_meta_title'] ?? '') ?: null,
                'meta_description'    => trim($_POST['game_meta_desc'] ?? '') ?: null,
                'version'             => trim($_POST['game_version'] ?? '') ?: null,
                'developer'           => trim($_POST['game_developer'] ?? '') ?: null,
                'features'            => trim($_POST['game_features'] ?? '') ?: null,
                'system_requirements' => trim($_POST['game_sysreq'] ?? '') ?: null,
            ];
            if ($image !== '') {
                $data['image'] = $image;
            }
            update_game($id, $data);
            handle_screenshots_upload($id);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => "Software «{$name}» updated."];
        }
        header('Location: games.php'); exit;
    }
}

/* ---------- AJAX: get game data ---------- */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_game') {
    header('Content-Type: application/json');
    $id = (int)($_GET['id'] ?? 0);
    $game = get_game_by_id($id);
    if (!$game) { echo json_encode(['error' => 'Not found']); exit; }
    $game['screenshots'] = get_game_screenshots($id);
    $cat = $game['category_id'] ? get_category_by_id($game['category_id']) : null;
    $game['category_name'] = $cat ? $cat['name'] : 'Uncategorized';
    echo json_encode($game);
    exit;
}

/* ---------- Data ---------- */
$games      = get_all_games();
$categories = get_all_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Management — SoftMaster Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-container">

    <!-- ========== SIDEBAR ========== -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <i class="fas fa-cube"></i>
            <span><b>Soft</b>Master</span>
        </div>
        <nav class="admin-nav">
            <ul>
                <li><a href="control.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="games.php"><i class="fas fa-box"></i> Software</a></li>
                <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- ========== CONTENT ========== -->
    <main class="admin-content">
        <header class="admin-header">
            <h1><i class="fas fa-box"></i> Software Management</h1>
            <div class="header-actions">
                <button class="btn btn-primary" id="btnShowCategories" onclick="showSection('categories')">
                    <i class="fas fa-folder"></i> Categories
                </button>
                <button class="btn btn-primary active" id="btnShowSoftware" onclick="showSection('software')">
                    <i class="fas fa-th"></i> Software
                </button>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Software
                </button>
            </div>
        </header>

        <?php if (!empty($_SESSION['alert'])): ?>
            <div class="alert alert-<?= $_SESSION['alert']['type'] ?>">
                <i class="fas fa-<?= $_SESSION['alert']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($_SESSION['alert']['msg']) ?>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <!-- ===== CATEGORIES SECTION ===== -->
        <section id="categoriesSection" style="display:none;">
            <div class="content-card">
                <h2 class="card-title"><i class="fas fa-folder-plus"></i> Add Category</h2>
                <form method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="add_category">
                    <div style="flex:1; min-width:200px;">
                        <label class="form-label">Name</label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Video Editing" required>
                    </div>
                    <div style="width:120px;">
                        <label class="form-label">Order</label>
                        <input type="number" name="category_order" class="form-control" value="0" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height:42px;">
                        <i class="fas fa-plus"></i> Create
                    </button>
                </form>
            </div>
            <div class="content-card" style="margin-top:20px;">
                <h2 class="card-title"><i class="fas fa-list"></i> Categories</h2>
                <div class="categories-grid">
                    <?php if (empty($categories)): ?>
                        <p style="color:var(--admin-text-muted); grid-column:1/-1; text-align:center; padding:30px 0;">
                            <i class="fas fa-folder-open" style="font-size:2rem; display:block; margin-bottom:10px; opacity:0.4;"></i>
                            No categories yet.
                        </p>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <div class="category-card">
                                <div class="category-card-header">
                                    <span class="category-card-name"><i class="fas fa-folder" style="margin-right:6px; opacity:0.5;"></i><?= htmlspecialchars($cat['name']) ?></span>
                                    <span class="category-card-order">#<?= (int)$cat['display_order'] ?></span>
                                </div>
                                <div class="category-card-meta">Slug: <code><?= htmlspecialchars($cat['slug']) ?></code></div>
                                <div class="category-card-actions">
                                    <button type="button" class="btn btn-edit-cat" onclick="openEditCategoryModal(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>', <?= (int)$cat['display_order'] ?>)">
                                        <i class="fas fa-pen"></i> Edit
                                    </button>
                                    <form method="POST" style="flex:1; display:flex;" onsubmit="return confirm('Delete «<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>»?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn btn-delete-cat" style="width:100%;"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ===== SOFTWARE SECTION ===== -->
        <section id="softwareSection">
            <div class="content-card">
                <h2 class="card-title"><i class="fas fa-th"></i> Software Catalog</h2>

                <div class="software-filter-tabs">
                    <?php
                    $total_games = count($games);
                    $uncategorized = 0;
                    foreach ($games as $g) { if (empty($g['category_id'])) $uncategorized++; }
                    ?>
                    <button class="filter-tab active" onclick="filterGames('all', this)">
                        <i class="fas fa-th"></i> All (<?= $total_games ?>)
                    </button>
                    <button class="filter-tab" onclick="filterGames('uncategorized', this)">
                        <i class="fas fa-folder-open"></i> Uncategorized (<?= $uncategorized ?>)
                    </button>
                    <?php foreach ($categories as $fcat): ?>
                        <button class="filter-tab" onclick="filterGames('cat-<?= $fcat['id'] ?>', this)">
                            <i class="fas fa-folder"></i> <?= htmlspecialchars($fcat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($games)): ?>
                    <div class="no-games">
                        <i class="fas fa-box-open"></i>
                        <p>No software added yet. Click "Add Software" to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="sw-cards-grid">
                        <?php foreach ($games as $game): ?>
                            <?php
                            $cat_name = 'Uncategorized';
                            if (!empty($game['category_id'])) {
                                foreach ($categories as $c) {
                                    if ($c['id'] == $game['category_id']) { $cat_name = $c['name']; break; }
                                }
                            }
                            $img_src = '';
                            if (!empty($game['image'])) {
                                $img_src = $game['image'];
                                if (strpos($img_src, 'http') !== 0) $img_src = '../' . ltrim($img_src, '/');
                            }
                            ?>
                            <div class="sw-card <?= $game['is_active'] ? '' : 'sw-card-inactive' ?>"
                                 data-category-id="<?= (int)($game['category_id'] ?? 0) ?>"
                                 onclick="openViewModal(<?= $game['id'] ?>)">
                                <div class="sw-card-img">
                                    <?php if ($img_src): ?>
                                        <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($game['name']) ?>">
                                    <?php else: ?>
                                        <div class="sw-card-noimg"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                    <?php if (!$game['is_active']): ?>
                                        <div class="sw-card-badge-inactive">Inactive</div>
                                    <?php endif; ?>
                                </div>
                                <div class="sw-card-body">
                                    <h3 class="sw-card-title"><?= htmlspecialchars($game['name']) ?></h3>
                                    <span class="sw-card-cat"><i class="fas fa-folder"></i> <?= htmlspecialchars($cat_name) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>
</div>

<!-- ========== VIEW/EDIT MODAL ========== -->
<div class="modal-overlay" id="viewModal" style="display:none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="viewModalTitle"><i class="fas fa-box"></i> Software Details</h3>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <div class="modal-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>
</div>

<!-- ========== ADD SOFTWARE MODAL ========== -->
<div class="modal-overlay" id="addModal" style="display:none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Software</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="add_game">
            <div class="modal-body modal-form-body">
                <div class="mf-row">
                    <div class="mf-col">
                        <label class="form-label">Software Name *</label>
                        <input type="text" name="game_name" class="form-control" required placeholder="e.g. Adobe Photoshop">
                    </div>
                    <div class="mf-col">
                        <label class="form-label">Category</label>
                        <select name="game_category" class="form-control">
                            <option value="">— Uncategorized —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mf-row">
                    <div class="mf-col">
                        <label class="form-label">Version</label>
                        <input type="text" name="game_version" class="form-control" placeholder="e.g. 2024 v25.0">
                    </div>
                    <div class="mf-col">
                        <label class="form-label">Developer</label>
                        <input type="text" name="game_developer" class="form-control" placeholder="e.g. Adobe Inc.">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="game_description" class="form-control" rows="4" placeholder="Detailed description of the software..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Features (one per line)</label>
                    <textarea name="game_features" class="form-control" rows="3" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">System Requirements</label>
                    <textarea name="game_sysreq" class="form-control" rows="3" placeholder="Windows 10/11, 8GB RAM..."></textarea>
                </div>
                <div class="mf-row">
                    <div class="mf-col">
                        <label class="form-label">Cover Image (upload)</label>
                        <input type="file" name="game_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mf-col">
                        <label class="form-label">— or Image URL</label>
                        <input type="url" name="game_image_url" class="form-control" placeholder="https://...">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Screenshots (multiple)</label>
                    <input type="file" name="screenshots[]" class="form-control" accept="image/*" multiple>
                    <span class="form-help">Upload multiple screenshots at once</span>
                </div>
                <div class="mf-row">
                    <div class="mf-col">
                        <label class="form-label">SEO Title</label>
                        <input type="text" name="game_meta_title" class="form-control" placeholder="Custom page title for search engines">
                    </div>
                    <div class="mf-col">
                        <label class="form-label">SEO Description</label>
                        <input type="text" name="game_meta_desc" class="form-control" placeholder="Custom meta description">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Software</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== EDIT CATEGORY MODAL ========== -->
<div class="modal-overlay" id="editCategoryModal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-folder-open"></i> Edit Category</h3>
            <button class="modal-close" onclick="closeEditCategoryModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="update_category">
            <input type="hidden" name="category_id" id="edit_category_id">
            <div class="modal-body modal-form-body">
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="category_order" id="edit_category_order" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditCategoryModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?= $csrf_token ?>';
const CATEGORIES = <?= json_encode($categories) ?>;

/* ===== Sections ===== */
function showSection(s) {
    document.getElementById('categoriesSection').style.display = s === 'categories' ? '' : 'none';
    document.getElementById('softwareSection').style.display   = s === 'software' ? '' : 'none';
    document.getElementById('btnShowCategories').classList.toggle('active', s === 'categories');
    document.getElementById('btnShowSoftware').classList.toggle('active', s === 'software');
}

/* ===== Filter ===== */
function filterGames(filter, btn) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.querySelectorAll('.sw-card').forEach(card => {
        const cid = card.getAttribute('data-category-id');
        if (filter === 'all') { card.style.display = ''; }
        else if (filter === 'uncategorized') { card.style.display = (cid === '0' || cid === '') ? '' : 'none'; }
        else { card.style.display = ('cat-' + cid) === filter ? '' : 'none'; }
    });
}

/* ===== View Modal ===== */
function openViewModal(id) {
    const modal = document.getElementById('viewModal');
    const body  = document.getElementById('viewModalBody');
    modal.style.display = 'flex';
    body.innerHTML = '<div class="modal-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    fetch('games.php?ajax=get_game&id=' + id)
        .then(r => r.json())
        .then(game => {
            if (game.error) { body.innerHTML = '<p>Error: ' + game.error + '</p>'; return; }
            let imgSrc = game.image || '';
            if (imgSrc && imgSrc.indexOf('http') !== 0) imgSrc = '../' + imgSrc;

            let screenshotsHtml = '';
            if (game.screenshots && game.screenshots.length > 0) {
                screenshotsHtml = '<div class="vm-screenshots"><h4><i class="fas fa-images"></i> Screenshots</h4><div class="vm-screenshots-grid">';
                game.screenshots.forEach(s => {
                    let sSrc = s.image_path;
                    if (sSrc.indexOf('http') !== 0) sSrc = '../' + sSrc;
                    screenshotsHtml += '<div class="vm-screenshot-item">' +
                        '<img src="' + esc(sSrc) + '" alt="Screenshot" onclick="openLightbox(this.src)">' +
                        '<form method="POST" class="vm-screenshot-del" onsubmit="return confirm(\'Delete this screenshot?\')">' +
                        '<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
                        '<input type="hidden" name="action" value="delete_screenshot">' +
                        '<input type="hidden" name="screenshot_id" value="' + s.id + '">' +
                        '<button type="submit" title="Delete"><i class="fas fa-trash"></i></button></form></div>';
                });
                screenshotsHtml += '</div></div>';
            }

            let featuresHtml = '';
            if (game.features) {
                const lines = game.features.split('\n').filter(l => l.trim());
                if (lines.length) {
                    featuresHtml = '<div class="vm-features"><h4><i class="fas fa-check-circle"></i> Features</h4><ul>';
                    lines.forEach(l => { featuresHtml += '<li>' + esc(l.trim()) + '</li>'; });
                    featuresHtml += '</ul></div>';
                }
            }

            body.innerHTML =
                '<div class="vm-top">' +
                    '<div class="vm-cover">' + (imgSrc ? '<img src="' + esc(imgSrc) + '" alt="">' : '<div class="sw-card-noimg"><i class="fas fa-image"></i></div>') + '</div>' +
                    '<div class="vm-info">' +
                        '<h2>' + esc(game.name) + '</h2>' +
                        '<div class="vm-meta">' +
                            '<span class="vm-badge ' + (game.is_active == 1 ? 'vm-active' : 'vm-inactive') + '">' + (game.is_active == 1 ? 'Active' : 'Inactive') + '</span>' +
                            '<span><i class="fas fa-folder"></i> ' + esc(game.category_name) + '</span>' +
                            (game.version ? '<span><i class="fas fa-code-branch"></i> ' + esc(game.version) + '</span>' : '') +
                            (game.developer ? '<span><i class="fas fa-building"></i> ' + esc(game.developer) + '</span>' : '') +
                        '</div>' +
                        (game.slug ? '<div class="vm-slug"><i class="fas fa-link"></i> /software/' + esc(game.slug) + '</div>' : '') +
                        (game.description ? '<div class="vm-desc">' + esc(game.description) + '</div>' : '<div class="vm-desc vm-empty">No description added yet.</div>') +
                    '</div>' +
                '</div>' +
                featuresHtml +
                (game.system_requirements ? '<div class="vm-sysreq"><h4><i class="fas fa-desktop"></i> System Requirements</h4><p>' + esc(game.system_requirements) + '</p></div>' : '') +
                screenshotsHtml +
                '<div class="vm-actions">' +
                    '<button class="btn btn-primary" onclick="openEditFromView(' + game.id + ')"><i class="fas fa-edit"></i> Edit</button>' +
                    '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Delete this software?\')">' +
                        '<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
                        '<input type="hidden" name="action" value="delete_game">' +
                        '<input type="hidden" name="game_id" value="' + game.id + '">' +
                        '<button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>' +
                    '</form>' +
                '</div>';
        })
        .catch(err => { body.innerHTML = '<p style="color:var(--admin-danger)">Failed to load: ' + err + '</p>'; });
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

/* ===== Edit from View ===== */
function openEditFromView(id) {
    closeViewModal();
    fetch('games.php?ajax=get_game&id=' + id)
        .then(r => r.json())
        .then(game => {
            if (game.error) return;
            openEditModal(game);
        });
}

/* ===== Edit Modal (reuses Add modal) ===== */
function openEditModal(game) {
    const modal = document.getElementById('addModal');
    const form  = modal.querySelector('form');
    modal.querySelector('.modal-header h3').innerHTML = '<i class="fas fa-edit"></i> Edit Software';
    form.querySelector('[name="action"]').value = 'update_game';

    // Remove old hidden fields
    const oldId = form.querySelector('[name="game_id"]');
    if (oldId) oldId.remove();
    const oldOrder = form.querySelector('[name="game_order"]');
    if (oldOrder) oldOrder.remove();
    const oldActive = form.querySelector('[name="game_active"]');
    if (oldActive) oldActive.parentElement.remove();

    // Add game_id
    let inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'game_id'; inp.value = game.id;
    form.appendChild(inp);

    // Add game_order
    inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'game_order'; inp.value = game.display_order || 0;
    form.appendChild(inp);

    // Add game_active
    let chkDiv = document.createElement('div');
    chkDiv.className = 'form-group';
    chkDiv.innerHTML = '<label class="form-label"><input type="checkbox" name="game_active" ' + (game.is_active == 1 ? 'checked' : '') + '> Active</label>';
    form.querySelector('.modal-form-body').prepend(chkDiv);

    // Fill fields
    form.querySelector('[name="game_name"]').value = game.name || '';
    form.querySelector('[name="game_category"]').value = game.category_id || '';
    form.querySelector('[name="game_version"]').value = game.version || '';
    form.querySelector('[name="game_developer"]').value = game.developer || '';
    form.querySelector('[name="game_description"]').value = game.description || '';
    form.querySelector('[name="game_features"]').value = game.features || '';
    form.querySelector('[name="game_sysreq"]').value = game.system_requirements || '';
    form.querySelector('[name="game_meta_title"]').value = game.meta_title || '';
    form.querySelector('[name="game_meta_desc"]').value = game.meta_description || '';

    // Change submit button text
    form.querySelector('[type="submit"]').innerHTML = '<i class="fas fa-save"></i> Save Changes';

    modal.style.display = 'flex';
}

/* ===== Add Modal ===== */
function openAddModal() {
    const modal = document.getElementById('addModal');
    const form  = modal.querySelector('form');
    modal.querySelector('.modal-header h3').innerHTML = '<i class="fas fa-plus-circle"></i> Add Software';
    form.querySelector('[name="action"]').value = 'add_game';

    // Remove edit-specific fields
    const oldId = form.querySelector('[name="game_id"]');
    if (oldId) oldId.remove();
    const oldOrder = form.querySelector('[name="game_order"]');
    if (oldOrder) oldOrder.remove();
    const oldActive = form.querySelector('.modal-form-body > .form-group:first-child');
    if (oldActive && oldActive.querySelector('[name="game_active"]')) oldActive.remove();

    form.reset();
    form.querySelector('[type="submit"]').innerHTML = '<i class="fas fa-plus"></i> Add Software';
    modal.style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

/* ===== Category Modal ===== */
function openEditCategoryModal(id, name, order) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_category_order').value = order;
    document.getElementById('editCategoryModal').style.display = 'flex';
}
function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
}

/* ===== Lightbox ===== */
function openLightbox(src) {
    const lb = document.createElement('div');
    lb.className = 'lightbox-overlay';
    lb.onclick = () => lb.remove();
    lb.innerHTML = '<img src="' + src + '" class="lightbox-img">';
    document.body.appendChild(lb);
}

/* ===== Close modals on overlay click ===== */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});

/* ===== Helper ===== */
function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

/* ===== Auto-hide alerts ===== */
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => { a.style.opacity = '0'; setTimeout(() => a.remove(), 300); }, 4000);
});
</script>

<script src="js/admin-notifications.js"></script>
</body>
</html>