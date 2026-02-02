<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="col-lg-2 d-none d-lg-flex flex-column ant-sidebar shadow-sm"
    style="height: calc(100vh - 64px); position: sticky; top: 64px;">

    <div class="px-4 py-4">
        <small class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            Secretary Portal
        </small>
    </div>

    <div class="nav flex-column flex-grow-1">

        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>

        <a href="pooja_bookings.php"
            class="nav-link-custom <?= ($currentPage == 'pooja_bookings.php') ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span>Pooja Bookings</span>
        </a>

        <a href="contributions.php"
            class="nav-link-custom <?= ($currentPage == 'contributions.php') ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> <span>Contributions</span>
        </a>

        <a href="donations.php" class="nav-link-custom <?= ($currentPage == 'donations.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span>Donations</span>
        </a>

        <a href="payments.php" class="nav-link-custom <?= ($currentPage == 'payments.php') ? 'active' : '' ?>">
            <i class="bi bi-credit-card"></i> <span>Payments</span>
        </a>

        <a href="receipts.php" class="nav-link-custom <?= ($currentPage == 'receipts.php') ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span>Receipts</span>
        </a>

        <a href="reports.php" class="nav-link-custom <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> <span>Reports</span>
        </a>

        <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span>My Profile</span>
        </a>

        <div class="ant-divider" style="margin: 20px 0; opacity: 0.5;"></div>

        <a href="../../index.php" class="nav-link-custom">
            <i class="bi bi-house"></i> <span>Back to Home</span>
        </a>
    </div>

    <div class="mt-auto border-top p-2">
        <a href="../../auth/logout.php" class="nav-link-custom text-danger">
            <i class="bi bi-power"></i> <span>Logout</span>
        </a>
    </div>
</nav>

<!-- MOBILE -->
<div class="offcanvas offcanvas-start" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Secretary Menu</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0 pt-3">
        <div class="nav flex-column flex-grow-1">

            <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <a href="pooja_bookings.php"
                class="nav-link-custom <?= ($currentPage == 'pooja_bookings.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> Pooja Bookings
            </a>

            <a href="contributions.php"
                class="nav-link-custom <?= ($currentPage == 'contributions.php') ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Contributions
            </a>

            <a href="donations.php" class="nav-link-custom <?= ($currentPage == 'donations.php') ? 'active' : '' ?>">
                <i class="bi bi-heart-fill"></i> Donations
            </a>

            <a href="payments.php" class="nav-link-custom <?= ($currentPage == 'payments.php') ? 'active' : '' ?>">
                <i class="bi bi-credit-card"></i> Payments
            </a>

            <a href="receipts.php" class="nav-link-custom <?= ($currentPage == 'receipts.php') ? 'active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i> Receipts
            </a>

            <a href="reports.php" class="nav-link-custom <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> Reports
            </a>

            <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </div>

        <div class="mt-auto border-top p-3 bg-light">
            <a href="../../auth/logout.php" class="nav-link-custom text-danger p-0">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</div>