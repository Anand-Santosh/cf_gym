<?php
ob_start();
session_start();
require_once '../config/database.php';

// Only allow members
if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member info
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Get active bookings
$activeBookings = $conn->query("
    SELECT b.*, p.name as package_name, p.duration_months, p.price, 
           t.full_name as trainer_name, t.specialization
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    LEFT JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.member_id = {$member['member_id']} 
    AND b.status = 'active'
    ORDER BY b.end_date DESC
")->fetchAll();

// Get past bookings
$pastBookings = $conn->query("
    SELECT b.*, p.name as package_name, p.duration_months, p.price, 
           t.full_name as trainer_name
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    LEFT JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.member_id = {$member['member_id']} 
    AND b.status != 'active'
    ORDER BY b.end_date DESC
    LIMIT 5
")->fetchAll();

// Get assigned trainer
$assignedTrainer = $conn->query("
    SELECT t.* FROM trainers t
    JOIN bookings b ON t.trainer_id = b.trainer_id
    WHERE b.member_id = {$member['member_id']}
    AND b.status = 'active'
    LIMIT 1
")->fetch();

$pageTitle = "Member Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--darker);
            color: var(--text-light);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        .dashboard-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--dark);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-header.bg-secondary {
            background-color: #6c757d !important;
        }

        .card-header.bg-info {
            background-color: var(--info) !important;
        }

        .card-body {
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.4);
        }

        .btn-outline-primary {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-home {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            margin-right: 10px;
        }

        .btn-home:hover {
            background-color: var(--primary);
            color: white;
        }

        .table {
            width: 100%;
            color: var(--text-light);
            border-collapse: collapse;
        }

        .table th {
            background-color: rgba(255,255,255,0.05);
            padding: 12px;
            text-align: left;
        }

        .table td {
            padding: 12px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .bg-success {
            background-color: var(--success) !important;
        }

        .bg-warning {
            background-color: var(--warning) !important;
            color: #000;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.2);
            border: 1px solid rgba(23, 162, 184, 0.3);
            color: var(--info);
        }

        .quick-action-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .quick-action-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .trainer-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header with Home Button -->
        <div class="dashboard-header">
            <div>
                <h1>Welcome, <?= htmlspecialchars($member['full_name']) ?></h1>
                <p class="lead">Your CrossFit Dashboard</p>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="btn btn-home">
                    <i class="bi bi-house"></i> Home
                </a>
                <a href="profile.php" class="btn btn-outline-primary">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card quick-action-card">
                    <div class="card-body">
                        <i class="bi bi-box-seam"></i>
                        <h5>Book a Package</h5>
                        <p>Browse and book our fitness packages</p>
                        <a href="book_package.php" class="btn">View Packages</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card quick-action-card">
                    <div class="card-body">
                        <i class="bi bi-people"></i>
                        <h5>My Trainer</h5>
                        <p>Connect with your assigned trainer</p>
                        <?php if($assignedTrainer): ?>
                            <a href="trainer.php" class="btn">View Trainer</a>
                        <?php else: ?>
                            <a href="book_trainer.php" class="btn">Book a Trainer</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card quick-action-card">
                    <div class="card-body">
                        <i class="bi bi-cart"></i>
                        <h5>Supplements</h5>
                        <p>Order fitness supplements</p>
                        <a href="supplements.php" class="btn">View Supplements</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Bookings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Active Memberships</h5>
            </div>
            <div class="card-body">
                <?php if(empty($activeBookings)): ?>
                    <div class="alert alert-info">You don't have any active memberships. <a href="book_package.php" style="color: var(--primary);">Book a package now</a>.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Trainer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($activeBookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['package_name']) ?></td>
                                    <td><?= $booking['duration_months'] ?> months</td>
                                    <td>$<?= number_format($booking['price'], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($booking['start_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($booking['end_date'])) ?></td>
                                    <td>
                                        <?php if($booking['trainer_name']): ?>
                                            <?= htmlspecialchars($booking['trainer_name']) ?><br>
                                            <small style="color: var(--text-dark);"><?= htmlspecialchars($booking['specialization']) ?></small>
                                        <?php else: ?>
                                            Not assigned
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($booking['trainer_name']): ?>
                                            <a href="message.php?trainer_id=<?= $booking['trainer_id'] ?>" class="btn btn-outline-primary" style="padding: 5px 10px; font-size: 12px;">
                                                <i class="bi bi-chat"></i> Message
                                            </a>
                                        <?php endif; ?>
                                        <a href="booking_details.php?id=<?= $booking['booking_id'] ?>" class="btn btn-outline-primary" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="bi bi-eye"></i> Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Bookings -->
        <div class="card mb-4">
            <div class="card-header bg-secondary">
                <h5 class="mb-0">Booking History</h5>
            </div>
            <div class="card-body">
                <?php if(empty($pastBookings)): ?>
                    <div class="alert alert-info">No past bookings found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>End Date</th>
                                    <th>Trainer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pastBookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['package_name']) ?></td>
                                    <td><?= $booking['duration_months'] ?> months</td>
                                    <td>
                                        <span class="badge bg-<?= $booking['status'] == 'completed' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($booking['end_date'])) ?></td>
                                    <td><?= $booking['trainer_name'] ? htmlspecialchars($booking['trainer_name']) : 'Not assigned' ?></td>
                                    <td>
                                        <a href="booking_details.php?id=<?= $booking['booking_id'] ?>" class="btn btn-outline-primary" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <?php if($booking['status'] == 'completed'): ?>
                                            <a href="book_package.php?package_id=<?= $booking['package_id'] ?>" class="btn" style="padding: 5px 10px; font-size: 12px;">
                                                <i class="bi bi-arrow-repeat"></i> Renew
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="booking_history.php" class="btn btn-outline-primary">View Full History</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assigned Trainer Section -->
        <?php if($assignedTrainer): ?>
        <div class="card mb-4">
            <div class="card-header bg-info">
                <h5 class="mb-0">My Trainer</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <img src="../assets/images/trainers/<?= htmlspecialchars($assignedTrainer['image'] ?? 'default.jpg') ?>" 
                             class="trainer-img mb-3" 
                             alt="<?= htmlspecialchars($assignedTrainer['full_name']) ?>">
                        <h5><?= htmlspecialchars($assignedTrainer['full_name']) ?></h5>
                        <p style="color: var(--text-dark);"><?= htmlspecialchars($assignedTrainer['specialization']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>About</h6>
                        <p><?= htmlspecialchars($assignedTrainer['bio']) ?></p>
                        <h6 style="margin-top: 20px;">Certification</h6>
                        <p><?= htmlspecialchars($assignedTrainer['certification']) ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Quick Actions</h6>
                        <div style="display: grid; gap: 10px;">
                            <a href="message.php?trainer_id=<?= $assignedTrainer['trainer_id'] ?>" class="btn">
                                <i class="bi bi-chat"></i> Send Message
                            </a>
                            <a href="schedule_session.php" class="btn btn-outline-primary">
                                <i class="bi bi-calendar-plus"></i> Schedule Session
                            </a>
                            <a href="trainer_advice.php" class="btn btn-outline-primary">
                                <i class="bi bi-lightbulb"></i> Get Advice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>