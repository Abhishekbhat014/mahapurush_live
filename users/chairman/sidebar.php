<?php
require_once __DIR__ . '/../../includes/no_cache.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="../../css/sidebar.css">

<style>
    :root {
        --sb-top-offset: 64px;
        /* Start below header */
        --sb-active-text: #1677ff;
        /* Standard Blue for active state */
        --sb-active-bg: #e6f4ff;
        /* Light Blue Background */
    }

    /* Prevent full sidebar scroll to keep logout sticky */
    .sb-sidebar, .sb-offcanvas {
        overflow-y: hidden !important;
    }
</style>

<nav class="col-lg-2 d-none d-lg-flex flex-column sb-sidebar shadow-sm p-0">

    <div class="px-4 py-4">
        <small class="sb-title text-uppercase text-muted fw-bold">
            <?php echo $t['chairman_portal'] ?? 'Chairman Portal'; ?>
        </small>
    </div>

    <div class="nav flex-column flex-grow-1" style="overflow-y: auto; flex-wrap: nowrap; min-height: 0; padding-bottom: 20px;">

        <a href="../../index.php" class="sb-link">
            <i class="bi bi-house"></i> <span><?php echo $t['home'] ?? 'Home'; ?></span>
        </a>
        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span><?php echo $t['dashboard'] ?? 'Dashboard'; ?></span>
        </a>

        <a href="event_approvals.php" class="sb-link <?= ($currentPage == 'event_approvals.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar2-check"></i> <span><?php echo $t['event_approvals'] ?? 'Event Approvals'; ?></span>
        </a>

        <a href="pooja_requests.php" class="sb-link <?= ($currentPage == 'pooja_requests.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-journal-check"></i> <span><?php echo $t['pooja_approvals'] ?? 'Pooja Approvals'; ?></span>
        </a>

        <a href="reports.php" class="sb-link <?= ($currentPage == 'reports.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> <span><?php echo $t['reports'] ?? 'Reports'; ?></span>
        </a>

        <a href="users.php" class="sb-link <?= ($currentPage == 'users.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-gear"></i> <span><?php echo $t['users_and_committee'] ?? 'Users & Committee'; ?></span>
        </a>

        <a href="gallery.php" class="sb-link <?= ($currentPage == 'gallery.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-images"></i> <span><?php echo $t['gallery'] ?? 'Gallery'; ?></span>
        </a>

        <a href="settings.php" class="sb-link <?= ($currentPage == 'settings.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-gear"></i> <span><?php echo $t['settings'] ?? 'Settings'; ?></span>
        </a>

        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span><?php echo $t['my_profile'] ?? 'My Profile'; ?></span>
        </a>

    </div>

    <div class="mt-auto p-2 border-top">
        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i> <span><?php echo $t['logout']; ?></span>
        </a>
    </div>
</nav>

<div class="offcanvas offcanvas-start sb-offcanvas" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><?php echo $t['chairman_menu'] ?? 'Chairman Menu'; ?></h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0 pt-3" style="overflow-y: hidden;">
        <div class="nav flex-column flex-grow-1" style="overflow-y: auto; flex-wrap: nowrap; min-height: 0; padding-bottom: 20px;">

            <a href="../../index.php" class="sb-link">
                <i class="bi bi-house"></i> <span><?php echo $t['home'] ?? 'Home'; ?></span>
            </a>
            <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <span><?php echo $t['dashboard'] ?? 'Dashboard'; ?></span>
            </a>

            <a href="event_approvals.php"
                class="sb-link <?= ($currentPage == 'event_approvals.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-calendar2-check"></i> <span><?php echo $t['event_approvals'] ?? 'Event Approvals'; ?></span>
            </a>

            <a href="pooja_requests.php" class="sb-link <?= ($currentPage == 'pooja_requests.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-journal-check"></i> <span><?php echo $t['pooja_approvals'] ?? 'Pooja Approvals'; ?></span>
            </a>

            <a href="reports.php" class="sb-link <?= ($currentPage == 'reports.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> <span><?php echo $t['reports'] ?? 'Reports'; ?></span>
            </a>

            <a href="users.php" class="sb-link <?= ($currentPage == 'users.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-person-gear"></i> <span><?php echo $t['users_and_committee'] ?? 'Users & Committee'; ?></span>
            </a>

            <a href="gallery.php" class="sb-link <?= ($currentPage == 'gallery.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-images"></i> <span><?php echo $t['gallery'] ?? 'Gallery'; ?></span>
            </a>

            <a href="settings.php" class="sb-link <?= ($currentPage == 'settings.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-gear"></i> <span><?php echo $t['settings'] ?? 'Settings'; ?></span>
            </a>

            <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-person-circle"></i> <span><?php echo $t['my_profile'] ?? 'My Profile'; ?></span>
            </a>
        </div>

        <div class="border-top p-2 bg-light">
            <a href="../../auth/logout.php" class="sb-link text-danger">
                <i class="bi bi-power"></i> <span><?php echo $t['logout']; ?></span>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('contextmenu', function(e) {
        if (e.target.closest('.sb-sidebar') || e.target.closest('.sb-offcanvas')) {
            e.preventDefault();
        }
    });
</script>