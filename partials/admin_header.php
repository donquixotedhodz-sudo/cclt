<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$loggedIn = isset($_SESSION['user']);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clarendon College Library Tracker</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?=defined('ROOT_BASE')?ROOT_BASE:(defined('ASSET_BASE')?ASSET_BASE:APP_BASE)?>/image/ccclogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?=defined('ASSET_BASE')?ASSET_BASE:APP_BASE?>/assets/style.css">
  </head>
  <body>
    <aside class="sidenav">
      <div class="brand d-flex align-items-center gap-2 mb-3">
        <img src="<?=defined('ROOT_BASE')?ROOT_BASE:(defined('ASSET_BASE')?ASSET_BASE:APP_BASE)?>/image/ccclogo.png" alt="Clarendon College logo" width="36" height="36">
        <div class="fw-semibold">Clarendon College</div>
      </div>
      <ul class="nav flex-column">
        <?php if ($loggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/books.php"><i class="bi bi-book me-2"></i>Books</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/borrowers.php"><i class="bi bi-people me-2"></i>Borrowers</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/transactions.php"><i class="bi bi-arrow-left-right me-2"></i>Borrow/Return</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/reports.php"><i class="bi bi-graph-up me-2"></i>Reports</a></li>
          <li class="nav-item mt-auto"><a class="nav-link" href="<?=APP_BASE?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?=defined('ROOT_BASE')?ROOT_BASE:APP_BASE?>/index.php"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a></li>
        <?php endif; ?>
      </ul>
    </aside>
    <div class="container content-with-sidenav">