<?php
// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="col-lg-2 d-none d-lg-block ant-sidebar shadow-sm">
    <div class="px-4 mb-4">
        <small class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            Devotee Portal
        </small>
    </div>

    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
        </a>

        <a href="donate.php" class=" nav-link-custom <?= ($currentPage == 'donate.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span>Make Donation</span>
        </a>

        <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span>Pooja Bookings</span>
        </a>

        <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span>My Profile</span>
        </a>

        <div class="ant-divider" style="margin: 20px 0; opacity: 0.5;"></div>

        <a href="../../index.php" class="nav-link-custom">
            <i class="bi bi-house"></i> <span>Back to Home</span>
        </a>

        <a href="../../auth/logout.php" class="nav-link-custom text-danger">
            <i class="bi bi-power"></i> <span>Logout</span>
        </a>
    </div>
</nav>

<div class="offcanvas offcanvas-start" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 pt-3">
        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="../donate.php" class="nav-link-custom"><i class="bi bi-heart-fill"></i> Make Donation</a>
        <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> My Profile</a>
    </div>
</div>