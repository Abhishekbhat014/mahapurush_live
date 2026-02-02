<?php
session_start();

require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';

/* ---------------------------
   AUTH CHECK
--------------------------- */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'member'; // Changed to check standard 'role'

/* ---------------------------
   INPUT VALIDATION
--------------------------- */
$receiptNo = $_GET['no'] ?? '';
if ($receiptNo === '') {
    die("Invalid receipt reference.");
}

/* ---------------------------
   FETCH RECEIPT
--------------------------- */
$stmt = $con->prepare("SELECT * FROM receipt WHERE receipt_no = ? LIMIT 1");
$stmt->bind_param("s", $receiptNo);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    die("Receipt not found.");
}

/* ---------------------------
   OWNERSHIP CHECK
--------------------------- */


/* ---------------------------
   FETCH DYNAMIC DETAILS
--------------------------- */
$details = [];
$source = $receipt['source_table'];

if ($source === 'donations') {
    $q = $con->prepare("SELECT donor_name AS name, note FROM payments WHERE receipt_id = ? LIMIT 1");
} elseif ($source === 'pooja') {
    $q = $con->prepare("SELECT pt.type AS name, pj.pooja_date, pj.time_slot FROM pooja pj JOIN pooja_type pt ON pt.id = pj.pooja_type_id WHERE pj.receipt_id = ? LIMIT 1");
} elseif ($source === 'contributions') {
    $q = $con->prepare("SELECT title AS name, quantity, unit, description FROM contributions WHERE receipt_id = ? LIMIT 1");
}

if (isset($q)) {
    $q->bind_param("i", $receipt['id']);
    $q->execute();
    $details = $q->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= htmlspecialchars($receipt['receipt_no']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-border: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
        }

        body {
            background-color: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--ant-text);
            -webkit-user-select: none;
            user-select: none;
        }

        /* The Virtual Paper */
        .receipt-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 60px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        /* AntD Watermark */
        .receipt-container::before {
            content: "\F396";
            /* bi-flower1 */
            font-family: "bootstrap-icons";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 350px;
            color: rgba(22, 119, 255, 0.03);
            z-index: 0;
        }

        .receipt-content {
            position: relative;
            z-index: 1;
        }

        .receipt-header {
            border-bottom: 2px solid var(--ant-primary);
            padding-bottom: 20px;
            margin-bottom: 40px;
        }

        .receipt-no {
            font-family: 'SFMono-Regular', Consolas, monospace;
            background: #e6f4ff;
            color: var(--ant-primary);
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            user-select: text !important;
        }

        .label-text {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--ant-text-sec);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .data-text {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 24px;
            color: #000;
        }

        .amount-block {
            background: #fafafa;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--ant-border);
            text-align: right;
            margin-top: 20px;
        }

        .amount-val {
            font-size: 32px;
            font-weight: 800;
            color: var(--ant-primary);
            user-select: text !important;
        }

        .verified-stamp {
            border: 3px double #52c41a;
            color: #52c41a;
            display: inline-block;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 900;
            text-transform: uppercase;
            transform: rotate(-12deg);
            opacity: 0.7;
            font-size: 18px;
            position: absolute;
            right: 40px;
            bottom: 150px;
        }

        .no-print-bar {
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media print {
            body {
                background: #fff;
            }

            .receipt-container {
                margin: 0;
                padding: 40px;
                box-shadow: none;
                width: 100%;
                max-width: 100%;
            }

            .no-print-bar {
                display: none !important;
            }

            .receipt-container::before {
                color: rgba(0, 0, 0, 0.02);
            }
        }
    </style>
</head>

<body>

    <div class="no-print-bar">
        <a href="javascript:history.back()" class="btn btn-light border rounded-pill px-4 fw-bold">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm"
            style="background: var(--ant-primary); border:none;">
            <i class="bi bi-printer-fill me-2"></i> Print Official Receipt
        </button>
    </div>

    <div class="receipt-container">
        <div class="receipt-content">
            <div class="receipt-header d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($t['title']) ?></h2>
                    <p class="text-muted small mb-0">Official Acknowledgment Receipt</p>
                </div>
                <div class="text-end">
                    <div class="label-text">Receipt Number</div>
                    <span class="receipt-no">#<?= htmlspecialchars($receipt['receipt_no']) ?></span>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-7">
                    <div class="label-text">Devotee / Contributor</div>
                    <div class="data-text"><?= htmlspecialchars($details['name'] ?? $_SESSION['user_name']) ?></div>

                    <div class="label-text">Purpose of Payment</div>
                    <div class="data-text">
                        <span
                            class="badge bg-light text-dark border fw-bold px-3"><?= ucfirst($receipt['purpose']) ?></span>
                        <?php if ($source === 'pooja'): ?>
                            <div class="small text-muted mt-1">Scheduled:
                                <?= date('d M Y', strtotime($details['pooja_date'])) ?> (<?= $details['time_slot'] ?>)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="label-text">Date Issued</div>
                    <div class="data-text"><?= date('d F Y, h:i A', strtotime($receipt['issued_on'])) ?></div>
                </div>

                <div class="col-5 text-end">
                    <?php if ($source === 'contributions'): ?>
                        <div class="label-text">Quantity Received</div>
                        <div class="data-text"><?= htmlspecialchars($details['quantity'] . ' ' . $details['unit']) ?></div>
                    <?php endif; ?>

                    <div class="label-text">Transaction Status</div>
                    <div class="data-text text-success">COMPLETED</div>
                </div>
            </div>

            <div class="amount-block">
                <div class="label-text">Total Value Received</div>
                <div class="amount-val">
                    <?= $receipt['amount'] > 0 ? '₹' . number_format($receipt['amount'], 2) : 'IN-KIND' ?>
                </div>
                <div class="small text-muted italic mt-2" style="font-size: 11px;">
                    This is a digitally generated document. No physical signature is required.
                </div>
            </div>

            <div class="verified-stamp">Verified System</div>

            <div class="mt-5 pt-5 text-center border-top">
                <p class="small text-muted mb-0">
                    Thank you for your support and devotion.<br>
                    <strong>May the divine blessings be with you always.</strong>
                </p>
            </div>
        </div>
    </div>

</body>

</html>