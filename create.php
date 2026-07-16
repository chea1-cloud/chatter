<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// This script only handles sending a message from the chat composer —
// it doesn't render its own page. If someone lands here directly, send
// them back to their inbox.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$me = currentUserId();
$recipientId = (int)($_POST['recipient_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

$db = getDB();

// Make sure the recipient is a real, different user (this is what keeps
// a message private to exactly one other person).
$stmt = $db->prepare('SELECT id FROM users WHERE id = :id');
$stmt->bindValue(':id', $recipientId, SQLITE3_INTEGER);
$validRecipient = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$validRecipient || $recipientId === $me) {
    $_SESSION['flash'] = ['type' => 'error', 'text' => 'Could not send that message — invalid recipient.'];
    header('Location: index.php');
    exit;
}

$errors = [];

// --- Basic form validation ---
if ($message === '' && empty($_FILES['image']['name'])) {
    $errors[] = 'Please enter a message or attach an image.';
} elseif (strlen($message) > 280) {
    $errors[] = 'Your message must be 280 characters or fewer.';
}

// --- Optional image upload ---
$storedFilename = null;
if (!empty($_FILES['image']['name'])) {
    $file = $_FILES['image'];
    $allowed = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'There was a problem uploading the image.';
    } elseif (!array_key_exists($ext, $allowed)) {
        $errors[] = 'Images must be jpg, png, or gif.';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $errors[] = 'Images must be smaller than 2MB.';
    } else {
        $storedFilename = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $storedFilename)) {
            $errors[] = 'Could not save the uploaded image.';
            $storedFilename = null;
        }
    }
}

if (!empty($errors)) {
    $_SESSION['flash'] = ['type' => 'error', 'text' => implode(' ', $errors)];
    header('Location: chat.php?with=' . $recipientId);
    exit;
}

// --- CREATE: insert the private message (encrypted at rest) ---
$stmt = $db->prepare('INSERT INTO messages (sender_id, recipient_id, message, image) VALUES (:sender, :recipient, :msg, :img)');
$stmt->bindValue(':sender', $me, SQLITE3_INTEGER);
$stmt->bindValue(':recipient', $recipientId, SQLITE3_INTEGER);
$stmt->bindValue(':msg', encryptMessage($message), SQLITE3_TEXT);
$stmt->bindValue(':img', $storedFilename, SQLITE3_TEXT);
$stmt->execute();

header('Location: chat.php?with=' . $recipientId);
exit;
