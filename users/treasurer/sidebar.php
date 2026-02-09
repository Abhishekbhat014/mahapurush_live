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
            <?php echo $t['treasurer_portal']; ?>
        </small>
    </div>

    <div class="nav flex-column">
        <a href="../../index.php" class="sb-link">
            <i class="bi bi-house"></i> <?php echo $t['home']; ?>
        </a>
        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <span><?php echo $t['dashboard']; ?></span>
        </a>

        <a href="donations.php" class="sb-link <?= ($currentPage == 'donations.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span><?php echo $t['donations']; ?></span>
        </a>

        <a href="receipts.php" class="sb-link <?= ($currentPage == 'receipts.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span><?php echo $t['receipts']; ?></span>
        </a>

        <a href="donation_records.php"
            class="sb-link <?= ($currentPage == 'donation_records.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-journal-text"></i> <span><?php echo $t['donation_records']; ?></span>
        </a>

        <a href="reports.php" class="sb-link <?= ($currentPage == 'reports.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> <span><?php echo $t['reports']; ?></span>
        </a>


        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span><?php echo $t['my_profile']; ?></span>
        </a>

        <div class="sb-divider"></div>

        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i> <span><?php echo $t['logout']; ?></span>
        </a>
    </div>
</nav>


<div class="offcanvas offcanvas-start sb-offcanvas" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><?php echo $t['treasurer_menu']; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 pt-3">
        <a href="../../index.php" class="sb-link"><i class="bi bi-house"></i>
            <?php echo $t['home']; ?>
        </a>
        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <?php echo $t['dashboard']; ?>
        </a>

        <div class="sb-divider"></div>
        <small class="text-uppercase text-muted fw-bold px-3"
            style="font-size:11px;opacity:.6;"><?php echo $t['finance']; ?></small>

        <a href="donations.php" class="sb-link <?= ($currentPage == 'donations.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <?php echo $t['donations']; ?>
        </a>

        <a href="receipts.php" class="sb-link <?= ($currentPage == 'receipts.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <?php echo $t['receipts']; ?>
        </a>

        <a href="donation_records.php"
            class="sb-link <?= ($currentPage == 'donation_records.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-journal-text"></i> <?php echo $t['donation_records']; ?>
        </a>

        <a href="reports.php" class="sb-link <?= ($currentPage == 'reports.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> <?php echo $t['reports']; ?>
        </a>

        <div class="sb-divider"></div>

        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i> <?php echo $t['my_profile']; ?>
        </a>

        <div class="sb-divider"></div>

        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i> <?php echo $t['logout']; ?>
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