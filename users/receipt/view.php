<?php
require_once __DIR__ . '/../../includes/no_cache.php';
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
$role = $_SESSION['primary_role'] ?? ($_SESSION['role'] ?? 'member');

$backUrl = '../customer/my_receipts.php';
if ($role === 'chairman') {
    $backUrl = '../chairman/receipts.php';
} elseif ($role === 'secretary') {
    $backUrl = '../secretary/receipts.php';
} elseif ($role === 'vice_chairman') {
    $backUrl = '../vice_chairman/receipts.php';
} elseif ($role === 'treasurer') {
    $backUrl = '../treasurer/receipts.php';
} elseif ($role === 'manager') {
    $backUrl = '../manager/receipts.php';
} elseif ($role === 'member') {
    $backUrl = '../member/my_receipts.php';
}

/* ---------------------------
   INPUT VALIDATION
--------------------------- */
$receiptNo = $_GET['no'] ?? '';
if ($receiptNo === '') {
    die($t['invalid_receipt_reference']);
}

/* ---------------------------
   FETCH RECEIPT
--------------------------- */
$stmt = $con->prepare("SELECT * FROM receipt WHERE receipt_no = ? LIMIT 1");
$stmt->bind_param("s", $receiptNo);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    die($t['receipt_not_found']);
}

$paymentMeta = null;
$paymentMethodLabel = '';
$pStmt = $con->prepare("SELECT payment_method, status FROM payments WHERE receipt_id = ? LIMIT 1");
if ($pStmt) {
    $pStmt->bind_param("i", $receipt['id']);
    $pStmt->execute();
    $paymentMeta = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();
}
if (!empty($paymentMeta['payment_method'])) {
    $method = strtolower($paymentMeta['payment_method']);
    $paymentMethodLabel = $method === 'upi' ? 'GPay' : ucfirst($method);
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
    $q = $con->prepare("SELECT c.title AS name, c.quantity, c.unit, c.description, c.contributor_name, ct.type AS category FROM contributions c LEFT JOIN contribution_type ct ON c.contribution_type_id = ct.id WHERE c.receipt_id = ? LIMIT 1");
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
    <title><?php echo $t['receipt']; ?> #<?= htmlspecialchars($receipt['receipt_no']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-border: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-bg: #f0f2f5;
            --ant-success: #52c41a;
        }

        body {
            background-color: var(--ant-bg);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--ant-text);
            -webkit-user-select: none;
            user-select: none;
        }

        .page-wrap {
            max-width: 1100px;
            margin: 24px auto 40px;
            padding: 0 16px;
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .receipt-card {
            background: #fff;
            border: 1px solid var(--ant-border);
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            padding: 24px;
            position: relative;
            overflow: hidden;
            min-height: 360px;
        }

        .receipt-container {
            display: none;
        }

        .receipt-card::before {
            content: "\F396";
            font-family: "bootstrap-icons";
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 48px;
            color: rgba(22, 119, 255, 0.08);
        }

        .receipt-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--ant-border);
            margin-bottom: 16px;
        }

        .temple-title {
            font-weight: 800;
            font-size: 18px;
            color: #000;
        }

        .receipt-no {
            font-family: 'SFMono-Regular', Consolas, monospace;
            background: #e6f4ff;
            color: var(--ant-primary);
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 600;
            user-select: text !important;
            display: inline-block;
        }

        .label-text {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--ant-text-sec);
            font-weight: 700;
            margin-bottom: 3px;
        }

        .value-text {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #000;
        }

        .receipt-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 20px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .amount-block {
            background: #fafafa;
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid var(--ant-border);
            text-align: right;
            margin-top: 8px;
        }

        .amount-val {
            font-size: 20px;
            font-weight: 800;
            color: var(--ant-primary);
            user-select: text !important;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f6ffed;
            color: var(--ant-success);
            border: 1px solid #b7eb8f;
            font-size: 12px;
            font-weight: 700;
        }

        .copy-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            background: #f0f5ff;
            color: #2f54eb;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .no-print-bar {
            max-width: 1100px;
            margin: 16px auto 0;
            padding: 0 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 991px) {
            .receipt-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print-bar {
                display: none !important;
            }

            .page-wrap {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            .receipt-grid {
                grid-template-columns: 1fr;
                gap: 12mm;
            }

            .receipt-card {
                box-shadow: none;
                border: 1px solid #ddd;
                min-height: auto;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="no-print-bar">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-light border rounded-pill px-4 fw-bold">
            <i class="bi bi-arrow-left me-2"></i> <?php echo $t['back']; ?>
        </a>
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm"
            style="background: var(--ant-primary); border:none;">
            <i class="bi bi-printer-fill me-2"></i> <?php echo $t['print_official_receipt']; ?>
        </button>
    </div>

    <div class="page-wrap">
        <div class="receipt-grid">
            <?php
            $receiptCopies = [
                $t['receipt_copy_customer'] ?? 'Customer Copy',
                $t['receipt_copy_office'] ?? 'Office Copy'
            ];
            ?>
            <?php foreach ($receiptCopies as $copyLabel): ?>
                <section class="receipt-card">
                    <div class="receipt-top">
                        <div>
                            <div class="temple-title"><?= htmlspecialchars($t['title']) ?></div>
                            <div class="small text-muted"><?php echo $t['official_ack_receipt']; ?></div>
                        </div>
                        <div class="text-end">
                            <div class="copy-badge"><?= htmlspecialchars($copyLabel) ?></div>
                            <div class="label-text"><?php echo $t['receipt_number']; ?></div>
                            <div class="receipt-no">#<?= htmlspecialchars($receipt['receipt_no']) ?></div>
                        </div>
                    </div>

                    <div class="receipt-body">
                        <div>
                            <div class="label-text"><?php echo $t['contributor']; ?></div>
                            <div class="value-text"><?= htmlspecialchars($details['contributor_name'] ?? ($details['name'] ?? $_SESSION['user_name'])) ?></div>
                        </div>

                        <div class="text-end">
                            <div class="label-text"><?php echo $t['transaction_status']; ?></div>
                            <div class="status-pill"><?php echo $t['completed']; ?></div>
                        </div>

                        <div>
                            <div class="label-text"><?php echo $t['purpose_of_payment']; ?></div>
                            <div class="value-text">
                                <span class="badge bg-light text-dark border fw-bold px-2"><?= ucfirst($receipt['purpose']) ?></span>
                                <?php if ($source === 'pooja'): ?>
                                    <div class="small text-muted mt-1"><?php echo $t['scheduled']; ?>:
                                        <?= date('d M Y', strtotime($details['pooja_date'])) ?> (<?= $details['time_slot'] ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <div class="label-text"><?php echo $t['date_issued']; ?></div>
                            <div class="value-text"><?= date('d F Y, h:i A', strtotime($receipt['issued_on'])) ?></div>
                        </div>

                        <?php if (!empty($paymentMethodLabel)): ?>
                            <div class="field-full">
                                <div class="label-text"><?php echo $t['payment_method']; ?></div>
                                <div class="value-text"><?= htmlspecialchars($paymentMethodLabel) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($source === 'contributions'): ?>
                            <div class="field-full">
                                <div class="label-text"><?php echo $t['item_name'] ?? 'Item Details'; ?></div>
                                <div class="value-text">
                                    <?= htmlspecialchars($details['name']) ?> 
                                    <span class="badge bg-light text-secondary border ms-2"><?= htmlspecialchars($details['category']) ?></span>
                                </div>
                            </div>
                            <div class="field-full">
                                <div class="label-text"><?php echo $t['quantity_received'] ?? 'Quantity'; ?></div>
                                <div class="value-text"><?= htmlspecialchars($details['quantity'] . ' ' . $details['unit']) ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="field-full amount-block">
                            <div class="label-text"><?php echo $t['total_value_received']; ?></div>
                            <div class="amount-val">
                                <?= $receipt['amount'] > 0 ? '&#8377;' . number_format($receipt['amount'], 2) : ($t['in_kind'] ?? 'In Kind') ?>
                            </div>
                            <div class="small text-muted italic mt-1" style="font-size: 11px;">
                                <?php echo $t['digital_document_note']; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top text-center">
                        <div class="small text-muted">
                            <?php echo $t['thank_you_support_devotion']; ?> <strong><?php echo $t['divine_blessings']; ?></strong>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="receipt-container">
        <div class="receipt-content">
            <div class="receipt-header d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($t['title']) ?></h2>
                    <p class="text-muted small mb-0"><?php echo $t['official_ack_receipt']; ?></p>
                </div>
                <div class="text-end">
                    <div class="label-text"><?php echo $t['receipt_number']; ?></div>
                    <span class="receipt-no">#<?= htmlspecialchars($receipt['receipt_no']) ?></span>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-7">
                    <div class="label-text"><?php echo $t['devotee_contributor']; ?></div>
                    <div class="data-text"><?= htmlspecialchars($details['contributor_name'] ?? ($details['name'] ?? $_SESSION['user_name'])) ?></div>

                    <div class="label-text"><?php echo $t['purpose_of_payment']; ?></div>
                    <div class="data-text">
                        <span
                            class="badge bg-light text-dark border fw-bold px-3"><?= ucfirst($receipt['purpose']) ?></span>
                        <?php if ($source === 'pooja'): ?>
                            <div class="small text-muted mt-1"><?php echo $t['scheduled']; ?>:
                                <?= date('d M Y', strtotime($details['pooja_date'])) ?> (<?= $details['time_slot'] ?>)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="label-text"><?php echo $t['date_issued']; ?></div>
                    <div class="data-text"><?= date('d F Y, h:i A', strtotime($receipt['issued_on'])) ?></div>
                </div>

                <div class="col-5 text-end">
                    <?php if ($source === 'contributions'): ?>
                        <div class="label-text"><?php echo $t['item_name'] ?? 'Item Details'; ?></div>
                        <div class="data-text mb-2"><?= htmlspecialchars($details['name']) ?> (<?= htmlspecialchars($details['category'] ?? 'N/A') ?>)</div>
                        <div class="label-text"><?php echo $t['quantity_received'] ?? 'Quantity'; ?></div>
                        <div class="data-text mb-2"><?= htmlspecialchars($details['quantity'] . ' ' . $details['unit']) ?></div>
                    <?php endif; ?>

                    <div class="label-text"><?php echo $t['transaction_status']; ?></div>
                    <div class="data-text text-success"><?php echo $t['completed']; ?></div>

                    <?php if (!empty($paymentMethodLabel)): ?>
                        <div class="label-text"><?php echo $t['payment_method']; ?></div>
                        <div class="data-text"><?= htmlspecialchars($paymentMethodLabel) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="amount-block">
                <div class="label-text"><?php echo $t['total_value_received']; ?></div>
                <div class="amount-val">
                    <?= $receipt['amount'] > 0 ? '&#8377;' . number_format($receipt['amount'], 2) : ($t['in_kind'] ?? 'In Kind') ?>
                </div>
                <div class="small text-muted italic mt-2" style="font-size: 11px;">
                    <?php echo $t['digital_document_note']; ?>
                </div>
            </div>

            <div class="verified-stamp"><?php echo $t['verified_system']; ?></div>

            <div class="mt-5 pt-5 text-center border-top">
                <p class="small text-muted mb-0">
                    <?php echo $t['thank_you_support_devotion']; ?><br>
                    <strong><?php echo $t['divine_blessings']; ?></strong>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    </script>
</body>

</html>
