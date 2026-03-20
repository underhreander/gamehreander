<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$stats = get_admin_stats();
$recent_visits = get_recent_visits(10);
$recent_downloads = get_recent_downloads(10);
$countries = get_country_stats();
$daily_stats = get_daily_stats(30);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoftMaster — Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <i class="fas fa-cube"></i>
                <span><b>Soft</b>Master</span>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li class="active"><a href="control.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="games.php"><i class="fas fa-box"></i> Software</a></li>
                    <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <section class="admin-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-eye"></i></div>
                    <div class="stat-info">
                        <h3>Total Visits</h3>
                        <p><?php echo number_format($stats['total_visits']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-info">
                        <h3>Today's Visits</h3>
                        <p><?php echo number_format($stats['today_visits']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3>Unique Visitors</h3>
                        <p><?php echo number_format($stats['unique_visits']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-info">
                        <h3>Today's Unique Visitors</h3>
                        <p><?php echo number_format($stats['today_unique_visits']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-download"></i></div>
                    <div class="stat-info">
                        <h3>Total Downloads</h3>
                        <p><?php echo number_format($stats['total_downloads']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                    <div class="stat-info">
                        <h3>Unique Downloads</h3>
                        <p><?php echo number_format($stats['unique_downloads']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3>Today's Downloads</h3>
                        <p><?php echo number_format($stats['today_downloads']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-gamepad"></i></div>
                    <div class="stat-info">
                        <h3>Active Software</h3>
                        <p><?php echo number_format($stats['active_games']); ?></p>
                    </div>
                </div>
            </section>

            <section class="admin-charts">
                <div class="chart-container">
                    <h2>Visits & Downloads (Last 30 Days)</h2>
                    <canvas id="trafficChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2>Visitors by Country</h2>
                    <canvas id="countryChart"></canvas>
                </div>
            </section>

            <section class="admin-tables">
                <div class="table-container">
                    <h2>Recent Visits</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Country</th>
                                <th>Page</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_visits)): ?>
                                <tr><td colspan="4" style="text-align:center; color:#64748b;">No visits yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_visits as $visit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($visit['ip_address'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($visit['country'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($visit['page_visited'] ?? ''); ?></td>
                                    <td><?php echo !empty($visit['visit_time']) ? date('M j, Y H:i', strtotime($visit['visit_time'])) : '—'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-container">
                    <h2>Recent Downloads</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Country</th>
                                <th>Trial Code</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_downloads)): ?>
                                <tr><td colspan="4" style="text-align:center; color:#64748b;">No downloads yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_downloads as $download): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($download['ip_address'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($download['country'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($download['trial_code'] ?? ''); ?></td>
                                    <td><?php echo !empty($download['download_time']) ? date('M j, Y H:i', strtotime($download['download_time'])) : '—'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Traffic chart
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_stats, 'date')); ?>,
                datasets: [
                    {
                        label: 'Visits',
                        data: <?php echo json_encode(array_column($daily_stats, 'visits')); ?>,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Downloads',
                        data: <?php echo json_encode(array_column($daily_stats, 'downloads')); ?>,
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top', labels: { color: '#94a3b8' } } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#64748b' }, grid: { color: 'rgba(148,163,184,0.08)' } },
                    x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(148,163,184,0.08)' } }
                }
            }
        });

        // Country chart
        <?php
        $country_labels = array_column($countries, 'country');
        $country_counts = array_column($countries, 'cnt');
        ?>
        const countryCtx = document.getElementById('countryChart').getContext('2d');
        new Chart(countryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($country_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($country_counts); ?>,
                    backgroundColor: ['#2563eb', '#3b82f6', '#06b6d4', '#22d3ee', '#0ea5e9', '#6366f1', '#8b5cf6', '#a78bfa', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#64748b', '#475569', '#78716c', '#a3a3a3', '#d4d4d4', '#fafafa'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'right', labels: { color: '#94a3b8' } } }
            }
        });
    </script>
    <script src="js/admin-notifications.js"></script>
</body>
</html>