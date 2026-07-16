<?php require_once __DIR__ . '/../auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' · Chatter' : 'Chatter' ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= !empty($chatPage) ? 'chat-body' : '' ?>">

<header class="site-header">
    <div class="wrap">
        <a class="brand" href="index.php">💬 Chatter</a>
        <nav>
            <a href="index.php">Chats</a>
            <?php if (isLoggedIn()): ?>
                <a href="new_chat.php">New Chat</a>
                <span class="who">Hi, <?= h(currentUsername()) ?></span>
                <a href="logout.php" class="btn-link">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="wrap <?= !empty($chatPage) ? 'chat-wrap' : '' ?>">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash <?= h($_SESSION['flash']['type']) ?>">
        <?= h($_SESSION['flash']['text']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
