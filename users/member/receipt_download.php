<?php
session_start();
require '../../includes/lang.php';
require '../../config/db.php';
// Assuming receipt_helper handles ownership logic based on your tables
require '../../includes/receipt_helper.php';

if (!isset($_SESSION['logged_in'])) {
    die($t['unauthorized']);
}

$receiptId = (int) ($_GET['id'] ?? 0);
$uid = (int) $_SESSION['user_id'];

if ($receiptId <= 0) {
    die($t['invalid_receipt']);
}

// Ownership check
if (!validateReceiptOwnership($con, $receiptId, $uid)) {
    die($t['access_denied']);
}

// Fetch unified receipt data (Joining with payments for amount/purpose)
// Adjust the JOINs based on your specific table names if needed
$sql = "SELECT r.*, p.amount, p.donor_name, p.status, p.payment_method 
        FROM receipt r 
        LEFT JOIN payments p ON r.id = p.receipt_id 
        WHERE r.id = ? LIMIT 1";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $receiptId);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    die($t['receipt_not_found']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['receipt']; ?> #<?= htmlspecialchars($receipt['receipt_no']) ?></title>
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

        /* Container for the "Paper" */
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

        /* Subtle Watermark */
        .receipt-container::before {
            content: "\F396";
            /* bi-flower1 */
            font-family: "bootstrap-icons";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 300px;
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
            /* Allow copying receipt number */
        }

        .label-text {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--ant-text-sec);
            font-weight: 700;
        }

        .data-text {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .amount-block {
            background: #fafafa;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--ant-border);
            text-align: right;
            margin-top: 40px;
        }

        .amount-val {
            font-size: 32px;
            font-weight: 800;
            color: var(--ant-primary);
            user-select: text !important;
        }

        .stamp {
            border: 3px double #52c41a;
            color: #52c41a;
            display: inline-block;
            padding: 5px 15px;
            border-radius: 8px;
            font-weight: 900;
            text-transform: uppercase;
            transform: rotate(-15deg);
            opacity: 0.8;
            font-size: 20px;
        }

        /* Buttons logic */
        .action-bar {
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            justify-content: space-between;
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

            .action-bar,
            .ant-header {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="action-bar no-print">
        <a href="receipt_history.php" class="btn btn-light border rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i><?php echo $t['back_to_history']; ?>
        </a>
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm"
            style="background: var(--ant-primary); border:none;">
            <i class="bi bi-printer me-2"></i><?php echo $t['print_receipt']; ?>
        </button>
    </div>

    <div class="receipt-container">
        <div class="receipt-content">
            <div class="receipt-header d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="fw-bold mb-1 text-dark"><?php echo $t['official_receipt']; ?></h2>
                    <p class="text-muted small mb-0"><?php echo $t['issued_by_temple_admin']; ?></p>
                </div>
                <div class="text-end">
                    <span class="label-text d-block"><?php echo $t['receipt_number']; ?></span>
                    <span class="receipt-no">#<?= htmlspecialchars($receipt['receipt_no']) ?></span>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-6">
                    <div class="label-text"><?php echo $t['donor_devotee_name']; ?></div>
                    <div class="data-text"><?= htmlspecialchars($receipt['donor_name'] ?? $_SESSION['user_name']) ?>
                    </div>

                    <div class="label-text"><?php echo $t['date_of_issue']; ?></div>
                    <div class="data-text"><?= date("d M Y, h:i A", strtotime($receipt['issued_on'])) ?></div>
                </div>
                <div class="col-6 text-end">
                    <div class="label-text"><?php echo $t['payment_status']; ?></div>
                    <div class="mb-4 mt-2">
                        <span class="stamp"><?php echo $t['verified_success']; ?></span>
                    </div>

                    <div class="label-text"><?php echo $t['payment_method']; ?></div>
                    <div class="data-text"><?= ucfirst(htmlspecialchars($receipt['payment_method'] ?? $t['manual'])) ?>
                    </div>
                </div>
            </div>

            <div class="amount-block">
                <div class="label-text"><?php echo $t['total_contribution']; ?></div>
                <div class="amount-val">₹<?= number_format($receipt['amount'], 2) ?></div>
                <div class="small text-muted italic mt-2">
                    <?php echo $t['receipt_note']; ?>
                </div>
            </div>

            <div class="mt-5 pt-5 text-center border-top">
                <p class="small text-muted mb-0">
                    <?php echo $t['thank_you_contribution']; ?><br>
                    <strong><?php echo $t['blessings_message']; ?></strong>
                </p>
            </div>
        </div>
    </div>

</body>

</html>
