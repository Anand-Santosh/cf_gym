<?php
require_once '../includes/auth.php';
require_once '../includes/header.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

$booking_id = $_GET['id'] ?? null;

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, p.name as package_name, p.description, p.duration_months, p.price, p.features,
           t.full_name as trainer_name, t.specialization, t.image as trainer_image
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    LEFT JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.booking_id = ?
    AND b.member_id = (SELECT member_id FROM members WHERE user_id = ?)
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php");
    exit();
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Booking Details</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Package Information</h5>
                </div>
                <div class="card-body">
                    <h4><?= htmlspecialchars($booking['package_name']) ?></h4>
                    <p class="lead">$<?= number_format($booking['price'], 2) ?> for <?= $booking['duration_months'] ?> month<?= $booking['duration_months'] > 1 ? 's' : '' ?></p>
                    
                    <h5 class="mt-4">Description</h5>
                    <p><?= htmlspecialchars($booking['description']) ?></p>
                    
                    <h5 class="mt-4">Package Features</h5>
                    <ul>
                        <?php 
                        $features = explode(',', $booking['features']);
                        foreach($features as $feature): 
                        ?>
                            <li><?= trim($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Booking Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Status</h6>
                        <span class="badge bg-<?= 
                            $booking['status'] == 'active' ? 'success' : 
                            ($booking['status'] == 'completed' ? 'info' : 'warning') 
                        ?>">
                            <?= ucfirst($booking['status']) ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Start Date</h6>
                        <p><?= date('M j, Y', strtotime($booking['start_date'])) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>End Date</h6>
                        <p><?= date('M j, Y', strtotime($booking['end_date'])) ?></p>
                    </div>
                    
                    <?php if($booking['trainer_name']): ?>
                        <div class="mb-3">
                            <h6>Assigned Trainer</h6>
                            <div class="d-flex align-items-center">
                                <img src="../assets/images/trainers/<?= $booking['trainer_image'] ?? 'default.jpg' ?>" 
                                     class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <p class="mb-0"><?= htmlspecialchars($booking['trainer_name']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($booking['specialization']) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($booking['status'] == 'active'): ?>
                        <a href="book_package.php?package_id=<?= $booking['package_id'] ?>" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-repeat"></i> Renew Package
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($booking['trainer_name']): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Trainer Contact</h5>
                    </div>
                    <div class="card-body">
                        <a href="message.php?trainer_id=<?= $booking['trainer_id'] ?>" class="btn btn-outline-success w-100 mb-2">
                            <i class="bi bi-chat"></i> Send Message
                        </a>
                        <a href="schedule_session.php" class="btn btn-success w-100">
                            <i class="bi bi-calendar-plus"></i> Schedule Session
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>