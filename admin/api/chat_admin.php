<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db_connect.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ===== GET ALL SESSIONS =====
    case 'get_sessions':
        $sessions = [];
        $sql = "
            SELECT cs.*,
                (SELECT COUNT(*) FROM chat_messages cm 
                 WHERE cm.session_id = cs.id AND cm.sender = 'user' AND cm.is_read = 0
                ) as unread_count,
                (SELECT cm2.message FROM chat_messages cm2 
                 WHERE cm2.session_id = cs.id 
                 ORDER BY cm2.id DESC LIMIT 1
                ) as last_message
            FROM chat_sessions cs
            ORDER BY 
                CASE cs.status 
                    WHEN 'waiting' THEN 0
                    WHEN 'open' THEN 1
                    WHEN 'answered' THEN 2
                    WHEN 'closed' THEN 3
                END ASC,
                cs.last_message_at DESC,
                cs.created_at DESC
        ";
        $r = $conn->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                // Truncate last_message for preview
                if (!empty($row['last_message'])) {
                    $msg = $row['last_message'];
                    // Clean up purchase request prefix for preview
                    $msg = str_replace("[PURCHASE REQUEST]\n", '[Purchase] ', $msg);
                    if (mb_strlen($msg) > 60) {
                        $msg = mb_substr($msg, 0, 60) . '...';
                    }
                    $row['last_message'] = $msg;
                }
                $sessions[] = $row;
            }
        }
        echo json_encode(['success' => true, 'sessions' => $sessions]);
        break;

    // ===== GET MESSAGES FOR A SESSION =====
    case 'get_messages':
        $session_id = (int)($_GET['session_id'] ?? 0);
        if ($session_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid session']);
            exit;
        }

        $messages = [];
        $stmt = $conn->prepare("SELECT id, session_id, sender, message, is_read, created_at FROM chat_messages WHERE session_id = ? ORDER BY id ASC");
        if ($stmt) {
            $stmt->bind_param('i', $session_id);
            $stmt->execute();
            $r = $stmt->get_result();
            while ($row = $r->fetch_assoc()) {
                $messages[] = $row;
            }
            $stmt->close();
        }

        // Mark user messages as read
        $stmt2 = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender = 'user' AND is_read = 0");
        if ($stmt2) {
            $stmt2->bind_param('i', $session_id);
            $stmt2->execute();
            $stmt2->close();
        }

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    // ===== SEND ADMIN REPLY =====
    case 'send_reply':
        $session_id = (int)($_POST['session_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($session_id <= 0 || $message === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            exit;
        }

        // Insert message
        $stmt = $conn->prepare("INSERT INTO chat_messages (session_id, sender, message, is_read, created_at) VALUES (?, 'admin', ?, 0, NOW())");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'DB error']);
            exit;
        }
        $stmt->bind_param('is', $session_id, $message);
        $stmt->execute();
        $msg_id = $stmt->insert_id;
        $stmt->close();

        // Update session status
        $conn->query("UPDATE chat_sessions SET status = 'answered', last_message_at = NOW(), updated_at = NOW() WHERE id = {$session_id}");

        echo json_encode(['success' => true, 'message_id' => $msg_id]);
        break;

    // ===== POLL NEW MESSAGES =====
    case 'poll_new':
        $session_id = (int)($_GET['session_id'] ?? 0);
        $last_id = (int)($_GET['last_id'] ?? 0);

        if ($session_id <= 0) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        $messages = [];
        $stmt = $conn->prepare("SELECT id, session_id, sender, message, is_read, created_at FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY id ASC");
        if ($stmt) {
            $stmt->bind_param('ii', $session_id, $last_id);
            $stmt->execute();
            $r = $stmt->get_result();
            while ($row = $r->fetch_assoc()) {
                $messages[] = $row;
            }
            $stmt->close();
        }

        // Mark user messages as read
        if (!empty($messages)) {
            $stmt2 = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender = 'user' AND is_read = 0");
            if ($stmt2) {
                $stmt2->bind_param('i', $session_id);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    // ===== GET USER INFO =====
    case 'get_user_info':
        $session_id = (int)($_GET['session_id'] ?? 0);
        if ($session_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid session']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM chat_sessions WHERE id = ? LIMIT 1");
        $info = null;
        if ($stmt) {
            $stmt->bind_param('i', $session_id);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if (!$info) {
            echo json_encode(['success' => false, 'error' => 'Session not found']);
            exit;
        }

        // Get visit & download counts for this IP
        $ip = $info['ip_address'];
        $visits = 0;
        $downloads = 0;

        $r = $conn->query("SELECT COUNT(*) as cnt FROM visits WHERE ip_address = '" . $conn->real_escape_string($ip) . "'");
        if ($r) $visits = (int)$r->fetch_assoc()['cnt'];

        $r = $conn->query("SELECT COUNT(*) as cnt FROM downloads WHERE ip_address = '" . $conn->real_escape_string($ip) . "'");
        if ($r) $downloads = (int)$r->fetch_assoc()['cnt'];

        // Get purchase requests for this session
        $purchase_requests = [];
        $stmt = $conn->prepare("SELECT * FROM purchase_requests WHERE session_hash = ? ORDER BY created_at DESC");
        if ($stmt) {
            $session_hash = $info['session_hash'] ?? '';
            $stmt->bind_param('s', $session_hash);
            $stmt->execute();
            $pr = $stmt->get_result();
            while ($row = $pr->fetch_assoc()) {
                $purchase_requests[] = $row;
            }
            $stmt->close();
        }

        $info['visits'] = $visits;
        $info['downloads'] = $downloads;
        $info['purchase_requests'] = $purchase_requests;

        echo json_encode(['success' => true, 'info' => $info]);
        break;

    // ===== CLOSE SESSION =====
    case 'close_session':
        $session_id = (int)($_POST['session_id'] ?? 0);
        if ($session_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid session']);
            exit;
        }

        $conn->query("UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = {$session_id}");

        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
?>