<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$footerIsLoggedIn = $_SESSION['logged_in'] ?? false;
?>
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

    .footer-input-base,
    .footer-textarea-base,
    .footer-select-base {
        width: 100%;
        border: 1px solid #e6e6e6;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        background: #fff;
    }

    .footer-textarea-base {
        min-height: 90px;
        resize: vertical;
    }

    .footer-submit-base {
        background: #1677ff;
        color: #fff;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 600;
        width: 100%;
    }
</style>

<footer class="ant-footer-base">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="fw-bold fs-4 mb-3"><?php echo $t['temple_portal']; ?></div>
                <p class="small text-muted"><?php echo $t['footer_desc']; ?></p>
                <div class="d-flex gap-3 fs-5 mt-3">
                    <i class="bi bi-facebook"></i><i class="bi bi-instagram"></i><i class="bi bi-youtube"></i>
                </div>
            </div>
            <!-- TODO : Fix broken link -->
            <div class="col-lg-2 col-6">
                <h6 class="fw-bold mb-4 small"><?php echo $t['quick_links']; ?></h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="panchang.php" class="footer-link-base"><?php echo $t['panchang']; ?></a></li>
                    <li><a href="gallery.php" class="footer-link-base"><?php echo $t['gallery']; ?></a></li>
                    <li><a href="about.php" class="footer-link-base"><?php echo $t['about_us']; ?></a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="fw-bold mb-4 small"><?php echo $t['devotional']; ?></h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="donate.php" class="footer-link-base"><?php echo $t['donations']; ?></a></li>
                    <li><a href="auth/redirect.php" class="footer-link-base"><?php echo $t['service_pooja']; ?></a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-bold mb-4 small"><?php echo $t['feedback'] ?? 'Feedback'; ?></h6>

                <?php if (!empty($_GET['feedback']) && $_GET['feedback'] === 'success'): ?>
                    <div class="alert alert-success py-2 small mb-3">
                        <?php echo $t['feedback_thanks'] ?? 'Thank you for your feedback!'; ?>
                    </div>
                <?php elseif (!empty($_GET['feedback']) && $_GET['feedback'] === 'error'): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <?php echo $t['feedback_error'] ?? 'Please fill all fields and try again.'; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="feedback_save.php" class="d-grid gap-2">
                    <input type="hidden" name="redirect"
                        value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php'); ?>">
                    <input type="email" name="email" class="footer-input-base"
                        value="<?php echo (isset($_SESSION['user_email']) && $_SESSION['user_email'] != null) ? $_SESSION['user_email'] : '' ?>"
                        placeholder="<?php echo $t['email'] ?? 'Email'; ?>" required>
                    <select name="rating" class="footer-select-base" required>
                        <option value=""><?php echo $t['rating'] ?? 'Rating'; ?></option>
                        <option value="5">5 - <?php echo $t['rating_excellent'] ?? 'Excellent'; ?></option>
                        <option value="4">4 - <?php echo $t['rating_good'] ?? 'Good'; ?></option>
                        <option value="3">3 - <?php echo $t['rating_ok'] ?? 'Okay'; ?></option>
                        <option value="2">2 - <?php echo $t['rating_poor'] ?? 'Poor'; ?></option>
                        <option value="1">1 - <?php echo $t['rating_bad'] ?? 'Bad'; ?></option>
                    </select>
                    <textarea name="message" class="footer-textarea-base"
                        placeholder="<?php echo $t['your_feedback'] ?? 'Share your feedback'; ?>" required></textarea>
                    <button type="submit" class="footer-submit-base">
                        <?php echo $t['submit_feedback'] ?? 'Submit Feedback'; ?>
                    </button>
                </form>

            </div>
        </div>
        <div style="height:1px; background:#f0f0f0; margin: 40px 0;"></div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="small text-muted"><?php echo sprintf($t['copyright_footer'], date("Y")); ?></div>
            <div class="d-flex gap-3 small fw-bold">
                <span class="text-primary">Developed By: Abhishek Bhat & Yojana Gawade</span>
            </div>
        </div>
    </div>
</footer>
<script>
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });
</script>