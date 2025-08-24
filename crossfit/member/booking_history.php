<?php
require_once '../includes/auth.php';
require_once '../includes/header.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member ID
$member_id = $conn->query("SELECT member_id FROM members WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();

// Get all past bookings
$bookings = $conn->query("
    SELECT b.*, p.name as package_name, p.duration_months, p.price, 
           t.full_name as trainer_name
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    LEFT JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.member_id = $member_id
    ORDER BY b.end_date DESC
")->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Booking History</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">All Your Bookings</h5>
        </div>
        <div class="card-body">
            <?php if(empty($bookings)): ?>
                <div class="alert alert-info">No booking history found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Trainer</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['package_name']) ?></td>
                                <td><?= $booking['duration_months'] ?> months</td>
                                <td>$<?= number_format($booking['price'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $booking['status'] == 'active' ? 'success' : 
                                        ($booking['status'] == 'completed' ? 'info' : 'warning') 
                                    ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($booking['start_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($booking['end_date'])) ?></td>
                                <td><?= $booking['trainer_name'] ? htmlspecialchars($booking['trainer_name']) : 'Not assigned' ?></td>
                                <td>
                                    <a href="booking_details.php?id=<?= $booking['booking_id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                    <?php if($booking['status'] == 'completed'): ?>
                                        <a href="book_package.php?package_id=<?= $booking['package_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-arrow-repeat"></i> Renew
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>