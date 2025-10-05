<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$q = trim($_GET['q'] ?? '');

// Borrowed list
$borrowed = pdo()->query('SELECT t.*, b.title, b.author, br.name, br.borrower_id FROM transactions t JOIN books b ON t.book_id=b.id JOIN borrowers br ON t.borrower_id=br.id WHERE t.status="Borrowed" ORDER BY t.borrow_date DESC')->fetchAll();

// Returned list
$returned = pdo()->query('SELECT t.*, b.title, br.name FROM transactions t JOIN books b ON t.book_id=b.id JOIN borrowers br ON t.borrower_id=br.id WHERE t.status="Returned" ORDER BY t.return_date DESC')->fetchAll();

// Most borrowed books (top 10)
$mostBorrowed = pdo()->query('SELECT b.title, b.author, COUNT(*) AS times_borrowed FROM transactions t JOIN books b ON t.book_id=b.id GROUP BY t.book_id ORDER BY times_borrowed DESC LIMIT 10')->fetchAll();

// Borrower history (if q matches borrower id or name)
$borrowerHistory = [];
if ($q !== '') {
    $stmt = pdo()->prepare('SELECT t.*, b.title, br.name FROM transactions t JOIN books b ON t.book_id=b.id JOIN borrowers br ON t.borrower_id=br.id WHERE br.borrower_id LIKE ? OR br.name LIKE ? ORDER BY t.created_at DESC');
    $like = "%$q%";
    $stmt->execute([$like, $like]);
    $borrowerHistory = $stmt->fetchAll();
}

// Search by title/author/borrower across transactions
$searchResults = [];
if ($q !== '') {
    $stmt2 = pdo()->prepare('SELECT t.*, b.title, b.author, br.name, br.borrower_id FROM transactions t JOIN books b ON t.book_id=b.id JOIN borrowers br ON t.borrower_id=br.id WHERE b.title LIKE ? OR b.author LIKE ? OR br.name LIKE ? ORDER BY t.created_at DESC');
    $like2 = "%$q%";
    $stmt2->execute([$like2, $like2, $like2]);
    $searchResults = $stmt2->fetchAll();
}

// Print view for borrowed books
if (($_GET['print'] ?? '') === 'borrowed') {
    ?>
    <!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Print — Borrowed Books</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?=defined('ROOT_BASE')?ROOT_BASE:(defined('ASSET_BASE')?ASSET_BASE:APP_BASE)?>/image/ccclogo.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?=defined('ASSET_BASE')?ASSET_BASE:APP_BASE?>/assets/style.css">
        <style>
          @media print { .no-print { display:none !important; } }
        </style>
      </head>
      <body data-auto-print="true">
        <div class="container mt-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <img src="<?=defined('ROOT_BASE')?ROOT_BASE:(defined('ASSET_BASE')?ASSET_BASE:APP_BASE)?>/image/ccclogo.png" alt="Clarendon College logo" width="40" height="40">
              <div class="page-title mb-0">Clarendon College Library Tracker</div>
            </div>
            <button class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()">Print</button>
          </div>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Borrower</th>
                  <th>Book</th>
                  <th>Status</th>
                  <th>Borrow Date</th>
                  <th>Due Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($borrowed as $r): ?>
                  <tr>
                    <td><?=htmlspecialchars($r['name'])?> (<?=htmlspecialchars($r['borrower_id'])?>)</td>
                    <td><?=htmlspecialchars($r['title'])?> — <?=htmlspecialchars($r['author'])?></td>
                    <td><?=htmlspecialchars($r['status'])?></td>
                    <td><?=htmlspecialchars($r['borrow_date'])?></td>
                    <td><?=htmlspecialchars($r['due_date'])?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?=defined('ASSET_BASE')?ASSET_BASE:APP_BASE?>/assets/app.js"></script>
      </body>
    </html>
    <?php
    exit;
}

// Print view for returned books
if (($_GET['print'] ?? '') === 'returned') {
    ?>
    <!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Print — Returned Books</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?=defined('ROOT_BASE')?ROOT_BASE:(defined('ASSET_BASE')?ASSET_BASE:APP_BASE)?>/image/ccclogo.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?=defined('ASSET_BASE')?ASSET_BASE:APP_BASE?>/assets/style.css">
        <style>
          @media print { .no-print { display:none !important; } }
        </style>
      </head>
      <body data-auto-print="true">
        <div class="container mt-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <img src="<?=defined('ROOT_BASE')?ROOT_BASE:(defined('ASSET_BASE')?ASSET_BASE:APP_BASE)?>/image/ccclogo.png" alt="Clarendon College logo" width="40" height="40">
              <div class="page-title mb-0">Clarendon College Library Tracker</div>
            </div>
            <button class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()">Print</button>
          </div>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Borrower</th>
                  <th>Book</th>
                  <th>Status</th>
                  <th>Borrowed</th>
                  <th>Returned</th>
                  <th>Late Fee</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($returned as $r): ?>
                  <tr>
                    <td><?=htmlspecialchars($r['name'])?></td>
                    <td><?=htmlspecialchars($r['title'])?></td>
                    <td><?=htmlspecialchars($r['status'])?></td>
                    <td><?=htmlspecialchars($r['borrow_date'])?></td>
                    <td><?=htmlspecialchars($r['return_date'])?></td>
                    <td>₱<?=number_format((float)$r['late_fee'],2)?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?=defined('ASSET_BASE')?ASSET_BASE:APP_BASE?>/assets/app.js"></script>
      </body>
    </html>
    <?php
    exit;
}

include __DIR__ . '/../partials/admin_header.php';
?>
<div class="row mb-3">
  <div class="col-md-8">
    <form method="get" class="d-flex gap-2">
      <input type="text" class="form-control" name="q" placeholder="Search title, author, borrower name or ID" value="<?=htmlspecialchars($q)?>">
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="page-title mb-0">Borrowed Books</div>
          <a class="btn btn-sm btn-outline-secondary" href="<?=APP_BASE?>/reports.php?print=borrowed" target="_blank">Print</a>
        </div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Borrower</th>
                <th>Book</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($borrowed as $r): ?>
                <tr>
                  <td><?=htmlspecialchars($r['name'])?> (<?=htmlspecialchars($r['borrower_id'])?>)</td>
                  <td><?=htmlspecialchars($r['title'])?> — <?=htmlspecialchars($r['author'])?></td>
                  <td><?=htmlspecialchars($r['borrow_date'])?></td>
                  <td><?=htmlspecialchars($r['due_date'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="page-title mb-0">Returned Books</div>
          <a class="btn btn-sm btn-outline-secondary" href="<?=APP_BASE?>/reports.php?print=returned" target="_blank">Print</a>
        </div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Borrower</th>
                <th>Book</th>
                <th>Borrowed</th>
                <th>Returned</th>
                <th>Late Fee</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($returned as $r): ?>
                <tr>
                  <td><?=htmlspecialchars($r['name'])?></td>
                  <td><?=htmlspecialchars($r['title'])?></td>
                  <td><?=htmlspecialchars($r['borrow_date'])?></td>
                  <td><?=htmlspecialchars($r['return_date'])?></td>
                  <td>₱<?=number_format((float)$r['late_fee'],2)?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mt-1">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Most Borrowed Books</div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Times Borrowed</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mostBorrowed as $r): ?>
                <tr>
                  <td><?=htmlspecialchars($r['title'])?></td>
                  <td><?=htmlspecialchars($r['author'])?></td>
                  <td><?=$r['times_borrowed']?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Borrower History <?= $q? '(for "'.htmlspecialchars($q).'")':'' ?></div>
        <?php if ($q === ''): ?>
          <div class="alert alert-info">Enter a borrower name or ID above to view their history.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Borrower</th>
                  <th>Book</th>
                  <th>Status</th>
                  <th>Borrowed</th>
                  <th>Returned</th>
                  <th>Late Fee</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($borrowerHistory as $r): ?>
                  <tr>
                    <td><?=htmlspecialchars($r['name'])?></td>
                    <td><?=htmlspecialchars($r['title'])?></td>
                    <td><?=$r['status']?></td>
                    <td><?=htmlspecialchars($r['borrow_date'])?></td>
                    <td><?=htmlspecialchars($r['return_date'])?></td>
                    <td>₱<?=number_format((float)$r['late_fee'],2)?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($q !== ''): ?>
<div class="row mt-4">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Search Results</div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Borrower</th>
                <th>Book</th>
                <th>Status</th>
                <th>Borrowed</th>
                <th>Returned</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($searchResults as $r): ?>
                <tr>
                  <td><?=htmlspecialchars($r['name'])?> (<?=htmlspecialchars($r['borrower_id'])?>)</td>
                  <td><?=htmlspecialchars($r['title'])?> — <?=htmlspecialchars($r['author'])?></td>
                  <td><?=$r['status']?></td>
                  <td><?=htmlspecialchars($r['borrow_date'])?></td>
                  <td><?=htmlspecialchars($r['return_date'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../partials/footer.php'; ?>