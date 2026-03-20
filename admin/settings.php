<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

$success = '';
$error = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_general') {
        $site_name = trim($_POST['site_name'] ?? '');
        $hero_title = trim($_POST['hero_title'] ?? '');
        $hero_subtitle = trim($_POST['hero_subtitle'] ?? '');
        if ($site_name !== '') update_setting('site_name', $site_name);
        if ($hero_title !== '') update_setting('hero_title', $hero_title);
        if ($hero_subtitle !== '') update_setting('hero_subtitle', $hero_subtitle);
        $success = 'General settings updated successfully!';
        log_admin_action('settings', 'Updated general settings');
    }

    if ($action === 'save_download') {
        $download_link = trim($_POST['download_link'] ?? '');
        $archive_password = trim($_POST['archive_password'] ?? '');
        update_setting('download_link', $download_link);
        update_setting('archive_password', $archive_password);
        $success = 'Download settings updated successfully!';
        log_admin_action('settings', 'Updated download link and archive password');
    }

    /* ===== NEW: Homepage Counters ===== */
    if ($action === 'save_counters') {
        $downloads_offset = (int)($_POST['downloads_offset'] ?? 0);
        $users_offset = (int)($_POST['users_offset'] ?? 0);
        update_setting('downloads_offset', (string)$downloads_offset);
        update_setting('users_offset', (string)$users_offset);
        $success = 'Homepage counters updated successfully!';
        log_admin_action('settings', "Updated counters: downloads_offset={$downloads_offset}, users_offset={$users_offset}");
    }
    /* ===== END NEW ===== */

    if ($action === 'save_promo') {
        $promo_enabled = isset($_POST['promo_enabled']) ? '1' : '0';
        update_setting('promo_enabled', $promo_enabled);
        update_setting('promo_title', trim($_POST['promo_title'] ?? 'Special Offer!'));
        update_setting('promo_text', trim($_POST['promo_text'] ?? ''));
        update_setting('promo_countdown', (int)($_POST['promo_countdown'] ?? 300));
        update_setting('promo_btn_text', trim($_POST['promo_btn_text'] ?? 'Get Deal Now'));
        update_setting('promo_btn_link', trim($_POST['promo_btn_link'] ?? '#pricing'));
        update_setting('promo_animation', trim($_POST['promo_animation'] ?? 'fadeIn'));
        update_setting('promo_delay', (int)($_POST['promo_delay'] ?? 5));
        $success = 'Promo popup settings updated successfully!';
        log_admin_action('settings', 'Updated promo popup settings');
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            $admin_id = $_SESSION['admin_id'] ?? 0;
            $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $admin_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row && password_verify($current, $row['password_hash'])) {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt2 = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                    if ($stmt2) {
                        $stmt2->bind_param('si', $new_hash, $admin_id);
                        $stmt2->execute();
                        $stmt2->close();
                        $success = 'Password changed successfully!';
                        log_admin_action('change_password', 'Admin changed password');
                    }
                } else {
                    $error = 'Current password is incorrect.';
                }
            }
        }
    }
}

// Load current values
$site_name = get_setting('site_name') ?? 'SoftMaster';
$hero_title = get_setting('hero_title') ?? 'Next-Gen Game Enhancement Platform';
$hero_subtitle = get_setting('hero_subtitle') ?? 'Premium tools for serious gamers';
$download_link = get_setting('download_link') ?? '';
$archive_password = get_setting('archive_password') ?? '';

/* ===== NEW: load counter offsets ===== */
$downloads_offset = get_setting('downloads_offset') ?? '0';
$users_offset = get_setting('users_offset') ?? '0';
/* ===== END NEW ===== */

$promo_enabled = get_setting('promo_enabled') ?? '0';
$promo_title = get_setting('promo_title') ?? 'Special Offer!';
$promo_text = get_setting('promo_text') ?? 'Get 50% off on all plans. Limited time only!';
$promo_countdown = get_setting('promo_countdown') ?? '300';
$promo_btn_text = get_setting('promo_btn_text') ?? 'Get Deal Now';
$promo_btn_link = get_setting('promo_btn_link') ?? '#pricing';
$promo_animation = get_setting('promo_animation') ?? 'fadeIn';
$promo_delay = get_setting('promo_delay') ?? '5';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
        .settings-section { background:var(--admin-card); border:1px solid var(--admin-border); border-radius:var(--admin-radius); padding:28px; transition:all .2s ease; }
        .settings-section:hover { border-color:rgba(37,99,235,.2); }
        .settings-section.full-width { grid-column:1/-1; }
        .settings-section h3 { font-size:16px; font-weight:700; color:var(--admin-text); margin-bottom:6px; display:flex; align-items:center; gap:10px; }
        .settings-section h3 i { color:var(--admin-primary); font-size:15px; }
        .settings-section .section-desc { font-size:13px; color:var(--admin-text-muted); margin-bottom:20px; }
        .s-form-group { margin-bottom:18px; }
        .s-form-group:last-child { margin-bottom:0; }
        .s-form-group label { display:block; font-size:13px; font-weight:600; color:var(--admin-text-secondary); margin-bottom:6px; }
        .s-form-group input[type="text"],.s-form-group input[type="url"],.s-form-group input[type="number"],.s-form-group input[type="password"],.s-form-group textarea,.s-form-group select { width:100%; padding:10px 14px; background:var(--admin-bg); border:1px solid var(--admin-border); border-radius:8px; color:var(--admin-text); font-family:var(--admin-font); font-size:14px; outline:none; transition:border-color .2s ease; }
        .s-form-group input:focus,.s-form-group textarea:focus,.s-form-group select:focus { border-color:var(--admin-primary); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
        .s-form-group input::placeholder,.s-form-group textarea::placeholder { color:var(--admin-text-muted); }
        .s-form-group select { appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:36px; }
        .s-form-group select option { background:var(--admin-bg); color:var(--admin-text); }
        .s-form-group textarea { min-height:80px; resize:vertical; }
        .toggle-group { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
        .settings-toggle { position:relative; display:inline-block; width:48px; height:26px; flex-shrink:0; }
        .settings-toggle input { opacity:0; width:0; height:0; }
        .settings-toggle-slider { position:absolute; cursor:pointer; inset:0; background:var(--admin-text-muted); border-radius:26px; transition:all .3s ease; }
        .settings-toggle-slider::before { content:''; position:absolute; width:20px; height:20px; border-radius:50%; background:#fff; left:3px; bottom:3px; transition:all .3s ease; }
        .settings-toggle input:checked+.settings-toggle-slider { background:var(--admin-success); }
        .settings-toggle input:checked+.settings-toggle-slider::before { transform:translateX(22px); }
        .toggle-label { font-size:14px; color:var(--admin-text); font-weight:500; }
        .s-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:var(--admin-primary); color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; font-family:var(--admin-font); cursor:pointer; transition:all .2s ease; margin-top:8px; }
        .s-btn:hover { background:#1d4ed8; transform:translateY(-1px); box-shadow:0 4px 12px rgba(37,99,235,.3); }
        .promo-preview { background:rgba(37,99,235,.05); border:1px dashed rgba(37,99,235,.2); border-radius:var(--admin-radius); padding:20px; text-align:center; margin-top:16px; }
        .promo-preview h4 { font-size:16px; font-weight:700; color:var(--admin-text); margin-bottom:8px; }
        .promo-preview p { font-size:13px; color:var(--admin-text-secondary); margin-bottom:12px; }
        .promo-preview .preview-countdown { font-size:18px; font-weight:700; color:var(--admin-primary); margin-bottom:12px; font-family:'JetBrains Mono',monospace; }
        .promo-preview .preview-btn { display:inline-block; padding:8px 20px; background:var(--admin-primary); color:#fff; border-radius:8px; font-size:13px; font-weight:600; }
        .settings-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .settings-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
        .password-hint { font-size:12px; color:var(--admin-text-muted); margin-top:4px; }
        /* ===== NEW: counter info styles ===== */
        .counter-info { display:flex; align-items:center; gap:8px; margin-top:6px; padding:8px 12px; background:rgba(37,99,235,.06); border-radius:6px; font-size:12px; color:var(--admin-text-muted); }
        .counter-info i { color:var(--admin-primary); font-size:11px; }
        .counter-formula { font-family:'JetBrains Mono',monospace; color:var(--admin-text-secondary); font-weight:500; }
        /* ===== END NEW ===== */
        @media(max-width:768px) { .settings-grid { grid-template-columns:1fr; } .settings-row-2,.settings-row-3 { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo"><i class="fas fa-cube"></i><span><b>Soft</b>Master</span></div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="control.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="games.php"><i class="fas fa-gamepad"></i> Software</a></li>
                    <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                    <li class="active"><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Settings</h1>
                <div class="admin-user"><i class="fas fa-user-circle"></i><span>Admin</span></div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="settings-grid">

                <!-- General Settings -->
                <div class="settings-section">
                    <h3><i class="fas fa-globe"></i> General Settings</h3>
                    <p class="section-desc">Configure your site name and hero section content</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_general">
                        <div class="s-form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="<?= htmlspecialchars($site_name) ?>" placeholder="SoftMaster">
                        </div>
                        <div class="s-form-group">
                            <label>Hero Title</label>
                            <input type="text" name="hero_title" value="<?= htmlspecialchars($hero_title) ?>" placeholder="Next-Gen Game Enhancement Platform">
                        </div>
                        <div class="s-form-group">
                            <label>Hero Subtitle</label>
                            <input type="text" name="hero_subtitle" value="<?= htmlspecialchars($hero_subtitle) ?>" placeholder="Premium tools for serious gamers">
                        </div>
                        <button type="submit" class="s-btn"><i class="fas fa-save"></i> Save General</button>
                    </form>
                </div>

                <!-- Download Link + Archive Password -->
                <div class="settings-section">
                    <h3><i class="fas fa-download"></i> Download Configuration</h3>
                    <p class="section-desc">Set the download URL and archive password</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_download">
                        <div class="s-form-group">
                            <label>Download Link (URL)</label>
                            <input type="url" name="download_link" value="<?= htmlspecialchars($download_link) ?>" placeholder="https://example.com/launcher.exe">
                        </div>
                        <div class="s-form-group">
                            <label><i class="fas fa-lock" style="margin-right:4px;font-size:12px;color:var(--admin-primary);"></i> Archive Password</label>
                            <input type="text" name="archive_password" value="<?= htmlspecialchars($archive_password) ?>" placeholder="Enter archive password...">
                            <p class="password-hint">This password will be shown to users after they generate a trial key. Leave empty to disable.</p>
                        </div>
                        <button type="submit" class="s-btn"><i class="fas fa-save"></i> Save Download Settings</button>
                    </form>
                </div>

                <!-- ===== NEW: Homepage Counters ===== -->
                <div class="settings-section">
                    <h3><i class="fas fa-chart-line"></i> Homepage Counters</h3>
                    <p class="section-desc">Set initial offset values for hero section stats. Real statistics are added on top of these numbers automatically. Admin dashboard always shows real data without offsets.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_counters">
                        <div class="s-form-group">
                            <label>Downloads Offset</label>
                            <input type="number" name="downloads_offset" value="<?= htmlspecialchars($downloads_offset) ?>" min="0" placeholder="13817">
                            <?php
                                $real_stats = get_site_stats();
                                $real_dl = (int)($real_stats['total_downloads'] ?? 0);
                                $real_vis = (int)($real_stats['total_visits'] ?? 0);
                            ?>
                            <div class="counter-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Homepage will show: <span class="counter-formula"><?= (int)$downloads_offset ?> + <?= $real_dl ?> = <?= (int)$downloads_offset + $real_dl ?></span></span>
                            </div>
                        </div>
                        <div class="s-form-group">
                            <label>Users Offset</label>
                            <input type="number" name="users_offset" value="<?= htmlspecialchars($users_offset) ?>" min="0" placeholder="18361">
                            <div class="counter-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Homepage will show: <span class="counter-formula"><?= (int)$users_offset ?> + <?= $real_vis ?> = <?= (int)$users_offset + $real_vis ?></span></span>
                            </div>
                        </div>
                        <div class="s-form-group">
                            <label>Products</label>
                            <input type="number" value="<?= count(get_games_list()) ?>" disabled style="opacity:.6;cursor:not-allowed;">
                            <div class="counter-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Products count is always the actual number of active software items. Not editable.</span>
                            </div>
                        </div>
                        <button type="submit" class="s-btn"><i class="fas fa-save"></i> Save Counters</button>
                    </form>
                </div>
                <!-- ===== END NEW ===== -->

                <!-- Promo Popup Settings -->
                <div class="settings-section full-width">
                    <h3><i class="fas fa-fire"></i> Promo Popup</h3>
                    <p class="section-desc">Configure the promotional modal that appears after page load</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_promo">
                        <div class="toggle-group">
                            <label class="settings-toggle">
                                <input type="checkbox" name="promo_enabled" value="1" <?= $promo_enabled === '1' ? 'checked' : '' ?> id="promoEnabledToggle">
                                <span class="settings-toggle-slider"></span>
                            </label>
                            <span class="toggle-label">Enable Promo Popup</span>
                        </div>
                        <div id="promoFields">
                            <div class="settings-row-2">
                                <div class="s-form-group">
                                    <label>Title</label>
                                    <input type="text" name="promo_title" value="<?= htmlspecialchars($promo_title) ?>" placeholder="Special Offer!">
                                </div>
                                <div class="s-form-group">
                                    <label>Button Text</label>
                                    <input type="text" name="promo_btn_text" value="<?= htmlspecialchars($promo_btn_text) ?>" placeholder="Get Deal Now">
                                </div>
                            </div>
                            <div class="s-form-group">
                                <label>Promo Text</label>
                                <textarea name="promo_text" placeholder="Get 50% off on all plans..."><?= htmlspecialchars($promo_text) ?></textarea>
                            </div>
                            <div class="settings-row-3">
                                <div class="s-form-group">
                                    <label>Button Link</label>
                                    <input type="text" name="promo_btn_link" value="<?= htmlspecialchars($promo_btn_link) ?>" placeholder="#pricing">
                                </div>
                                <div class="s-form-group">
                                    <label>Countdown (seconds)</label>
                                    <input type="number" name="promo_countdown" value="<?= htmlspecialchars($promo_countdown) ?>" min="0" max="86400" placeholder="300">
                                </div>
                                <div class="s-form-group">
                                    <label>Show Delay (seconds)</label>
                                    <input type="number" name="promo_delay" value="<?= htmlspecialchars($promo_delay) ?>" min="0" max="120" placeholder="5">
                                </div>
                            </div>
                            <div class="s-form-group">
                                <label>Animation Style</label>
                                <select name="promo_animation">
                                    <option value="fadeIn" <?= $promo_animation === 'fadeIn' ? 'selected' : '' ?>>Fade In</option>
                                    <option value="slideUp" <?= $promo_animation === 'slideUp' ? 'selected' : '' ?>>Slide Up</option>
                                    <option value="scaleIn" <?= $promo_animation === 'scaleIn' ? 'selected' : '' ?>>Scale In</option>
                                    <option value="bounceIn" <?= $promo_animation === 'bounceIn' ? 'selected' : '' ?>>Bounce In</option>
                                </select>
                            </div>
                            <div class="promo-preview" id="promoPreview">
                                <h4 id="previewTitle"><?= htmlspecialchars($promo_title) ?></h4>
                                <p id="previewText"><?= htmlspecialchars($promo_text) ?></p>
                                <div class="preview-countdown" id="previewCountdown"><?php $m=floor((int)$promo_countdown/60); $s=(int)$promo_countdown%60; echo str_pad($m,2,'0',STR_PAD_LEFT).':'.str_pad($s,2,'0',STR_PAD_LEFT); ?></div>
                                <span class="preview-btn" id="previewBtn"><?= htmlspecialchars($promo_btn_text) ?></span>
                            </div>
                        </div>
                        <button type="submit" class="s-btn" style="margin-top:16px;"><i class="fas fa-save"></i> Save Promo Settings</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="settings-section full-width">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                    <p class="section-desc">Update your admin panel password</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="settings-row-3">
                            <div class="s-form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required placeholder="••••••••">
                            </div>
                            <div class="s-form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required placeholder="••••••••" minlength="6">
                            </div>
                            <div class="s-form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required placeholder="••••••••">
                            </div>
                        </div>
                        <button type="submit" class="s-btn"><i class="fas fa-key"></i> Change Password</button>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
        const titleInput=document.querySelector('input[name="promo_title"]');
        const textInput=document.querySelector('textarea[name="promo_text"]');
        const btnTextInput=document.querySelector('input[name="promo_btn_text"]');
        const countdownInput=document.querySelector('input[name="promo_countdown"]');
        if(titleInput) titleInput.addEventListener('input',function(){document.getElementById('previewTitle').textContent=this.value||'Title';});
        if(textInput) textInput.addEventListener('input',function(){document.getElementById('previewText').textContent=this.value||'Promo text...';});
        if(btnTextInput) btnTextInput.addEventListener('input',function(){document.getElementById('previewBtn').textContent=this.value||'Button';});
        if(countdownInput) countdownInput.addEventListener('input',function(){const v=parseInt(this.value)||0;const m=Math.floor(v/60);const s=v%60;document.getElementById('previewCountdown').textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');});
        const promoToggle=document.getElementById('promoEnabledToggle');
        const promoFields=document.getElementById('promoFields');
        if(promoToggle&&promoFields){function updatePromoFields(){promoFields.style.opacity=promoToggle.checked?'1':'0.4';promoFields.style.pointerEvents=promoToggle.checked?'auto':'none';}promoToggle.addEventListener('change',updatePromoFields);updatePromoFields();}
        document.querySelectorAll('.alert').forEach(el=>{setTimeout(()=>{el.style.transition='opacity 0.5s ease';el.style.opacity='0';setTimeout(()=>el.remove(),500);},4000);});
    </script>
    <script src="js/admin-notifications.js"></script>
</body>
</html>