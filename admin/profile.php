<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$message_username = null;
$error_username = null;
$message_password = null;
$error_password = null;

$userId = (int)$_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = trim($_POST['form'] ?? '');
    if ($form === 'update_username') {
        $newUsername = trim($_POST['username'] ?? '');
        if ($newUsername === '') {
            $error_username = 'Username is required.';
        } else {
            try {
                $stmt = pdo()->prepare('SELECT COUNT(*) AS c FROM users WHERE username = ? AND id != ?');
                $stmt->execute([$newUsername, $userId]);
                $exists = (int)$stmt->fetch()['c'] > 0;
                if ($exists) {
                    $error_username = 'Username is already taken.';
                } else {
                    pdo()->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$newUsername, $userId]);
                    $_SESSION['user']['username'] = $newUsername;
                    $message_username = 'Username updated successfully.';
                }
            } catch (Throwable $e) {
                $error_username = 'Failed to update username: ' . $e->getMessage();
            }
        }
    } elseif ($form === 'change_password') {
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $error_password = 'All password fields are required.';
        } elseif (strlen($newPassword) < 6) {
            $error_password = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error_password = 'New password and confirmation do not match.';
        } else {
            try {
                $stmt = pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                    $error_password = 'Current password is incorrect.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
                    $message_password = 'Password changed successfully.';
                }
            } catch (Throwable $e) {
                $error_password = 'Failed to change password: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../partials/admin_header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-body">
        <div class="page-title mb-2">Profile</div>
        <p class="text-muted small">Update your username or change your password.</p>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="mb-3"><strong>Current Username:</strong> <?=htmlspecialchars($_SESSION['user']['username'])?></div>
            <?php if ($error_username): ?><div class="alert alert-danger" role="alert"><?=$error_username?></div><?php endif; ?>
            <?php if ($message_username): ?><div class="alert alert-success" role="alert"><?=$message_username?></div><?php endif; ?>
            <form method="post" autocomplete="off">
              <input type="hidden" name="form" value="update_username">
              <div class="mb-3">
                <label class="form-label required">New Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter new username" required>
              </div>
              <button type="submit" class="btn btn-primary">Update Username</button>
            </form>
          </div>
          <div class="col-md-6">
            <?php if ($error_password): ?><div class="alert alert-danger" role="alert"><?=$error_password?></div><?php endif; ?>
            <?php if ($message_password): ?><div class="alert alert-success" role="alert"><?=$message_password?></div><?php endif; ?>
            <form method="post" autocomplete="off">
              <input type="hidden" name="form" value="change_password">
              <div class="mb-3">
                <label class="form-label required">Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
              </div>
              <div class="mb-3">
                <label class="form-label required">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password (min 6)" required>
              </div>
              <div class="mb-3">
                <label class="form-label required">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
              </div>
              <button type="submit" class="btn btn-outline-primary">Change Password</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>