<?php
// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="col-lg-2 d-none d-lg-block ant-sidebar shadow-sm">
    <div class="px-4 mb-4">
        <small class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['devotee_portal']; ?>
        </small>
    </div>

    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <span><?php echo $t['dashboard']; ?></span>
        </a>

        <a href="donate.php" class=" nav-link-custom <?= ($currentPage == 'donate.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span><?php echo $t['make_donation']; ?></span>
        </a>

        <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span><?php echo $t['pooja_bookings']; ?></span>
        </a>

        <a href="contribute.php" class="nav-link-custom <?= ($currentPage == 'contribute.php') ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> <span><?php echo $t['contribution']; ?></span>
        </a>

        <a href="my_requests.php" class="nav-link-custom <?= ($currentPage == 'my_requests.php') ? 'active' : '' ?>">
            <i class="bi bi-hourglass-split"></i> <span><?php echo $t['my_requests']; ?></span>
        </a>

        <a href="my_receipts.php" class="nav-link-custom <?= ($currentPage == 'my_receipts.php') ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span><?php echo $t['receipts']; ?></span>
        </a>

        <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span><?php echo $t['my_profile']; ?></span>
        </a>

        <div class="ant-divider" style="margin: 20px 0; opacity: 0.5;"></div>

        <a href="../../index.php" class="nav-link-custom">
            <i class="bi bi-house"></i> <span><?php echo $t['back_to_home']; ?></span>
        </a>

        <a href="../../auth/logout.php" class="nav-link-custom text-danger">
            <i class="bi bi-power"></i> <span><?php echo $t['logout']; ?></span>
        </a>
    </div>
</nav>

<div class="offcanvas offcanvas-start" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><?php echo $t['menu']; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 pt-3">
        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <?php echo $t['dashboard']; ?>
        </a>
        <a href="donate.php" class="nav-link-custom"><i class="bi bi-heart-fill"></i> <?php echo $t['make_donation']; ?></a>
        <a href="contribute.php" class="nav-link-custom"><i class="bi bi-box-seam"></i> <?php echo $t['contribution']; ?></a>
        <a href="my_requests.php" class="nav-link-custom"><i class="bi bi-hourglass-split"></i> <?php echo $t['my_requests']; ?></a>
        <a href="my_receipts.php" class="nav-link-custom"><i class="bi bi-receipt-cutoff"></i> <?php echo $t['receipts']; ?></a>
        <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <?php echo $t['my_profile']; ?></a>
    </div>
</div>
