<?php
session_start();
require __DIR__ . '/includes/lang.php';
require __DIR__ . '/config/db.php';

$categories = [];
$catQuery = mysqli_query($con, "SELECT * FROM gallery_category ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($catQuery)) {
    $categories[] = $row;
}

$imgCountRes = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM gallery WHERE type = 'image'");
$imgCountRow = $imgCountRes ? mysqli_fetch_assoc($imgCountRes) : ['cnt' => 0];
$totalImages = (int) ($imgCountRow['cnt'] ?? 0);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['gallery']; ?> - <?php echo $t['title']; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
            --ant-bg-layout: #f8f9fa;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-border-color: #f0f0f0;
            --ant-radius: 12px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--ant-bg-layout);
            color: var(--ant-text);
            min-height: 100vh;
        }

        /* --- Hero Section (Consistent with Index) --- */
        .ant-hero {
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 70%);
            padding: 80px 0;
            text-align: center;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 40px;
        }

        .ant-hero h1 {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, #111 0%, #444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- Ant Card (High Depth) --- */
        .ant-card {
            background: #ffffff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }

        .ant-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--ant-shadow);
        }

        .ant-card-cover img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }

        .ant-card:hover img {
            transform: scale(1.08);
        }

        /* --- Ant Tag --- */
        .ant-tag {
            display: inline-block;
            padding: 6px 16px;
            font-size: 11px;
            font-weight: 700;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ant-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--ant-border-color), transparent);
            margin: 40px 0;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <section class="ant-hero">
        <div class="container">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3 fw-bold text-uppercase"
                style="letter-spacing: 1px; font-size: 11px;">
                <?php echo $t['our_spiritual_glimpses']; ?>
            </span>
            <h1><?php echo $t['temple_gallery']; ?></h1>
            <p class="text-secondary small mb-0 mx-auto" style="max-width: 600px;">
                <?php echo $t['here_are_glimpses']; ?>
            </p>
        </div>
    </section>

    <main class="container">
        <?php if ($totalImages === 0): ?>
            <div class="alert border-0 shadow-sm text-center"
                style="background: #f8f9fa; color: var(--ant-text-sec); border-radius: 12px;">
                <i class="bi bi-images fs-2 d-block mb-2" style="opacity: 0.4;"></i>
                <?php echo $t['no_gallery_items'] ?? 'No gallery images available right now.'; ?>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <div class="mb-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="ant-tag"><?= htmlspecialchars($cat['type']) ?></span>
                        <div class="flex-grow-1" style="height: 1px; background: var(--ant-border-color);"></div>
                    </div>

                    <div class="row g-4">
                        <?php
                        $catId = $cat['id'];
                        $imgQuery = mysqli_query($con, "SELECT * FROM gallery WHERE gallery_category_id = $catId AND type = 'image' ORDER BY created_at DESC");
                        while ($img = mysqli_fetch_assoc($imgQuery)): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="ant-card" data-bs-toggle="modal" data-bs-target="#imgModal<?= $img['id'] ?>">
                                    <div class="ant-card-cover">
                                        <img src="gallery/<?= htmlspecialchars($img['content']) ?>" loading="lazy"
                                            alt="<?php echo $t['gallery_image_alt']; ?>">
                                    </div>
                                    <div
                                        class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center opacity-0 bg-dark bg-opacity-25 transition-all hover-opacity-100">
                                        <i class="bi bi-eye text-white fs-2"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="imgModal<?= $img['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content shadow-lg">
                                        <div class="modal-body p-1 bg-dark">
                                            <img src="gallery/<?= htmlspecialchars($img['content']) ?>" class="img-fluid w-100">
                                        </div>
                                        <div class="modal-footer border-0 justify-content-end py-2">
                                            <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal"
                                                style="border-radius: 6px;"><?php echo $t['close']; ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

