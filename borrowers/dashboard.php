<?php
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$message = null;
$borrower = null;

$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$codeParam = trim($_GET['borrower_id'] ?? '');
try {
    if ($idParam) {
        $stmt = pdo()->prepare('SELECT * FROM borrowers WHERE id = ?');
        $stmt->execute([$idParam]);
        $borrower = $stmt->fetch();
    } elseif ($codeParam !== '') {
        $stmt = pdo()->prepare('SELECT * FROM borrowers WHERE borrower_id = ?');
        $stmt->execute([$codeParam]);
        $borrower = $stmt->fetch();
    }
    if (!$borrower && ($idParam || $codeParam !== '')) {
        $message = 'Borrower not found. Please check your ID.';
    }
} catch (Throwable $e) {
    $message = 'Error: ' . $e->getMessage();
}

include __DIR__ . '/../partials/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Borrower Dashboard</div>
        <p class="text-muted small">View your borrowing history using your Borrower ID.</p>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between mb-3">
          <div class="mb-2 mb-md-0">
            <?php if ($borrower): ?>
              <div class="d-flex flex-wrap gap-3">
                <span><strong>Name:</strong> <?=htmlspecialchars($borrower['name'])?></span>
                <span><strong>Borrower ID:</strong> <?=htmlspecialchars($borrower['borrower_id'])?></span>
                <?php if (!empty($borrower['contact'])): ?>
                  <span><strong>Contact:</strong> <?=htmlspecialchars($borrower['contact'])?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="ms-md-auto">
            <a class="btn btn-outline-secondary" href="<?=ROOT_BASE?>/index.php">Back to Home</a>
          </div>
        </div>

        <?php if ($message): ?>
          <div class="alert alert-warning" role="alert"><?=$message?></div>
        <?php endif; ?>

        <?php if ($borrower): ?>

          <?php
            $txRows = [];
            try {
                $stmt = pdo()->prepare('SELECT t.*, b.title, b.author FROM transactions t JOIN books b ON t.book_id=b.id WHERE t.borrower_id = ? ORDER BY t.created_at DESC');
                $stmt->execute([$borrower['id']]);
                $txRows = $stmt->fetchAll();
            } catch (Throwable $e) {
                echo '<div class="alert alert-danger">Failed to load transactions: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
          ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Book</th>
                  <th>Author</th>
                  <th>Status</th>
                  <th>Borrowed</th>
                  <th>Due</th>
                  <th>Returned</th>
                  <th>Late Fee</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($txRows) === 0): ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted">No transactions found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($txRows as $r): ?>
                    <tr>
                      <td><?=htmlspecialchars($r['title'])?></td>
                      <td><?=htmlspecialchars($r['author'])?></td>
                      <td><?=$r['status']?></td>
                      <td><?=htmlspecialchars($r['borrow_date'])?></td>
                      <td><?=htmlspecialchars($r['due_date'])?></td>
                      <td><?=htmlspecialchars($r['return_date'] ?? '')?></td>
                      <td><?=number_format((float)$r['late_fee'], 2)?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>