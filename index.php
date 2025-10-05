<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = pdo()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username']];
            header('Location: ' . APP_BASE . '/dashboard.php');
            exit;
        }
        $error = 'Invalid credentials.';
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-3">Login</div>
        <?php if ($error): ?>
          <div class="alert alert-danger" role="alert"><?=$error?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
          <div class="mb-3">
            <label class="form-label required">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Enter username" required>
          </div>
          <div class="mb-3">
            <label class="form-label required">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Login</button>
          
          </div>
        </form>
      </div>
    </div>
  </div>
  
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
