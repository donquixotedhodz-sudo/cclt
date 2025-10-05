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
    <link rel="stylesheet" href="<?=defined('ASSET_BASE')?ASSET_BASE:APP_BASE?>/assets/style.css">
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?=APP_BASE?>/dashboard.php">
          <img src="<?=defined('ROOT_BASE')?ROOT_BASE:(defined('ASSET_BASE')?ASSET_BASE:APP_BASE)?>/image/ccclogo.png" alt="Clarendon College logo" width="32" height="32">
          <span>Clarendon College Library Tracker</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample" aria-controls="navbarsExample" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarsExample">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <?php if ($loggedIn): ?>
            <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/books.php">Books</a></li>
            <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/borrowers.php">Borrowers</a></li>
            <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/transactions.php">Borrow/Return</a></li>
            <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/reports.php">Reports</a></li>
            <?php endif; ?>
          </ul>
          <ul class="navbar-nav ms-auto">
            <?php if ($loggedIn): ?>
              <li class="nav-item"><span class="nav-link">Hello, <?=$_SESSION['user']['username']?></span></li>
              <li class="nav-item"><a class="nav-link" href="<?=APP_BASE?>/logout.php">Logout</a></li>
            <?php else: ?>
            
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container">