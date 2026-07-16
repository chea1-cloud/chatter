<?php
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Register';
$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // --- Basic form validation ---
    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters (letters, numbers, underscore only).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $db = getDB();
        $check = $db->prepare('SELECT id FROM users WHERE username = :u');
        $check->bindValue(':u', $username, SQLITE3_TEXT);
        if ($check->execute()->fetchArray()) {
            $errors[] = 'That username is already taken.';
        }
    }

    // --- CREATE: insert new user ---
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :p)');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':p', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt->execute();

        $_SESSION['user_id'] = $db->lastInsertRowID();
        $_SESSION['username'] = $username;
        $_SESSION['flash'] = ['type' => 'success', 'text' => 'Welcome to Chatter, ' . $username . '!'];
        header('Location: index.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<h1>Register</h1>

<?php foreach ($errors as $err): ?>
    <div class="flash error"><?= h($err) ?></div>
<?php endforeach; ?>

<form action="register.php" method="post" class="form">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" value="<?= h($username) ?>" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <label for="confirm">Confirm Password</label>
    <input type="password" id="confirm" name="confirm" required>

    <button type="submit" class="btn">Create Account</button>
</form>
<p class="muted">Already have an account? <a href="login.php">Log in</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>
