<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$login_error = null;
$register_error = null;
$register_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = trim($_POST['form'] ?? '');
    if ($form === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            $login_error = 'Username and password are required.';
        } else {
            $stmt = pdo()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username']];
                header('Location: ' . APP_BASE . '/dashboard.php');
                exit;
            }
            $login_error = 'Invalid credentials.';
        }
    } elseif ($form === 'borrower_register') {
        $name = trim($_POST['name'] ?? '');
        $borrower_id = trim($_POST['borrower_id'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        if ($name === '' || $borrower_id === '') {
            $register_error = 'Name and Borrower ID are required.';
        } else {
            try {
                $stmt = pdo()->prepare('SELECT COUNT(*) AS c FROM borrowers WHERE borrower_id = ?');
                $stmt->execute([$borrower_id]);
                $exists = (int)$stmt->fetch()['c'] > 0;
                if ($exists) {
                    $register_error = 'Borrower ID already exists. Please choose another.';
                } else {
                    pdo()->prepare('INSERT INTO borrowers (name, borrower_id, contact) VALUES (?,?,?)')
                        ->execute([$name, $borrower_id, $contact]);
                    $register_message = 'Registration successful. Your Borrower ID: ' . htmlspecialchars($borrower_id) . '.';
                }
            } catch (Throwable $e) {
                $register_error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
  <?php $showRegister = ($register_error || $register_message || (isset($_GET['register']) && $_GET['register'] === '1')); ?>
  <div id="loginPanel" class="col-md-5<?= $showRegister ? ' d-none' : '' ?>">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-3">Login</div>
        <?php if ($login_error): ?>
          <div class="alert alert-danger" role="alert"><?=$login_error?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="form" value="login">
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
            <button type="button" id="btnShowRegister" class="btn btn-outline-secondary">Register</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div id="registerPanel" class="col-md-7 mt-4 mt-md-0<?= $showRegister ? '' : ' d-none' ?>">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-3">Borrower Registration</div>
        <p class="text-muted small">Register to get a Borrower ID you can use at the library. Your ID should be unique (e.g., student number).</p>
        <?php if ($register_error): ?>
          <div class="alert alert-danger" role="alert"><?=$register_error?></div>
        <?php endif; ?>
        <?php if ($register_message): ?>
          <div class="alert alert-success" role="alert">
            <?=$register_message?>
            <?php if (!empty($_POST['borrower_id'])): ?>
            <div class="mt-2">
              <a class="btn btn-sm btn-success" href="<?=ROOT_BASE?>/borrowers/dashboard.php?borrower_id=<?=urlencode($_POST['borrower_id'])?>">Go to your dashboard</a>
            </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="form" value="borrower_register">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label required">Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
            </div>
            <div class="col-md-3">
              <label class="form-label required">Borrower ID</label>
              <input type="text" name="borrower_id" class="form-control" placeholder="e.g., 2025-1234" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" class="form-control" placeholder="Phone or email">
            </div>
          </div>
            <div class="mt-3 d-flex gap-2">
              <button type="submit" class="btn btn-outline-primary">Register</button>
              <button type="button" id="btnShowLogin" class="btn btn-outline-secondary">Back to Login</button>
            </div>
        </form>
      </div>
    </div>
  </div>
  
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
