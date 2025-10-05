<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = null;

function redirect_list() {
    header('Location: ' . APP_BASE . '/books.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form'] === 'book_new') {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category = trim($_POST['category']);
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));
        pdo()->prepare('INSERT INTO books (title, author, isbn, category, quantity) VALUES (?,?,?,?,?)')
            ->execute([$title, $author, $isbn, $category, $quantity]);
        redirect_list();
    }
    if ($_POST['form'] === 'book_edit') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category = trim($_POST['category']);
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));
        pdo()->prepare('UPDATE books SET title=?, author=?, isbn=?, category=?, quantity=? WHERE id=?')
            ->execute([$title, $author, $isbn, $category, $quantity, $id]);
        redirect_list();
    }
}

if ($action === 'delete' && $id) {
    pdo()->prepare('DELETE FROM books WHERE id=?')->execute([$id]);
    redirect_list();
}

include __DIR__ . '/../partials/admin_header.php';

if ($action === 'new' || $action === 'edit') {
    $book = ['id'=>0,'title'=>'','author'=>'','isbn'=>'','category'=>'','quantity'=>0];
    if ($action === 'edit' && $id) {
        $stmt = pdo()->prepare('SELECT * FROM books WHERE id=?');
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        if (!$book) { echo '<div class="alert alert-danger">Book not found</div>'; include __DIR__ . '/../partials/footer.php'; exit; }
    }
    ?>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card">
          <div class="card-body">
            <div class="page-title mb-2"><?=( $action==='new' ? 'Add New Book' : 'Edit Book' )?></div>
            <form method="post">
              <input type="hidden" name="form" value="<?= $action==='new' ? 'book_new' : 'book_edit' ?>">
              <input type="hidden" name="id" value="<?=$book['id']?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label required">Title</label>
                  <input type="text" name="title" class="form-control" value="<?=htmlspecialchars($book['title'])?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label required">Author</label>
                  <input type="text" name="author" class="form-control" value="<?=htmlspecialchars($book['author'])?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">ISBN</label>
                  <input type="text" name="isbn" class="form-control" value="<?=htmlspecialchars($book['isbn'])?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Category</label>
                  <input type="text" name="category" class="form-control" value="<?=htmlspecialchars($book['category'])?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label required">Quantity</label>
                  <input type="number" min="0" name="quantity" class="form-control" value="<?=htmlspecialchars($book['quantity'])?>" required>
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn btn-outline-secondary" href="<?=APP_BASE?>/books.php">Cancel</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/partials/footer.php';
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
$total = (int)pdo()->query('SELECT COUNT(*) AS c FROM books')->fetch()['c'];
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$rows = pdo()->query("SELECT * FROM books ORDER BY created_at DESC, title ASC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="page-title">Books</div>
  <div>
    <a href="<?=APP_BASE?>/books.php?action=new" class="btn btn-primary">Add Book</a>
  </div>
</div>
<div class="row mb-3">
  <div class="col-md-6">
    <input type="text" class="form-control" placeholder="Search in table..." data-table-filter="#booksTable">
  </div>
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped" id="booksTable">
        <thead>
          <tr>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Category</th>
            <th>Qty</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?=htmlspecialchars($r['title'])?></td>
              <td><?=htmlspecialchars($r['author'])?></td>
              <td><?=htmlspecialchars($r['isbn'])?></td>
              <td><?=htmlspecialchars($r['category'])?></td>
              <td><?=$r['quantity']?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="<?=APP_BASE?>/books.php?action=edit&id=<?=$r['id']?>">Edit</a>
                <a class="btn btn-sm btn-outline-danger" href="<?=APP_BASE?>/books.php?action=delete&id=<?=$r['id']?>" data-confirm="Delete this book?">Delete</a>
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
  <nav aria-label="Books pagination">
    <ul class="pagination pagination-sm mb-0">
      <?php $prevPage = max(1, $page-1); $nextPage = min($totalPages, $page+1); ?>
      <li class="page-item <?=$page<=1?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/books.php?page=<?=$prevPage?>">Prev</a></li>
      <li class="page-item disabled"><span class="page-link">Page <?=$page?> of <?=$totalPages?></span></li>
      <li class="page-item <?=$page>=$totalPages?'disabled':''?>"><a class="page-link" href="<?=APP_BASE?>/books.php?page=<?=$nextPage?>">Next</a></li>
    </ul>
  </nav>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>