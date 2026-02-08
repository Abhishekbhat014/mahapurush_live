<?php
require_once __DIR__ . '/../../includes/no_cache.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="../../css/sidebar.css">

<nav class="col-lg-2 d-none d-lg-block sb-sidebar shadow-sm p-0">
    <div class="px-4 py-4">
        <small class="text-uppercase text-muted fw-bold sb-title" style="margin-top: 0;">
            <?php echo $t['member_portal'] ?? 'Member Portal'; ?>
        </small>
    </div>

    <div class="nav flex-column">
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

        <div class="sb-divider"></div>

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

    <div class="offcanvas-body p-0 pt-3">
        <a href="../../index.php" class="sb-link"><i class="bi bi-house"></i>
            <?php echo $t['home']; ?>
        </a>
        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <?php echo $t['dashboard'] ?? 'Dashboard'; ?>
        </a>

        <a href="pooja_requests.php" class="sb-link <?= ($currentPage == 'pooja_requests.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <?php echo $t['pooja_requests'] ?? 'Pooja Requests (View Only)'; ?>
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

        <div class="sb-divider"></div>

        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i> <?php echo $t['logout'] ?? 'Logout'; ?>
        </a>

    </div>
</div>

<script>
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });
</script>