<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'New Chat';

$db = getDB();
$stmt = $db->prepare('SELECT id, username FROM users WHERE id != :me ORDER BY username COLLATE NOCASE');
$stmt->bindValue(':me', currentUserId(), SQLITE3_INTEGER);
$res = $stmt->execute();

$users = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}

require __DIR__ . '/includes/header.php';
?>

<h1>Start a Chat</h1>

<?php if (empty($users)): ?>
    <p class="muted">No other users have registered yet. Once someone else signs up, you'll be able to message them here.</p>
<?php else: ?>
    <div class="conv-list">
        <?php foreach ($users as $u): ?>
            <a class="conv-item" href="chat.php?with=<?= (int)$u['id'] ?>">
                <div class="avatar" style="background: <?= h(avatarColor($u['username'])) ?>">
                    <?= h(strtoupper(substr($u['username'], 0, 1))) ?>
                </div>
                <div class="conv-info">
                    <div class="conv-name"><?= h($u['username']) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
