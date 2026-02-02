<?php
session_start();
require '../../includes/lang.php';
require '../../config/db.php';

$userId = $_SESSION['user_id'];

$result = $con->prepare("
    SELECT p.*, pt.type
    FROM pooja p
    JOIN pooja_type pt ON pt.id = p.pooja_type_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");

$result->bind_param("i", $userId);
$result->execute();
$data = $result->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Poojas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="container py-5">
    <div class="row">

        <?php include __DIR__ . '/../includes/customer_sidebar.php'; ?>

        <div class="col-lg-9">
            <div class="card dashboard-card">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">My Pooja Bookings</h4>

                    <table class="table">
                        <thead>
                        <tr>
                            <th>Pooja</th>
                            <th>Date</th>
                            <th>Slot</th>
                            <th>Fee</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $data->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['type']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['pooja_date'])); ?></td>
                                <td><?php echo ucfirst($row['time_slot'] ?? '-'); ?></td>
                                <td>₹<?php echo number_format($row['fee'], 2); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
