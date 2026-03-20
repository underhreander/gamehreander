<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$session_hash = $_POST['session_hash'] ?? $_GET['session_hash'] ?? '';

if (empty($session_hash) && $action !== '') {
    echo json_encode(['success' => false, 'error' => 'No session hash']);
    exit;
}

switch ($action) {

    case 'send':
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            echo json_encode(['success' => false, 'error' => 'Empty message']);
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $country = function_exists('get_country_by_ip') ? get_country_by_ip($ip) : 'Unknown';

        // Create session if not exists
        $stmt = $conn->prepare("SELECT id, status FROM chat_sessions WHERE session_hash = ? LIMIT 1");
        $stmt->bind_param('s', $session_hash);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$session) {
            $stmt = $conn->prepare("INSERT INTO chat_sessions (session_hash, ip_address, country, user_agent, status, session_type, created_at) VALUES (?, ?, ?, ?, 'open', 'support', NOW())");
            $stmt->bind_param('ssss', $session_hash, $ip, $country, $ua);
            $stmt->execute();
            $session_id = $stmt->insert_id;
            $stmt->close();
        } else {
            $session_id = $session['id'];
            if ($session['status'] === 'closed') {
                $conn->query("UPDATE chat_sessions SET status = 'open' WHERE id = {$session_id}");
            }
        }

        // Insert message
        $stmt = $conn->prepare("INSERT INTO chat_messages (session_id, sender, message, created_at) VALUES (?, 'user', ?, NOW())");
        $stmt->bind_param('is', $session_id, $message);
        $stmt->execute();
        $msg_id = $stmt->insert_id;
        $stmt->close();

        // Update session
        $conn->query("UPDATE chat_sessions SET status = 'waiting', last_message_at = NOW() WHERE id = {$session_id}");

        echo json_encode(['success' => true, 'message_id' => $msg_id]);
        break;

    case 'send_purchase':
        $message = trim($_POST['message'] ?? '');
        $plan_name = trim($_POST['plan_name'] ?? '');
        $plan_price = trim($_POST['plan_price'] ?? '');
        $currency = trim($_POST['currency'] ?? 'USD');
        $payment_method = trim($_POST['payment_method'] ?? '');

        if ($message === '') {
            echo json_encode(['success' => false, 'error' => 'Empty message']);
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $country = function_exists('get_country_by_ip') ? get_country_by_ip($ip) : 'Unknown';

        // Create or get session
        $stmt = $conn->prepare("SELECT id, status FROM chat_sessions WHERE session_hash = ? LIMIT 1");
        $stmt->bind_param('s', $session_hash);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$session) {
            $stmt = $conn->prepare("INSERT INTO chat_sessions (session_hash, ip_address, country, user_agent, status, session_type, created_at) VALUES (?, ?, ?, ?, 'open', 'purchase', NOW())");
            $stmt->bind_param('ssss', $session_hash, $ip, $country, $ua);
            $stmt->execute();
            $session_id = $stmt->insert_id;
            $stmt->close();
        } else {
            $session_id = $session['id'];
            $conn->query("UPDATE chat_sessions SET status = 'open', session_type = 'purchase' WHERE id = {$session_id}");
        }

        // Log purchase request
        $stmt = $conn->prepare("INSERT INTO purchase_requests (session_hash, plan_name, plan_price, currency, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param('sssss', $session_hash, $plan_name, $plan_price, $currency, $payment_method);
        $stmt->execute();
        $stmt->close();

        // Insert message
        $auto_msg = "[PURCHASE REQUEST]\nPlan: {$plan_name}\nPrice: {$plan_price} {$currency}\nPayment: {$payment_method}\n\n{$message}";
        $stmt = $conn->prepare("INSERT INTO chat_messages (session_id, sender, message, created_at) VALUES (?, 'user', ?, NOW())");
        $stmt->bind_param('is', $session_id, $auto_msg);
        $stmt->execute();
        $msg_id = $stmt->insert_id;
        $stmt->close();

        $conn->query("UPDATE chat_sessions SET status = 'waiting', last_message_at = NOW() WHERE id = {$session_id}");

        echo json_encode(['success' => true, 'message_id' => $msg_id]);
        break;

    case 'poll':
        $last_id = (int)($_POST['last_id'] ?? $_GET['last_id'] ?? 0);

        $stmt = $conn->prepare("SELECT cs.id as session_id FROM chat_sessions cs WHERE cs.session_hash = ? LIMIT 1");
        $stmt->bind_param('s', $session_hash);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$session) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, sender, message, created_at FROM chat_messages WHERE session_id = ? AND id > ? AND sender = 'admin' ORDER BY id ASC");
        $stmt->bind_param('ii', $session['session_id'], $last_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'history':
        $stmt = $conn->prepare("SELECT cs.id as session_id FROM chat_sessions cs WHERE cs.session_hash = ? LIMIT 1");
        $stmt->bind_param('s', $session_hash);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$session) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, sender, message, created_at FROM chat_messages WHERE session_id = ? ORDER BY id ASC LIMIT 100");
        $stmt->bind_param('i', $session['session_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'mark_read':
        $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_hash = ? LIMIT 1");
        $stmt->bind_param('s', $session_hash);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($session) {
            $conn->query("UPDATE chat_messages SET is_read = 1 WHERE session_id = {$session['id']} AND sender = 'admin' AND is_read = 0");
        }

        echo json_encode(['success' => true]);
        break;

    case 'check_unread':
        $stmt = $conn->prepare("SELECT cs.id FROM chat_sessions cs WHERE cs.session_hash = ? LIMIT 1");
        $stmt->bind_param('s', $session_hash);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $count = 0;
        if ($session) {
            $r = $conn->query("SELECT COUNT(*) as cnt FROM chat_messages WHERE session_id = {$session['id']} AND sender = 'admin' AND is_read = 0");
            if ($r) $count = (int)$r->fetch_assoc()['cnt'];
        }

        echo json_encode(['success' => true, 'unread' => $count]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
?>