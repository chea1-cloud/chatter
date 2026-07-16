<?php
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Login';
$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :u');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Incorrect username or password.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'Welcome back, ' . $user['username'] . '!'];
            header('Location: index.php');
            exit;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<h1>Login</h1>

<?php foreach ($errors as $err): ?>
    <div class="flash error"><?= h($err) ?></div>
<?php endforeach; ?>

<form action="login.php" method="post" class="form">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" value="<?= h($username) ?>" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" class="btn">Log In</button>
</form>
<p class="muted">No account yet? <a href="register.php">Register</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>
