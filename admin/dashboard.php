<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$stats = [
  'books' => (int)pdo()->query('SELECT COUNT(*) AS c FROM books')->fetch()['c'],
  'borrowers' => (int)pdo()->query('SELECT COUNT(*) AS c FROM borrowers')->fetch()['c'],
  'borrowed' => (int)pdo()->query("SELECT COUNT(*) AS c FROM transactions WHERE status='Borrowed'")->fetch()['c'],
];

// Chart data: borrows per month (last 6 months) and status distribution
$borrowsMonthly = pdo()->query("SELECT DATE_FORMAT(borrow_date,'%Y-%m') AS m, COUNT(*) AS c FROM transactions GROUP BY m ORDER BY m DESC LIMIT 6")->fetchAll();
$borrowsMonthly = array_reverse($borrowsMonthly);
$borrowsLabels = array_map(fn($r) => $r['m'], $borrowsMonthly);
$borrowsCounts = array_map(fn($r) => (int)$r['c'], $borrowsMonthly);

$statusRows = pdo()->query("SELECT status, COUNT(*) AS c FROM transactions GROUP BY status")->fetchAll();
$statusLabels = array_map(fn($r) => $r['status'], $statusRows);
$statusCounts = array_map(fn($r) => (int)$r['c'], $statusRows);

include __DIR__ . '/../partials/admin_header.php';
?>
<div class="row g-4">
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="page-title">Books</div>
        <div class="display-6"><?=$stats['books']?></div>
        <a href="<?=APP_BASE?>/books.php" class="btn btn-sm btn-primary mt-2">Manage Books</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="page-title">Borrowers</div>
        <div class="display-6"><?=$stats['borrowers']?></div>
        <a href="<?=APP_BASE?>/borrowers.php" class="btn btn-sm btn-primary mt-2">Manage Borrowers</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="page-title">Currently Borrowed</div>
        <div class="display-6"><?=$stats['borrowed']?></div>
        <a href="<?=APP_BASE?>/transactions.php" class="btn btn-sm btn-primary mt-2">Borrow/Return</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="page-title">Reports</div>
        <div class="display-6">📊</div>
        <a href="<?=APP_BASE?>/reports.php" class="btn btn-sm btn-primary mt-2">View Reports</a>
      </div>
    </div>
  </div>
</div>

<!-- <div class="row mt-4">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Quick Actions</div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="<?=APP_BASE?>/books.php?action=new" class="btn btn-outline-primary">Add Book</a>
          <a href="<?=APP_BASE?>/borrowers.php?action=new" class="btn btn-outline-primary">Register Borrower</a>
          <a href="<?=APP_BASE?>/transactions.php?action=borrow" class="btn btn-outline-primary">Borrow a Book</a>
          <a href="<?=APP_BASE?>/transactions.php" class="btn btn-outline-primary">Return a Book</a>
        </div>
      </div>
    </div>
  </div>
</div> -->

<div class="row mt-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Borrowed Books</div>
        <div class="chart-container"><canvas id="borrowsChart"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="page-title mb-2">Transaction Status</div>
        <div class="chart-container"><canvas id="statusChart"></canvas></div>
        
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const borrowsLabels = <?=json_encode($borrowsLabels)?>;
  const borrowsCounts = <?=json_encode($borrowsCounts)?>;
  const statusLabels = <?=json_encode($statusLabels)?>;
  const statusCounts = <?=json_encode($statusCounts)?>;

  const brandNavy = getComputedStyle(document.documentElement).getPropertyValue('--navy') || '#0e2a47';
  const brandGold = getComputedStyle(document.documentElement).getPropertyValue('--gold') || '#c7a64b';

  // Bar chart: borrows per month
  new Chart(document.getElementById('borrowsChart'), {
    type: 'bar',
    data: {
      labels: borrowsLabels,
      datasets: [{
        label: 'Books',
        data: borrowsCounts,
        backgroundColor: brandNavy.trim(),
      }]
    },
    options: {
      responsive: true,
      resizeDelay: 200,
      maintainAspectRatio: false,
      animation: { duration: 0 },
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });

  // Doughnut chart: status distribution
  const basePalette = [brandGold.trim(), brandNavy.trim(), '#6c757d', '#198754', '#dc3545', '#0d6efd', '#fd7e14', '#20c997'];
  const statusColors = statusLabels.map((_, i) => basePalette[i % basePalette.length]);
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: statusLabels,
      datasets: [{
        data: statusCounts,
        backgroundColor: statusColors,
      }]
    },
    options: {
      responsive: true,
      resizeDelay: 200,
      maintainAspectRatio: false,
      animation: { duration: 0 },
      plugins: { legend: { display: false } }
    }
  });
  const statusLegendEl = document.getElementById('statusLegend');
  if (statusLegendEl) {
    statusLegendEl.innerHTML = statusLabels.map((label, i) => {
      const count = statusCounts[i] !== undefined ? statusCounts[i] : 0;
      return `<span class="legend-item"><span class="legend-dot" style="background:${statusColors[i]}"></span>${label} (${count})</span>`;
    }).join('');
  }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>