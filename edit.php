<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'Edit Message';
$db = getDB();
$me = currentUserId();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Fetch the message and confirm the logged-in user is the sender
$stmt = $db->prepare('SELECT * FROM messages WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$msg = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$msg || $msg['sender_id'] != $me) {
    $_SESSION['flash'] = ['type' => 'error', 'text' => 'That message was not found or is not yours to edit.'];
    header('Location: index.php');
    exit;
}

$recipientId = (int)$msg['recipient_id'];
$errors = [];
$message = decryptMessage($msg['message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    // --- Basic form validation ---
    if ($message === '' && empty($msg['image']) && empty($_FILES['image']['name'])) {
        $errors[] = 'Your message cannot be empty.';
    } elseif (strlen($message) > 280) {
        $errors[] = 'Your message must be 280 characters or fewer.';
    }

    // --- Optional: replace the image ---
    $storedFilename = $msg['image'];
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
            $newFilename = bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newFilename)) {
                if ($storedFilename && file_exists(UPLOAD_DIR . $storedFilename)) {
                    unlink(UPLOAD_DIR . $storedFilename);
                }
                $storedFilename = $newFilename;
            } else {
                $errors[] = 'Could not save the uploaded image.';
            }
        }
    }

    // --- UPDATE: save changes ---
    if (empty($errors)) {
        $upd = $db->prepare("UPDATE messages SET message = :msg, image = :img, updated_at = datetime('now') WHERE id = :id");
        $upd->bindValue(':msg', encryptMessage($message), SQLITE3_TEXT);
        $upd->bindValue(':img', $storedFilename, SQLITE3_TEXT);
        $upd->bindValue(':id', $id, SQLITE3_INTEGER);
        $upd->execute();

        header('Location: chat.php?with=' . $recipientId);
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<h1>Edit Message</h1>

<?php foreach ($errors as $err): ?>
    <div class="flash error"><?= h($err) ?></div>
<?php endforeach; ?>

<form action="edit.php" method="post" enctype="multipart/form-data" class="form" id="chatter-form">
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <label for="message">Update your message</label>
    <textarea id="message" name="message" maxlength="280" rows="4"><?= h($message) ?></textarea>
    <div class="char-count" id="char-count">280 characters left</div>

    <?php if (!empty($msg['image'])): ?>
        <p class="muted">Current image:</p>
        <img class="chatter-image" src="<?= h(UPLOAD_URL . $msg['image']) ?>" alt="current attached image">
    <?php endif; ?>

    <label for="image">Replace image (optional)</label>
    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif">

    <button type="submit" class="btn">Save Changes</button>
    <a href="chat.php?with=<?= (int)$recipientId ?>" class="btn-link">Cancel</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
