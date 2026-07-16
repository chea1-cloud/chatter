<?php
/**
 * config.php
 * Handles the SQLite3 database connection and makes sure the
 * required tables exist. This is the ONE place the rest of the
 * app goes to for a database handle.
 */

// Where the SQLite database file lives on disk
define('DB_PATH', __DIR__ . '/database/chatter.db');

// Where uploaded images are stored
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

/**
 * Encryption at rest for message text.
 *
 * In a real deployment this key should come from an environment
 * variable (e.g. getenv('CHATTER_ENCRYPTION_KEY')) or a secrets
 * manager — never committed to source control. For this project it's
 * derived from a fixed passphrase so the app runs out of the box;
 * change CHATTER_KEY_PASSPHRASE before using this anywhere real.
 */
define('CHATTER_KEY_PASSPHRASE', 'change-me-before-deploying');
// crypto_secretbox needs a 32-byte key; hash() with raw output gives us exactly that.
define('ENCRYPTION_KEY', hash('sha256', CHATTER_KEY_PASSPHRASE, true));

/**
 * Encrypt a plaintext message for storage using libsodium's
 * crypto_secretbox (XSalsa20-Poly1305). The sodium extension ships
 * built into PHP by default from 7.2 onward, so unlike openssl it
 * doesn't need to be enabled in php.ini.
 * Returns base64( nonce . ciphertext ), or '' for an empty string so
 * blank messages (image-only posts) still store cleanly.
 */
function encryptMessage(string $plaintext): string {
    if ($plaintext === '') {
        return '';
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); // 24 bytes
    $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, ENCRYPTION_KEY);
    return base64_encode($nonce . $ciphertext);
}

/**
 * Decrypt a message that was stored with encryptMessage().
 * Fails safe: returns a placeholder string instead of throwing if the
 * data is malformed (e.g. old plaintext rows from before encryption
 * was added), so a bad row can't crash the whole chat thread.
 */
function decryptMessage(?string $stored): string {
    if ($stored === null || $stored === '') {
        return '';
    }
    $data = base64_decode($stored, true);
    if ($data === false || strlen($data) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return '[unreadable message]';
    }
    $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, ENCRYPTION_KEY);
    return $plaintext !== false ? $plaintext : '[unreadable message]';
}

/**
 * Open (and if necessary create) the SQLite database and
 * make sure our tables exist.
 */
function getDB(): SQLite3 {
    static $db = null;

    if ($db === null) {
        $db = new SQLite3(DB_PATH);
        $db->enableExceptions(true);
        $db->exec('PRAGMA foreign_keys = ON;');
        createTables($db);
    }

    return $db;
}

function createTables(SQLite3 $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            recipient_id INTEGER NOT NULL,
            message TEXT NOT NULL DEFAULT '',
            image TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
}
