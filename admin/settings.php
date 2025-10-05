<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add_admin') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    if ($username === '' || $password === '' || $confirm === '') {
      $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
      $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
      $error = 'Password must be at least 6 characters.';
    } else {
      try {
        $stmt = pdo()->prepare('SELECT COUNT(*) AS c FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ((int)$stmt->fetch()['c'] > 0) {
          $error = 'Username already exists.';
        } else {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          pdo()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)')->execute([$username, $hash]);
          $message = 'Admin user added successfully.';
        }
      } catch (Throwable $e) {
        $error = 'Error adding user: ' . $e->getMessage();
      }
    }
  }
}

// Load admins for display
$admins = pdo()->query('SELECT id, username, created_at FROM users ORDER BY username ASC')->fetchAll();

include __DIR__ . '/../partials/admin_header.php';
?>
<div class="row g-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Account Settings</div>
        <p class="text-muted">Add additional admin accounts and manage theme preferences.</p>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=$error?></div><?php endif; ?>
        <?php if ($message): ?><div class="alert alert-success" role="alert"><?=$message?></div><?php endif; ?>

        <h6 class="mt-3">Add Admin</h6>
        <form method="post" autocomplete="off" class="mt-2">
          <input type="hidden" name="action" value="add_admin">
          <div class="mb-2">
            <label class="form-label required">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Enter username" required>
          </div>
          <div class="mb-2">
            <label class="form-label required">Password</label>
            <div class="input-with-toggle position-relative">
              <input type="password" name="password" class="form-control pe-5" placeholder="Create a password (min 6)" required>
              <button type="button" class="password-toggle btn btn-sm btn-link d-none" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label required">Confirm Password</label>
            <div class="input-with-toggle position-relative">
              <input type="password" name="confirm_password" class="form-control pe-5" placeholder="Re-enter password" required>
              <button type="button" class="password-toggle btn btn-sm btn-link d-none" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Add Admin</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Theme Preferences</div>
        <p class="text-muted">Choose your display mode. Preference is saved to your browser.</p>
        <div class="d-flex align-items-center gap-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="themeSwitch">
            <label class="form-check-label" for="themeSwitch">Dark mode</label>
          </div>
          <button type="button" id="btnResetTheme" class="btn btn-sm btn-outline-secondary">Reset</button>
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <div class="page-title mb-2">Admins</div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Username</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $a): ?>
                <tr>
                  <td><?=htmlspecialchars($a['username'])?></td>
                  <td><?=htmlspecialchars($a['created_at'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Theme persistence via localStorage
  (function() {
    const key = 'cclt_theme';
    const current = localStorage.getItem(key) || 'light';
    const apply = (mode) => {
      const cls = 'theme-dark';
      if (mode === 'dark') {
        document.body.classList.add(cls);
      } else {
        document.body.classList.remove(cls);
      }
    };
    apply(current);
    const switchEl = document.getElementById('themeSwitch');
    if (switchEl) {
      switchEl.checked = (current === 'dark');
      switchEl.addEventListener('change', () => {
        const mode = switchEl.checked ? 'dark' : 'light';
        localStorage.setItem(key, mode);
        apply(mode);
      });
    }
    const resetBtn = document.getElementById('btnResetTheme');
    resetBtn?.addEventListener('click', () => {
      localStorage.removeItem(key);
      apply('light');
      if (switchEl) switchEl.checked = false;
    });
  })();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>