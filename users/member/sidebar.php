<?php
require_once __DIR__ . '/../../includes/no_cache.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
 <link rel="stylesheet" href="../../css/sidebar.css">

<style>
    /* Prevent full sidebar scroll to keep logout sticky */
    .sb-sidebar {
        height: calc(100vh - 64px) !important;
        position: sticky !important;
        top: 64px !important;
        overflow-y: hidden !important;
    }
    .sb-offcanvas {
        overflow-y: hidden !important;
    }
</style>

<nav class="col-lg-2 d-none d-lg-flex flex-column sb-sidebar shadow-sm p-0">
    <div class="px-4 py-4">
        <small class="text-uppercase text-muted fw-bold sb-title" style="margin-top: 0;">
            <?php echo $t['member_portal'] ?? 'Member Portal'; ?>
        </small>
    </div>

    <div class="nav flex-column flex-nowrap flex-grow-1" style="overflow-y: auto; overflow-x: hidden; min-height: 0;">
        <a href="../../index.php" class="sb-link">
            <i class="bi bi-house"></i> <span>
                <?php echo $t['home'] ?? 'Home'; ?>
            </span>
        </a>
        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-grid-1x2"></i>
            <span><?php echo $t['dashboard'] ?? 'Dashboard'; ?></span>
        </a>

        <a href="pooja_requests.php" class="sb-link <?= ($currentPage == 'pooja_requests.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar-check"></i>
            <span><?php echo $t['pooja_requests'] ?? 'Pooja Requests (View Only)'; ?></span>
        </a>

        <a href="my_donations.php" class="sb-link <?= ($currentPage == 'my_donations.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-gift"></i>
            <span><?php echo $t['all_donations_contributions'] ?? 'All Donations & Contributions'; ?></span>
        </a>

        <a href="events.php" class="sb-link <?= ($currentPage == 'events.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar-event"></i>
            <span><?php echo $t['events'] ?? 'Events'; ?></span>
        </a>

        <a href="../../gallery.php" class="sb-link">
            <i class="bi bi-images"></i>
            <span><?php echo $t['gallery'] ?? 'Gallery'; ?></span>
        </a>

        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span><?php echo $t['my_profile'] ?? 'My Profile'; ?></span>
        </a>

    </div>

    <div class="mt-auto p-2 border-top">
        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i>
            <span><?php echo $t['logout'] ?? 'Logout'; ?></span>
        </a>
    </div>
</nav>


<!-- Mobile Offcanvas -->
<div class="offcanvas offcanvas-start sb-offcanvas" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><?php echo $t['menu'] ?? 'Menu'; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0 pt-3 h-100">
        <div class="nav flex-column flex-nowrap flex-grow-1" style="overflow-y: auto; overflow-x: hidden; min-height: 0;">
            <a href="../../index.php" class="sb-link"><i class="bi bi-house"></i>
                <?php echo $t['home']; ?>
            </a>
            <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-grid-1x2"></i> <?php echo $t['dashboard'] ?? 'Dashboard'; ?>
            </a>

        <a href="pooja_requests.php" class="sb-link <?= ($currentPage == 'pooja_requests.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <?php echo $t['pooja_requests'] ?? 'Pooja Requests (View Only)'; ?>
        </a>

        <a href="my_donations.php" class="sb-link <?= ($currentPage == 'my_donations.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-gift"></i> <?php echo $t['all_donations_contributions'] ?? 'All Donations & Contributions'; ?>
        </a>

        <a href="events.php" class="sb-link <?= ($currentPage == 'events.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar-event"></i> <?php echo $t['events'] ?? 'Events'; ?>
        </a>

        <a href="../../gallery.php" class="sb-link">
            <i class="bi bi-images"></i> <?php echo $t['gallery'] ?? 'Gallery'; ?>
        </a>

        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i> <?php echo $t['my_profile'] ?? 'My Profile'; ?>
        </a>

        </div>

        <div class="mt-auto border-top p-2 bg-light">
            <a href="../../auth/logout.php" class="sb-link text-danger">
                <i class="bi bi-power"></i> <?php echo $t['logout'] ?? 'Logout'; ?>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });
</script>