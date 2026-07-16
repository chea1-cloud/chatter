<?php
require_once __DIR__ . '/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$me = currentUserId();
$db = getDB();

// Confirm the logged-in user is the sender before deleting
$stmt = $db->prepare('SELECT * FROM messages WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$msg = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

$redirectTo = 'index.php';

if ($msg && $msg['sender_id'] == $me) {
    $redirectTo = 'chat.php?with=' . (int)$msg['recipient_id'];

    // DELETE: remove the row
    $del = $db->prepare('DELETE FROM messages WHERE id = :id');
    $del->bindValue(':id', $id, SQLITE3_INTEGER);
    $del->execute();

    // Clean up the attached image file, if any
    if (!empty($msg['image']) && file_exists(UPLOAD_DIR . $msg['image'])) {
        unlink(UPLOAD_DIR . $msg['image']);
    }
} else {
    $_SESSION['flash'] = ['type' => 'error', 'text' => 'That message was not found or is not yours to delete.'];
}

header('Location: ' . $redirectTo);
exit;
