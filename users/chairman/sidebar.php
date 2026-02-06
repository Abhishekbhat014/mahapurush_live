<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    body,
    body * {
        -webkit-user-select: none;
        user-select: none;
    }
</style>

<nav class="col-lg-2 d-none d-lg-flex flex-column ant-sidebar shadow-sm"
    style="height: calc(100vh - 64px); position: sticky; top: 64px;">

    <div class="px-4 py-4">
        <small class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['chairman_portal'] ?? 'Chairman Portal'; ?>
        </small>
    </div>

    <div class="nav flex-column flex-grow-1">
        <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span><?php echo $t['dashboard'] ?? 'Dashboard'; ?></span>
        </a>

        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-4" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['services'] ?? 'Services'; ?>
        </small>

        <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
            <i class="bi bi-calendar-plus"></i> <span><?php echo $t['book_pooja'] ?? 'Book Pooja'; ?></span>
        </a>

        <a href="donations.php" class="nav-link-custom <?= ($currentPage == 'donations.php') ? 'active' : '' ?>">
            <i class="bi bi-heart-fill"></i> <span><?php echo $t['donations'] ?? 'Donations'; ?></span>
        </a>

        <a href="contribute.php" class="nav-link-custom <?= ($currentPage == 'contribute.php') ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> <span><?php echo $t['contribution'] ?? 'Contribution'; ?></span>
        </a>

        <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
        <small class="text-uppercase text-muted fw-bold px-4" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
            <?php echo $t['management'] ?? 'Management'; ?>
        </small>

        <a href="pooja_requests.php" class="nav-link-custom <?= ($currentPage == 'pooja_requests.php') ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span><?php echo $t['pooja_requests'] ?? 'Pooja Requests'; ?></span>
        </a>

        <a href="contributions.php" class="nav-link-custom <?= ($currentPage == 'contributions.php') ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> <span><?php echo $t['contributions'] ?? 'Contributions'; ?></span>
        </a>

        <a href="receipts.php" class="nav-link-custom <?= ($currentPage == 'receipts.php') ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> <span><?php echo $t['receipts'] ?? 'Receipts'; ?></span>
        </a>

        <a href="committee.php" class="nav-link-custom <?= ($currentPage == 'committee.php') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> <span><?php echo $t['committee'] ?? 'Committee'; ?></span>
        </a>

        <a href="gallery.php" class="nav-link-custom <?= ($currentPage == 'gallery.php') ? 'active' : '' ?>">
            <i class="bi bi-images"></i> <span><?php echo $t['gallery'] ?? 'Gallery'; ?></span>
        </a>

        <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span><?php echo $t['my_profile'] ?? 'My Profile'; ?></span>
        </a>

        <a href="settings.php" class="nav-link-custom <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> <span><?php echo $t['settings'] ?? 'Settings'; ?></span>
        </a>

        <div class="ant-divider" style="margin: 20px 0; opacity: 0.5;"></div>

        <a href="../../index.php" class="nav-link-custom">
            <i class="bi bi-house"></i> <span><?php echo $t['back_to_home'] ?? 'Back to Home'; ?></span>
        </a>
    </div>

    <div class="mt-auto border-top p-2">
        <a href="../../auth/logout.php" class="nav-link-custom text-danger">
            <i class="bi bi-power"></i> <span><?php echo $t['logout'] ?? 'Logout'; ?></span>
        </a>
    </div>
</nav>

<!-- MOBILE -->
<div class="offcanvas offcanvas-start" id="sidebarMenu" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><?php echo $t['chairman_menu'] ?? 'Chairman Menu'; ?></h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0 pt-3">
        <div class="nav flex-column flex-grow-1">
            <a href="dashboard.php" class="nav-link-custom <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <?php echo $t['dashboard'] ?? 'Dashboard'; ?>
            </a>

            <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
            <small class="text-uppercase text-muted fw-bold px-3" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
                <?php echo $t['services'] ?? 'Services'; ?>
            </small>

            <a href="pooja_book.php" class="nav-link-custom <?= ($currentPage == 'pooja_book.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-plus"></i> <?php echo $t['book_pooja'] ?? 'Book Pooja'; ?>
            </a>

            <a href="donations.php" class="nav-link-custom <?= ($currentPage == 'donations.php') ? 'active' : '' ?>">
                <i class="bi bi-heart-fill"></i> <?php echo $t['donations'] ?? 'Donations'; ?>
            </a>

            <a href="contribute.php" class="nav-link-custom <?= ($currentPage == 'contribute.php') ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> <?php echo $t['contribution'] ?? 'Contribution'; ?>
            </a>

            <div class="ant-divider" style="margin: 16px 20px; opacity: 0.5;"></div>
            <small class="text-uppercase text-muted fw-bold px-3" style="font-size: 10px; letter-spacing: 1px; opacity: 0.6;">
                <?php echo $t['management'] ?? 'Management'; ?>
            </small>

            <a href="pooja_requests.php" class="nav-link-custom <?= ($currentPage == 'pooja_requests.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> <?php echo $t['pooja_requests'] ?? 'Pooja Requests'; ?>
            </a>

            <a href="contributions.php" class="nav-link-custom <?= ($currentPage == 'contributions.php') ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> <?php echo $t['contributions'] ?? 'Contributions'; ?>
            </a>

            <a href="receipts.php" class="nav-link-custom <?= ($currentPage == 'receipts.php') ? 'active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i> <?php echo $t['receipts'] ?? 'Receipts'; ?>
            </a>

            <a href="committee.php" class="nav-link-custom <?= ($currentPage == 'committee.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i> <?php echo $t['committee'] ?? 'Committee'; ?>
            </a>

            <a href="gallery.php" class="nav-link-custom <?= ($currentPage == 'gallery.php') ? 'active' : '' ?>">
                <i class="bi bi-images"></i> <?php echo $t['gallery'] ?? 'Gallery'; ?>
            </a>

            <a href="profile.php" class="nav-link-custom <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i> <?php echo $t['my_profile'] ?? 'My Profile'; ?>
            </a>

            <a href="settings.php" class="nav-link-custom <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                <i class="bi bi-gear"></i> <?php echo $t['settings'] ?? 'Settings'; ?>
            </a>
        </div>

        <div class="mt-auto border-top p-3 bg-light">
            <a href="../../auth/logout.php" class="nav-link-custom text-danger p-0">
                <i class="bi bi-box-arrow-right"></i> <?php echo $t['logout'] ?? 'Logout'; ?>
            </a>
        </div>
    </div>
</div>
<script>
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });
</script>
