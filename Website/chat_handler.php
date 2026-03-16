<?php
session_start();
require_once __DIR__ . '/../Datenbank/dbconectio.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check if user is admin
$is_admin = false;
try {
    $stmt = $conn->prepare("SELECT admin FROM user WHERE uid = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && ($user['admin'] === 'ja' || $user_id == 3)) {
        $is_admin = true;
    }
} catch (Exception $e) {}

if ($action === 'send') {
    $message = $_POST['message'] ?? '';
    // If admin is sending, they must provide the target user_id
    $target_user_id = $is_admin ? (int)($_POST['target_user_id'] ?? 0) : $user_id;
    $sender_type = $is_admin ? 'admin' : 'user';

    if (empty($message) || $target_user_id <= 0) {
        echo json_encode(['error' => 'Invalid data']);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender_type, message) VALUES (?, ?, ?)");
        $stmt->execute([$target_user_id, $sender_type, $message]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} 
elseif ($action === 'fetch') {
    // If admin is fetching, they must specify which user's chat
    $target_user_id = $is_admin ? (int)($_GET['target_user_id'] ?? 0) : $user_id;
    
    if ($target_user_id <= 0) {
        echo json_encode(['messages' => []]);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY timestamp ASC");
        $stmt->execute([$target_user_id]);
        $messages = $stmt->fetchAll();

        // Mark as read if receiving messages from the other side
        $mark_read_type = $is_admin ? 'user' : 'admin';
        $update = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND sender_type = ? AND is_read = 0");
        $update->execute([$target_user_id, $mark_read_type]);

        echo json_encode(['messages' => $messages, 'is_admin' => $is_admin]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($action === 'list_users' && $is_admin) {
    // Admin needs to see who sent messages
    try {
        $sql = "SELECT DISTINCT u.uid, u.vorname, u.nachname, u.email,
                (SELECT COUNT(*) FROM chat_messages cm2 WHERE cm2.user_id = u.uid AND cm2.is_read = 0 AND cm2.sender_type = 'user') as unread_count,
                (SELECT MAX(timestamp) FROM chat_messages cm3 WHERE cm3.user_id = u.uid) as last_msg
                FROM user u
                JOIN chat_messages cm ON u.uid = cm.user_id
                ORDER BY unread_count DESC, last_msg DESC";
        $stmt = $conn->query($sql);
        echo json_encode(['users' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
