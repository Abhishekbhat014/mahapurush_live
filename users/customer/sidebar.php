<?php
// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    body,
    body * {
        -webkit-user-select: none;
        user-select: none;
    }
</style>

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

        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-4" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['services'] ?? 'Services'; ?>
        </small>

        <a href="donate.php" class=" nav-link-custom <?= ($currentPage == 'donate.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span><?php echo $t['make_donation']; ?></span>
        </a>

        <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span><?php echo $t['pooja_bookings']; ?></span>
        </a>

        <a href="contribute.php" class="nav-link-custom <?= ($currentPage == 'contribute.php') ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> <span><?php echo $t['contribution']; ?></span>
        </a>

        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-4" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['management'] ?? 'Management'; ?>
        </small>

        <a href="my_requests.php" class="nav-link-custom <?= ($currentPage == 'my_requests.php') ? 'active' : '' ?>">
            <i class="bi bi-hourglass-split"></i> <span><?php echo $t['my_requests']; ?></span>
        </a>

        <a href="my_receipts.php" class="nav-link-custom <?= ($currentPage == 'my_receipts.php') ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span><?php echo $t['receipts']; ?></span>
        </a>

        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-4" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['account'] ?? 'Account'; ?>
        </small>

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
        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-3" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['services'] ?? 'Services'; ?>
        </small>
        <a href="donate.php" class="nav-link-custom"><i class="bi bi-heart-fill"></i> <?php echo $t['make_donation']; ?></a>
        <a href="pooja_book.php" class="nav-link-custom"><i class="bi bi-person-circle"></i> <?php echo $t['pooja_bookings']; ?></a>
        <a href="contribute.php" class="nav-link-custom"><i class="bi bi-box-seam"></i> <?php echo $t['contribution']; ?></a>
        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-3" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['management'] ?? 'Management'; ?>
        </small>
        <a href="my_requests.php" class="nav-link-custom"><i class="bi bi-hourglass-split"></i> <?php echo $t['my_requests']; ?></a>
        <a href="my_receipts.php" class="nav-link-custom"><i class="bi bi-receipt-cutoff"></i> <?php echo $t['receipts']; ?></a>
        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-3" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['account'] ?? 'Account'; ?>
        </small>
        <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <?php echo $t['my_profile']; ?></a>
    </div>
</div>
<script>
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });
</script>
