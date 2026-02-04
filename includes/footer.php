<style>
    .ant-footer-base {
        background: #fafafa;
        border-top: 1px solid #f0f0f0;
        padding: 60px 0 30px;
        color: rgba(0, 0, 0, 0.88);
        margin-top: 50px;
    }

    .footer-link-base {
        color: rgba(0, 0, 0, 0.45);
        text-decoration: none;
        font-size: 14px;
        transition: 0.2s;
    }

    .footer-link-base:hover {
        color: #1677ff;
    }
</style>

<footer class="ant-footer-base">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="fw-bold fs-4 mb-3"><?php echo $t['temple_portal']; ?></div>
                <p class="small text-muted"><?php echo $t['footer_desc']; ?></p>
            </div>
            <!-- TODO : Fix broken link -->
            <div class="col-lg-2 offset-lg-1 col-6">
                <h6 class="fw-bold mb-4 small"><?php echo $t['quick_links']; ?></h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="/panchang.php" class="footer-link-base"><?php echo $t['panchang']; ?></a></li>
                    <li><a href="../gallery.php" class="footer-link-base"><?php echo $t['gallery']; ?></a></li>
                    <li><a href="/about.php" class="footer-link-base"><?php echo $t['about_us']; ?></a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="fw-bold mb-4 small"><?php echo $t['devotional']; ?></h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="donate.php" class="footer-link-base"><?php echo $t['donations']; ?></a></li>
                    <li><a href="pooja.php" class="footer-link-base"><?php echo $t['service_pooja']; ?></a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="fw-bold mb-4 small"><?php echo $t['social']; ?></h6>
                <div class="d-flex gap-3 fs-5">
                    <i class="bi bi-facebook"></i><i class="bi bi-instagram"></i><i class="bi bi-youtube"></i>
                </div>
            </div>
        </div>
        <div style="height:1px; background:#f0f0f0; margin: 40px 0;"></div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="small text-muted"><?php echo sprintf($t['copyright_footer'], date("Y")); ?></div>
            <div class="d-flex gap-3 small fw-bold">
                <span class="text-primary">Yojana Gawade</span>
                <span class="text-primary">Abhishek Bhat</span>
            </div>
        </div>
    </div>
</footer>