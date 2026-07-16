<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$me = currentUserId();
$with = (int)($_GET['with'] ?? 0);

if ($with === $me || $with <= 0) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Confirm the other participant actually exists
$stmt = $db->prepare('SELECT id, username FROM users WHERE id = :id');
$stmt->bindValue(':id', $with, SQLITE3_INTEGER);
$other = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$other) {
    $_SESSION['flash'] = ['type' => 'error', 'text' => 'That user could not be found.'];
    header('Location: index.php');
    exit;
}

// READ: only messages exchanged between exactly these two people —
// this is what makes the conversation private.
$stmt = $db->prepare("
    SELECT * FROM messages
    WHERE (sender_id = :me AND recipient_id = :with)
       OR (sender_id = :with AND recipient_id = :me)
    ORDER BY created_at ASC
");
$stmt->bindValue(':me', $me, SQLITE3_INTEGER);
$stmt->bindValue(':with', $with, SQLITE3_INTEGER);
$res = $stmt->execute();

$messages = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $row['message'] = decryptMessage($row['message']);
    $messages[] = $row;
}

$pageTitle = $other['username'];
$chatPage = true;

require __DIR__ . '/includes/header.php';
?>

<div class="chat-app">
    <div class="chat-thread-head">
        <a href="index.php" class="back-link" aria-label="Back to chats">←</a>
        <div class="avatar" style="background: <?= h(avatarColor($other['username'])) ?>">
            <?= h(strtoupper(substr($other['username'], 0, 1))) ?>
        </div>
        <div class="chat-thread-name"><?= h($other['username']) ?></div>
    </div>

    <div class="chat-messages" id="chat-messages">
        <?php if (empty($messages)): ?>
            <p class="muted center">No messages yet. Say hi to <?= h($other['username']) ?> 👋</p>
        <?php endif; ?>

        <?php foreach ($messages as $row):
            $isMine = $row['sender_id'] == $me;
        ?>
            <div class="msg-row <?= $isMine ? 'mine' : 'theirs' ?>">
                <div class="bubble-wrap">
                    <div class="bubble">
                        <?php if (!empty($row['image'])): ?>
                            <img class="bubble-image" src="<?= h(UPLOAD_URL . $row['image']) ?>" alt="attached image">
                        <?php endif; ?>
                        <?php if (trim($row['message']) !== ''): ?>
                            <p class="bubble-text"><?= nl2br(h($row['message'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="msg-meta">
                        <span class="msg-time"><?= h(formatChatTime($row['created_at'])) ?><?= $row['updated_at'] ? ' · edited' : '' ?></span>
                        <?php if ($isMine): ?>
                            <a href="edit.php?id=<?= (int)$row['id'] ?>" class="msg-action">Edit</a>
                            <form action="delete.php" method="post" onsubmit="return confirm('Delete this message?');" class="inline-form">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="msg-action danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <form action="create.php" method="post" enctype="multipart/form-data" class="composer">
        <input type="hidden" name="recipient_id" value="<?= (int)$with ?>">
        <label class="composer-attach" for="composer-image" title="Attach image">📎</label>
        <input type="file" id="composer-image" name="image" accept=".jpg,.jpeg,.png,.gif" class="composer-file-input" onchange="this.form.querySelector('.composer-input').placeholder = this.files.length ? this.files[0].name : 'Type a message...';">
        <input type="text" name="message" maxlength="280" placeholder="Type a message..." class="composer-input" autocomplete="off">
        <button type="submit" class="composer-send" aria-label="Send">➤</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
