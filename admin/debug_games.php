<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db_connect.php';

echo "<h2>Debug Games</h2>";

// 1. Check table structure
echo "<h3>1. Table structure (games):</h3>";
$r = $conn->query("DESCRIBE games");
if ($r) {
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>ERROR: " . $conn->error . "</p>";
}

// 2. Check categories table
echo "<h3>2. Table structure (categories):</h3>";
$r = $conn->query("DESCRIBE categories");
if ($r) {
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>ERROR: " . $conn->error . "</p>";
}

// 3. Count records
echo "<h3>3. Records count:</h3>";
$r = $conn->query("SELECT COUNT(*) as cnt FROM games");
$cnt = $r ? $r->fetch_assoc()['cnt'] : 'ERROR: '.$conn->error;
echo "<p>Games: <b>{$cnt}</b></p>";

$r = $conn->query("SELECT COUNT(*) as cnt FROM categories");
$cnt = $r ? $r->fetch_assoc()['cnt'] : 'ERROR: '.$conn->error;
echo "<p>Categories: <b>{$cnt}</b></p>";

// 4. Show all games
echo "<h3>4. All games in DB:</h3>";
$r = $conn->query("SELECT * FROM games ORDER BY id DESC LIMIT 20");
if ($r && $r->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr>";
    $first = true;
    while ($row = $r->fetch_assoc()) {
        if ($first) {
            foreach (array_keys($row) as $col) echo "<th>{$col}</th>";
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $val) echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No games found or error: " . $conn->error . "</p>";
}

// 5. Show all categories
echo "<h3>5. All categories in DB:</h3>";
$r = $conn->query("SELECT * FROM categories ORDER BY id DESC LIMIT 20");
if ($r && $r->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr>";
    $first = true;
    while ($row = $r->fetch_assoc()) {
        if ($first) {
            foreach (array_keys($row) as $col) echo "<th>{$col}</th>";
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $val) echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No categories found or error: " . $conn->error . "</p>";
}

// 6. Test INSERT
echo "<h3>6. Test INSERT:</h3>";
$test_name = 'DEBUG_TEST_' . time();
$test_image = '';
$test_cat = null;
$test_order = 0;

$stmt = $conn->prepare("INSERT INTO games (name, image, category_id, is_active, display_order) VALUES (?, ?, ?, 1, ?)");
if (!$stmt) {
    echo "<p style='color:red'>Prepare failed: " . $conn->error . "</p>";
} else {
    $stmt->bind_param('ssii', $test_name, $test_image, $test_cat, $test_order);
    $result = $stmt->execute();
    if ($result) {
        $new_id = $stmt->insert_id;
        echo "<p style='color:green'>INSERT OK! New ID: {$new_id}, Name: {$test_name}</p>";
        // Clean up
        $conn->query("DELETE FROM games WHERE id = {$new_id}");
        echo "<p>Test record deleted.</p>";
    } else {
        echo "<p style='color:red'>INSERT FAILED: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// 7. Check get_games_list()
echo "<h3>7. get_games_list() result:</h3>";
$list = get_games_list();
echo "<p>Count: <b>" . count($list) . "</b></p>";
foreach ($list as $g) {
    echo "<p>ID: {$g['id']} | Name: {$g['name']} | Active: {$g['is_active']} | Cat: " . ($g['category_id'] ?? 'NULL') . "</p>";
}

echo "<hr><p>Debug complete.</p>";
?>