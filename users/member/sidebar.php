<?php
// Determine which page is active to apply the 'active' class
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="col-lg-2 d-none d-lg-flex flex-column ant-sidebar shadow-sm"
    style="height: calc(100vh - 64px); position: sticky; top: 64px;">
    <div class="px-4 py-4">
        <small class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['member_portal']; ?>
        </small>
    </div>

    <div class="nav flex-column flex-grow-1">
        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> <span><?php echo $t['dashboard']; ?></span>
        </a>

        <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span><?php echo $t['bookings']; ?></span>
        </a>

        <a href="committee_info.php"
            class="nav-link-custom <?= ($currentPage == 'committee_info.php') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> <span><?php echo $t['committee']; ?></span>
        </a>

        <a href="donate.php" class="nav-link-custom <?= ($currentPage == 'donate.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span><?php echo $t['donation']; ?></span>
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
    </div>

    <div class="mt-auto border-top p-2">
        <a href="../../auth/logout.php" class="nav-link-custom text-danger">
            <i class="bi bi-power"></i> <span><?php echo $t['logout']; ?></span>
        </a>
    </div>
</nav>

<div class="offcanvas offcanvas-start" id="sidebarMenu" style="width: 280px; border-right: none;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><?php echo $t['menu']; ?></h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0 pt-3">
        <div class="nav flex-column flex-grow-1">
            <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i> <?php echo $t['dashboard']; ?>
            </a>
            <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> <?php echo $t['bookings']; ?>
            </a>
            <a href="committee_info.php"
                class="nav-link-custom <?= ($currentPage == 'committee_info.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i> <?php echo $t['committee']; ?>
            </a>
            <a href="donate.php" class="nav-link-custom <?= ($currentPage == 'donate.php') ? 'active' : '' ?>">
                <i class="bi bi-heart-fill"></i> <?php echo $t['donate']; ?>
            </a>
            <a href="contribute.php" class="nav-link-custom <?= ($currentPage == 'contribute.php') ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> <?php echo $t['contribute']; ?>
            </a>
            <a href="my_requests.php"
                class="nav-link-custom <?= ($currentPage == 'my_requests.php') ? 'active' : '' ?>">
                <i class="bi bi-hourglass-split"></i> <?php echo $t['my_requests']; ?>
            </a>


            <a href="my_receipts.php"
                class="nav-link-custom <?= ($currentPage == 'my_receipts.php') ? 'active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i> <?php echo $t['receipts']; ?>
            </a>
            <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i> <?php echo $t['my_profile']; ?>
            </a>
        </div>

        <div class="mt-auto border-top p-3 bg-light">
            <a href="../../auth/logout.php" class="nav-link-custom text-danger p-0">
                <i class="bi bi-box-arrow-right"></i> <?php echo $t['logout']; ?>
            </a>
        </div>
    </div>
</div>
