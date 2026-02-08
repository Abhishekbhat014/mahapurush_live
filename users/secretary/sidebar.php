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
</style>

<nav class="col-lg-2 d-none d-lg-flex flex-column sb-sidebar shadow-sm p-0">

    <div class="px-4 py-4">
        <small class="sb-title text-uppercase text-muted fw-bold">
            <?php echo $t['secretary_portal'] ?? 'Secretary Portal'; ?>
        </small>
    </div>

    <div class="nav flex-column flex-grow-1" style="overflow-y: auto;">
        <a href="../../index.php" class="sb-link">
            <i class="bi bi-house"></i> <span>Home</span>
        </a>

        <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>



        <a href="pooja_bookings.php" class="sb-link <?= ($currentPage == 'pooja_bookings.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span>Pooja Requests</span>
        </a>

        <a href="donations.php" class="sb-link <?= ($currentPage == 'donations.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span>Donations</span>
        </a>

        <a href="receipts.php" class="sb-link <?= ($currentPage == 'receipts.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span>Receipts</span>
        </a>

        <a href="events.php" class="sb-link <?= ($currentPage == 'events.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-calendar-event"></i> <span>Events</span>
        </a>


        <a href="gallery.php" class="sb-link <?= ($currentPage == 'gallery.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-images"></i> <span>Gallery</span>
        </a>



        <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span>My Profile</span>
        </a>

    </div>

    <div class="mt-auto p-2 border-top">
        <a href="../../auth/logout.php" class="sb-link text-danger">
            <i class="bi bi-power"></i> <span>Logout</span>
        </a>
    </div>
</nav>

<div class="offcanvas offcanvas-start sb-offcanvas" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Secretary Menu</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0 pt-3">
        <div class="nav flex-column flex-grow-1" style="overflow-y: auto;">

            <a href="../../index.php" class="sb-link">
                <i class="bi bi-house"></i> Home
            </a>
            <a href="dashboard.php" class="sb-link <?= ($currentPage == 'dashboard.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>



            <a href="pooja_bookings.php"
                class="sb-link <?= ($currentPage == 'pooja_bookings.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-calendar-check"></i> Pooja Requests
            </a>
            <a href="donations.php" class="sb-link <?= ($currentPage == 'donations.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-heart-fill"></i> Donations
            </a>
            <a href="receipts.php" class="sb-link <?= ($currentPage == 'receipts.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i> Receipts
            </a>
            <a href="events.php" class="sb-link <?= ($currentPage == 'events.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-calendar-event"></i> Events
            </a>


            <a href="gallery.php" class="sb-link <?= ($currentPage == 'gallery.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-images"></i> Gallery
            </a>



            <a href="profile.php" class="sb-link <?= ($currentPage == 'profile.php') ? 'sb-active' : '' ?>">
                <i class="bi bi-person-circle"></i> Profile
            </a>
        </div>

        <div class="border-top p-2 bg-light">
            <a href="../../auth/logout.php" class="sb-link text-danger">
                <i class="bi bi-power"></i> <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('contextmenu', function (e) {
        if (e.target.closest('.sb-sidebar') || e.target.closest('.sb-offcanvas')) {
            e.preventDefault();
        }
    });
</script>