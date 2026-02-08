<?php
require_once __DIR__ . '/../../includes/no_cache.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="../../css/sidebar.css">

<style>
    :root {
        --sb-top-offset: 64px;
    }
</style>

<nav class="col-lg-2 d-none d-lg-block sb-sidebar shadow-sm p-0">

    <div class="px-4 py-4">
        <small class="text-uppercase text-muted fw-bold sb-title">
            Treasurer Portal
        </small>
    </div>

    <div class="nav flex-column">
        <a href="../../index.php" class="sb-link">
            <i class="bi bi-house"></i> <?php echo $t['home'] ?? 'Home'; ?>
        </a>
        <!-- Dashboard -->
        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
        </a>

        <!-- Donations -->
        <a href="donations.php" class="sb-link <?= ($currentPage == 'donations.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span>Donations</span>
        </a>

        <!-- Receipts -->
        <a href="receipts.php" class="sb-link <?= ($currentPage == 'receipts.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span>Receipts</span>
        </a>

        <!-- Donation Records -->
        <a href="donation_records.php"
            class="sb-link <?= ($currentPage == 'donation_records.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-journal-text"></i> <span>Donation Records</span>
        </a>

        <!-- Reports -->
        <a href="reports.php" class="sb-link <?= ($currentPage == 'reports.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> <span>Reports</span>
        </a>


        <!-- Profile -->
        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span>My Profile</span>
        </a>

        <div class="sb-divider"></div>

        <!-- Logout -->
        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i> <span><?php echo $t['logout'] ?? 'Logout'; ?></span>
        </a>
    </div>
</nav>


<!-- Mobile -->
<div class="offcanvas offcanvas-start sb-offcanvas" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Treasurer Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 pt-3">
<a href="../../index.php" class="sb-link"><i class="bi bi-house"></i>
            <?php echo $t['home']; ?>
        </a>
        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        <div class="sb-divider"></div>
        <small class="text-uppercase text-muted fw-bold px-3" style="font-size:11px;opacity:.6;">Finance</small>

        <a href="donations.php" class="sb-link <?= ($currentPage == 'donations.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-heart-fill"></i> Donations
        </a>

        <a href="receipts.php" class="sb-link <?= ($currentPage == 'receipts.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> Receipts
        </a>

        <a href="donation_records.php"
            class="sb-link <?= ($currentPage == 'donation_records.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-journal-text"></i> Donation Records
        </a>

        <a href="reports.php" class="sb-link <?= ($currentPage == 'reports.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>

        <div class="sb-divider"></div>

        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i> My Profile
        </a>

        <div class="sb-divider"></div>

        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i> <?php echo $t['logout'] ?? 'Logout'; ?>
        </a>
    </div>
</div>

<script>
    document.addEventListener('contextmenu', function (e) {
        if (e.target.closest('.sb-sidebar') || e.target.closest('.sb-offcanvas')) {
            e.preventDefault();
        }
    });
</script>