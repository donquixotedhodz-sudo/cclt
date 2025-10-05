<?php
require_once __DIR__ . '/config.php';

// Create database and tables if they don't exist, and seed an admin user
try {
    $rootDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $pdo = new PDO($rootDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    // Create tables
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  isbn VARCHAR(50) UNIQUE,
  category VARCHAR(100),
  quantity INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS borrowers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  borrower_id VARCHAR(100) UNIQUE NOT NULL,
  contact VARCHAR(255),
  password_hash VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);
SQL);

    // Ensure password_hash column exists (for existing installations)
    try {
        $col = $pdo->query("SHOW COLUMNS FROM borrowers LIKE 'password_hash'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE borrowers ADD COLUMN password_hash VARCHAR(255) NULL AFTER contact");
        }
    } catch (Throwable $e) {
        // ignore
    }

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT NOT NULL,
  book_id INT NOT NULL,
  borrow_date DATE NOT NULL,
  due_date DATE NOT NULL,
  return_date DATE NULL,
  status ENUM('Borrowed','Returned') NOT NULL DEFAULT 'Borrowed',
  late_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (borrower_id) REFERENCES borrowers(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);
SQL);

    // Seed admin user if not exists
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    $exists = (int)$stmt->fetch()['c'] > 0;
    if (!$exists) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)')->execute(['admin', $hash]);
    }

    $message = 'Setup complete. Admin user: admin / admin123';
} catch (Throwable $e) {
    $message = 'Setup error: ' . $e->getMessage();
}
include __DIR__ . '/../partials/admin_header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">System Setup</div>
        <p class="mb-3"><?=$message?></p>
        <a href="<?=APP_BASE?>/index.php" class="btn btn-primary">Go to Login</a>
      </div>
    </div>
  </div>
  <div class="col-md-8 mt-3">
    <div class="alert alert-info">If you changed DB credentials, update <code>config.php</code> accordingly.</div>
  </div>
  
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>