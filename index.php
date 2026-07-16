<?php
require_once __DIR__ . '/auth.php';

$pageTitle = 'Chats';

$conversations = [];
if (isLoggedIn()) {
    $db = getDB();
    $me = currentUserId();

    // Find every conversation partner, with their most recent message
    $stmt = $db->prepare("
        SELECT
            u.id AS other_id,
            u.username,
            m.message,
            m.image,
            m.created_at,
            m.sender_id
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.sender_id = :me THEN m.recipient_id ELSE m.sender_id END
        WHERE m.sender_id = :me OR m.recipient_id = :me
        AND m.created_at = (
            SELECT MAX(m2.created_at) FROM messages m2
            WHERE (m2.sender_id = :me AND m2.recipient_id = u.id)
               OR (m2.recipient_id = :me AND m2.sender_id = u.id)
        )
        GROUP BY u.id
        ORDER BY m.created_at DESC
    ");
    $stmt->bindValue(':me', $me, SQLITE3_INTEGER);
    $res = $stmt->execute();
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $row['message'] = decryptMessage($row['message']);
        $conversations[] = $row;
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="inbox-head">
    <h1>Chats</h1>
    <?php if (isLoggedIn()): ?>
        <a href="new_chat.php" class="btn">+ New Chat</a>
    <?php endif; ?>
</div>

<?php if (!isLoggedIn()): ?>
    <p class="muted">
        <a href="login.php">Log in</a> or <a href="register.php">register</a> to see your private chats.
    </p>
<?php elseif (empty($conversations)): ?>
    <p class="muted">You don't have any conversations yet. Start one with "+ New Chat".</p>
<?php else: ?>
    <div class="conv-list">
        <?php foreach ($conversations as $c): ?>
            <a class="conv-item" href="chat.php?with=<?= (int)$c['other_id'] ?>">
                <div class="avatar" style="background: <?= h(avatarColor($c['username'])) ?>">
                    <?= h(strtoupper(substr($c['username'], 0, 1))) ?>
                </div>
                <div class="conv-info">
                    <div class="conv-name"><?= h($c['username']) ?></div>
                    <div class="conv-preview">
                        <?= $c['sender_id'] == currentUserId() ? 'You: ' : '' ?>
                        <?= h($c['message'] !== '' ? $c['message'] : ($c['image'] ? '📷 Photo' : '')) ?>
                    </div>
                </div>
                <div class="conv-time"><?= h(formatChatTime($c['created_at'])) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
