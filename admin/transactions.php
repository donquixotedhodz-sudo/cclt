<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$action = $_GET['action'] ?? 'list';
$message = null;

function redirect_tx_list() {
    header('Location: ' . APP_BASE . '/transactions.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form'] === 'borrow') {
        $borrower_id = (int)$_POST['borrower_id'];
        $book_id = (int)$_POST['book_id'];
        $borrow_date = $_POST['borrow_date'] ?: date('Y-m-d');
        $due_date = $_POST['due_date'] ?: date('Y-m-d', strtotime('+14 days', strtotime($borrow_date)));

        // Check book availability
        $book = pdo()->prepare('SELECT * FROM books WHERE id=?');
        $book->execute([$book_id]);
        $b = $book->fetch();
        if (!$b) {
            $message = 'Book not found.';
        } elseif ((int)$b['quantity'] <= 0) {
            $message = 'Book currently unavailable.';
        } else {
            // Create transaction and reduce quantity
            pdo()->prepare('INSERT INTO transactions (borrower_id, book_id, borrow_date, due_date, status) VALUES (?,?,?,?,"Borrowed")')
                ->execute([$borrower_id, $book_id, $borrow_date, $due_date]);
            pdo()->prepare('UPDATE books SET quantity = quantity - 1 WHERE id=?')->execute([$book_id]);
            redirect_tx_list();
        }
    }
    if ($_POST['form'] === 'return') {
        $tx_id = (int)$_POST['tx_id'];
        $txStmt = pdo()->prepare('SELECT * FROM transactions WHERE id=?');
        $txStmt->execute([$tx_id]);
        $tx = $txStmt->fetch();
        if (!$tx || $tx['status'] !== 'Borrowed') {
            $message = 'Transaction not found or already returned.';
        } else {
            $return_date = date('Y-m-d');
            $late_days = max(0, (int)((strtotime($return_date) - strtotime($tx['due_date'])) / 86400));
            $late_fee = $late_days * LATE_FEE_PER_DAY;
            pdo()->prepare('UPDATE transactions SET return_date=?, status="Returned", late_fee=? WHERE id=?')
                ->execute([$return_date, $late_fee, $tx_id]);
            // Increase book quantity
            pdo()->prepare('UPDATE books SET quantity = quantity + 1 WHERE id=?')->execute([$tx['book_id']]);
            redirect_tx_list();
        }
    }
}

$borrowers = pdo()->query('SELECT id, name, borrower_id FROM borrowers ORDER BY name ASC')->fetchAll();
$booksAvail = pdo()->query('SELECT id, title, author, quantity FROM books ORDER BY title ASC')->fetchAll();

include __DIR__ . '/../partials/admin_header.php';
?>
<div class="row g-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Borrow a Book</div>
        <?php if ($message && ($action==='borrow' || $_SERVER['REQUEST_METHOD']==='POST')): ?>
          <div class="alert alert-warning"><?=$message?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="form" value="borrow">
          <div class="mb-3">
            <label class="form-label required">Borrower</label>
            <select class="form-select" name="borrower_id" required>
              <option value="">Select borrower</option>
              <?php foreach ($borrowers as $br): ?>
                <option value="<?=$br['id']?>"><?=htmlspecialchars($br['name'])?> (<?=htmlspecialchars($br['borrower_id'])?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label required">Book</label>
            <select class="form-select" name="book_id" required>
              <option value="">Select book</option>
              <?php foreach ($booksAvail as $bk): ?>
                <option value="<?=$bk['id']?>" <?=$bk['quantity']>0?'':'disabled'?>><?=htmlspecialchars($bk['title'])?> — <?=htmlspecialchars($bk['author'])?> (Qty: <?=$bk['quantity']?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Borrow Date</label>
              <input type="date" name="borrow_date" class="form-control" value="<?=date('Y-m-d')?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" class="form-control" value="<?=date('Y-m-d', strtotime('+14 days'))?>">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary" type="submit">Record Borrow</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Return a Book</div>
        <div class="small text-muted mb-2">Use the table below to find borrowed books by borrower name, ID, or title and click Return.</div>
        <input type="text" class="form-control mb-2" placeholder="Search borrowed items..." data-table-filter="#borrowedTable">
        <?php if ($message && $_POST['form']==='return'): ?>
          <div class="alert alert-warning"><?=$message?></div>
        <?php endif; ?>
        <?php
          $bPage = max(1, (int)($_GET['bpage'] ?? 1));
          $bPer = max(1, min(100, (int)($_GET['bper_page'] ?? 10)));
          $bTotal = (int)pdo()->query("SELECT COUNT(*) AS c FROM transactions WHERE status='Borrowed'")->fetch()['c'];
          $bTotalPages = max(1, (int)ceil($bTotal / $bPer));
          $bPage = min($bPage, $bTotalPages);
          $bOffset = ($bPage - 1) * $bPer;
          $borrowedRows = pdo()->query("SELECT t.*, b.title, b.author, br.name, br.borrower_id FROM transactions t JOIN books b ON t.book_id=b.id JOIN borrowers br ON t.borrower_id=br.id WHERE t.status='Borrowed' ORDER BY t.borrow_date DESC LIMIT $bPer OFFSET $bOffset")->fetchAll();
        ?>
        <div class="table-responsive">
          <table class="table table-striped" id="borrowedTable">
            <thead>
              <tr>
                <th>Borrower</th>
                <th>Book</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($borrowedRows as $r): ?>
                <tr>
                  <td><?=htmlspecialchars($r['name'])?> (<?=htmlspecialchars($r['borrower_id'])?>)</td>
                  <td><?=htmlspecialchars($r['title'])?> — <?=htmlspecialchars($r['author'])?></td>
                  <td><?=htmlspecialchars($r['borrow_date'])?></td>
                  <td><?=htmlspecialchars($r['due_date'])?></td>
                  <td>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="form" value="return">
                      <input type="hidden" name="tx_id" value="<?=$r['id']?>">
                      <button class="btn btn-sm btn-outline-success" type="submit">Return Book</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="small text-muted">Showing <?=min($bOffset+1, $bTotal)?>–<?=min($bOffset+$bPer, $bTotal)?> of <?=$bTotal?></div>
          <nav aria-label="Borrowed pagination">
            <ul class="pagination pagination-sm mb-0">
              <?php $bpPrev = max(1, $bPage-1); $bpNext = min($bTotalPages, $bPage+1); ?>
              <li class="page-item <?=$bPage<=1?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/transactions.php?bpage=<?=$bpPrev?>">Prev</a></li>
              <li class="page-item disabled"><span class="page-link">Page <?=$bPage?> of <?=$bTotalPages?></span></li>
              <li class="page-item <?=$bPage>=$bTotalPages?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/transactions.php?bpage=<?=$bpNext?>">Next</a></li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">All Transactions</div>
        <?php
        $tPage = max(1, (int)($_GET['tpage'] ?? 1));
        $tPer = max(1, min(100, (int)($_GET['tper_page'] ?? 10)));
        $tTotal = (int)pdo()->query('SELECT COUNT(*) AS c FROM transactions')->fetch()['c'];
        $tTotalPages = max(1, (int)ceil($tTotal / $tPer));
        $tPage = min($tPage, $tTotalPages);
        $tOffset = ($tPage - 1) * $tPer;
        $allTx = pdo()->query("SELECT t.*, b.title, br.name FROM transactions t JOIN books b ON t.book_id=b.id JOIN borrowers br ON t.borrower_id=br.id ORDER BY t.created_at DESC LIMIT $tPer OFFSET $tOffset")->fetchAll();
        ?>
        <div class="table-responsive">
          <table class="table table-striped" id="allTransactions">
            <thead>
              <tr>
                <th>Borrower</th>
                <th>Book</th>
                <th>Borrowed</th>
                <th>Due</th>
                <th>Returned</th>
                <th>Status</th>
                <th>Late Fee</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allTx as $t): ?>
                <tr>
                  <td><?=htmlspecialchars($t['name'])?></td>
                  <td><?=htmlspecialchars($t['title'])?></td>
                  <td><?=htmlspecialchars($t['borrow_date'])?></td>
                  <td><?=htmlspecialchars($t['due_date'])?></td>
                  <td><?=htmlspecialchars($t['return_date'])?></td>
                  <td><?=$t['status']?></td>
                  <td>$<?=number_format((float)$t['late_fee'],2)?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="small text-muted">Showing <?=min($tOffset+1, $tTotal)?>–<?=min($tOffset+$tPer, $tTotal)?> of <?=$tTotal?></div>
          <nav aria-label="Transactions pagination">
            <ul class="pagination pagination-sm mb-0">
              <?php $tpPrev = max(1, $tPage-1); $tpNext = min($tTotalPages, $tPage+1); ?>
              <li class="page-item <?=$tPage<=1?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/transactions.php?tpage=<?=$tpPrev?>">Prev</a></li>
              <li class="page-item disabled"><span class="page-link">Page <?=$tPage?> of <?=$tTotalPages?></span></li>
              <li class="page-item <?=$tPage>=$tTotalPages?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/transactions.php?tpage=<?=$tpNext?>">Next</a></li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>