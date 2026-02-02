<?php
// Determine which page is active to apply the 'active' class
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="col-lg-2 d-none d-lg-flex flex-column ant-sidebar shadow-sm"
    style="height: calc(100vh - 64px); position: sticky; top: 64px;">
    <div class="px-4 py-4">
        <small class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            Member Portal
        </small>
    </div>

    <div class="nav flex-column flex-grow-1">
        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
        </a>

        <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span>Bookings</span>
        </a>

        <a href="committee_info.php"
            class="nav-link-custom <?= ($currentPage == 'committee_info.php') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> <span>Committee</span>
        </a>

        <a href="donate.php" class="nav-link-custom <?= ($currentPage == 'donate.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span>Donation</span>
        </a>

        <a href="contribute.php" class="nav-link-custom <?= ($currentPage == 'contribute.php') ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> <span>Contribution</span>
        </a>
        <a href="my_requests.php" class="nav-link-custom <?= ($currentPage == 'my_requests.php') ? 'active' : '' ?>">
            <i class="bi bi-hourglass-split"></i> <span>My Requests</span>
        </a>

        <a href="my_receipts.php" class="nav-link-custom <?= ($currentPage == 'my_receipts.php') ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span>Receipts</span>
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

<div class="offcanvas offcanvas-start" id="sidebarMenu" style="width: 280px; border-right: none;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Menu</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0 pt-3">
        <div class="nav flex-column flex-grow-1">
            <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> Bookings
            </a>
            <a href="committee_info.php"
                class="nav-link-custom <?= ($currentPage == 'committee_info.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Committee
            </a>
            <a href="donate.php" class="nav-link-custom <?= ($currentPage == 'donate.php') ? 'active' : '' ?>">
                <i class="bi bi-heart-fill"></i> Donate
            </a>
            <a href="contribute.php" class="nav-link-custom <?= ($currentPage == 'contribute.php') ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Contribute
            </a>
            <a href="my_requests.php"
                class="nav-link-custom <?= ($currentPage == 'my_requests.php') ? 'active' : '' ?>">
                <i class="bi bi-hourglass-split"></i> My Requests
            </a>


            <a href="my_receipts.php"
                class="nav-link-custom <?= ($currentPage == 'my_receipts.php') ? 'active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i> Receipts
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