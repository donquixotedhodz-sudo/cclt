<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function redirect_list_bor() {
    header('Location: ' . APP_BASE . '/borrowers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form'] === 'borrower_new') {
        $name = trim($_POST['name']);
        $borrower_id = trim($_POST['borrower_id']);
        $contact = trim($_POST['contact']);
        pdo()->prepare('INSERT INTO borrowers (name, borrower_id, contact) VALUES (?,?,?)')
            ->execute([$name, $borrower_id, $contact]);
        redirect_list_bor();
    }
    if ($_POST['form'] === 'borrower_edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $borrower_id = trim($_POST['borrower_id']);
        $contact = trim($_POST['contact']);
        pdo()->prepare('UPDATE borrowers SET name=?, borrower_id=?, contact=? WHERE id=?')
            ->execute([$name, $borrower_id, $contact, $id]);
        redirect_list_bor();
    }
}

if ($action === 'delete' && $id) {
    pdo()->prepare('DELETE FROM borrowers WHERE id=?')->execute([$id]);
    redirect_list_bor();
}

include __DIR__ . '/../partials/admin_header.php';

if ($action === 'new' || $action === 'edit') {
    $b = ['id'=>0,'name'=>'','borrower_id'=>'','contact'=>''];
    if ($action === 'edit' && $id) {
        $stmt = pdo()->prepare('SELECT * FROM borrowers WHERE id=?');
        $stmt->execute([$id]);
        $b = $stmt->fetch();
        if (!$b) { echo '<div class="alert alert-danger">Borrower not found</div>'; include __DIR__ . '/../partials/footer.php'; exit; }
    }
    ?>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card">
          <div class="card-body">
            <div class="page-title mb-2"><?=( $action==='new' ? 'Register Borrower' : 'Edit Borrower' )?></div>
            <form method="post">
              <input type="hidden" name="form" value="<?= $action==='new' ? 'borrower_new' : 'borrower_edit' ?>">
              <input type="hidden" name="id" value="<?=$b['id']?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label required">Name</label>
                  <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($b['name'])?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label required">Borrower ID</label>
                  <input type="text" name="borrower_id" class="form-control" value="<?=htmlspecialchars($b['borrower_id'])?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Contact</label>
                  <input type="text" name="contact" class="form-control" value="<?=htmlspecialchars($b['contact'])?>">
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn btn-outline-secondary" href="<?=APP_BASE?>/borrowers.php">Cancel</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/../partials/footer.php';
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
$total = (int)pdo()->query('SELECT COUNT(*) AS c FROM borrowers')->fetch()['c'];
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$rows = pdo()->query("SELECT * FROM borrowers ORDER BY created_at DESC, name ASC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="page-title">Borrowers</div>
  <div>
    <a href="<?=APP_BASE?>/borrowers.php?action=new" class="btn btn-primary">Register Borrower</a>
  </div>
</div>
<div class="row mb-3">
  <div class="col-md-6">
    <input type="text" class="form-control" placeholder="Search in table..." data-table-filter="#borrowersTable">
  </div>
  <!-- <div class="col-md-6 text-end">
    <a class="btn btn-outline-secondary" href="<?=APP_BASE?>/transactions.php?action=borrow">Borrow a Book</a>
  </div> -->
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped" id="borrowersTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Borrower ID</th>
            <th>Contact</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?=htmlspecialchars($r['name'])?></td>
              <td><?=htmlspecialchars($r['borrower_id'])?></td>
              <td><?=htmlspecialchars($r['contact'])?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="<?=APP_BASE?>/borrowers.php?action=edit&id=<?=$r['id']?>">Edit</a>
                <a class="btn btn-sm btn-outline-danger" href="<?=APP_BASE?>/borrowers.php?action=delete&id=<?=$r['id']?>" data-confirm="Delete this borrower?">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
  <div class="small text-muted">Showing <?=min($offset+1, $total)?>–<?=min($offset+$perPage, $total)?> of <?=$total?></div>
  <nav aria-label="Borrowers pagination">
    <ul class="pagination pagination-sm mb-0">
      <?php $prevPage = max(1, $page-1); $nextPage = min($totalPages, $page+1); ?>
      <li class="page-item <?=$page<=1?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/borrowers.php?page=<?=$prevPage?>">Prev</a></li>
      <li class="page-item disabled"><span class="page-link">Page <?=$page?> of <?=$totalPages?></span></li>
      <li class="page-item <?=$page>=$totalPages?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/borrowers.php?page=<?=$nextPage?>">Next</a></li>
    </ul>
  </nav>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>