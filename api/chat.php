<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../admin/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$session_hash = $input['session_hash'] ?? '';

if (empty($session_hash)) {
    echo json_encode(['success' => false, 'error' => 'No session']);
    exit;
}

switch ($action) {

    case 'send':
        $message = trim($input['message'] ?? '');
        if (empty($message) || mb_strlen($message) > 2000) {
            echo json_encode(['success' => false, 'error' => 'Invalid message']);
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $country = get_country_from_ip($ip);

        // Find or create session
        $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_hash = ? LIMIT 1");
        $stmt->bind_param("s", $session_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        if ($session) {
            $session_id = $session['id'];
            // Update session
            $upd = $conn->prepare("UPDATE chat_sessions SET status = 'waiting', last_message_at = NOW(), ip_address = ?, country = ?, user_agent = ? WHERE id = ?");
            $upd->bind_param("sssi", $ip, $country, $user_agent, $session_id);
            $upd->execute();
            $upd->close();
        } else {
            // Create new session
            $ins = $conn->prepare("INSERT INTO chat_sessions (session_hash, ip_address, country, user_agent, status, last_message_at) VALUES (?, ?, ?, ?, 'waiting', NOW())");
            $ins->bind_param("ssss", $session_hash, $ip, $country, $user_agent);
            $ins->execute();
            $session_id = $conn->insert_id;
            $ins->close();
        }

        // Insert message
        $msg_stmt = $conn->prepare("INSERT INTO chat_messages (session_id, sender, message) VALUES (?, 'user', ?)");
        $msg_stmt->bind_param("is", $session_id, $message);
        $msg_stmt->execute();
        $msg_id = $conn->insert_id;
        $msg_stmt->close();

        echo json_encode(['success' => true, 'session_id' => $session_id, 'message_id' => $msg_id]);
        break;

    case 'poll':
        $last_id = intval($input['last_id'] ?? 0);

        $stmt = $conn->prepare("SELECT cs.id as session_id FROM chat_sessions cs WHERE cs.session_hash = ? LIMIT 1");
        $stmt->bind_param("s", $session_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        if (!$session) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        $msg_stmt = $conn->prepare("SELECT id, sender, message, DATE_FORMAT(created_at, '%H:%i') as time FROM chat_messages WHERE session_id = ? AND id > ? AND sender = 'admin' ORDER BY id ASC");
        $msg_stmt->bind_param("ii", $session['session_id'], $last_id);
        $msg_stmt->execute();
        $msg_result = $msg_stmt->get_result();

        $messages = [];
        while ($row = $msg_result->fetch_assoc()) {
            $messages[] = $row;
        }
        $msg_stmt->close();

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'history':
        $stmt = $conn->prepare("SELECT cs.id as session_id FROM chat_sessions cs WHERE cs.session_hash = ? LIMIT 1");
        $stmt->bind_param("s", $session_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        if (!$session) {
            echo json_encode(['success' => true, 'messages' => [], 'unread_count' => 0]);
            exit;
        }

        // Get last 50 messages
        $msg_stmt = $conn->prepare("SELECT id, sender, message, DATE_FORMAT(created_at, '%H:%i') as time FROM chat_messages WHERE session_id = ? ORDER BY id DESC LIMIT 50");
        $msg_stmt->bind_param("i", $session['session_id']);
        $msg_stmt->execute();
        $msg_result = $msg_stmt->get_result();

        $messages = [];
        while ($row = $msg_result->fetch_assoc()) {
            $messages[] = $row;
        }
        $msg_stmt->close();

        // Reverse to chronological order
        $messages = array_reverse($messages);

        // Count unread admin messages
        $unread_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM chat_messages WHERE session_id = ? AND sender = 'admin' AND is_read = 0");
        $unread_stmt->bind_param("i", $session['session_id']);
        $unread_stmt->execute();
        $unread_result = $unread_stmt->get_result()->fetch_assoc();
        $unread_count = $unread_result['cnt'] ?? 0;
        $unread_stmt->close();

        echo json_encode(['success' => true, 'messages' => $messages, 'unread_count' => (int)$unread_count]);
        break;

    case 'mark_read':
        $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_hash = ? LIMIT 1");
        $stmt->bind_param("s", $session_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        if ($session) {
            $upd = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender = 'admin' AND is_read = 0");
            $upd->bind_param("i", $session['id']);
            $upd->execute();
            $upd->close();
        }

        echo json_encode(['success' => true]);
        break;

    case 'check_unread':
        $stmt = $conn->prepare("SELECT cs.id FROM chat_sessions cs WHERE cs.session_hash = ? LIMIT 1");
        $stmt->bind_param("s", $session_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        $count = 0;
        if ($session) {
            $cnt_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM chat_messages WHERE session_id = ? AND sender = 'admin' AND is_read = 0");
            $cnt_stmt->bind_param("i", $session['id']);
            $cnt_stmt->execute();
            $cnt_result = $cnt_stmt->get_result()->fetch_assoc();
            $count = $cnt_result['cnt'] ?? 0;
            $cnt_stmt->close();
        }

        echo json_encode(['success' => true, 'unread_count' => (int)$count]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
?>