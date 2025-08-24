<?php
ob_start();
session_start();
require_once '../config/database.php';

// Verify member access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member info
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Handle package booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $package_id = $_POST['package_id'];
    $start_date = $_POST['start_date'];
    
    try {
        // Get package duration
        $stmt = $conn->prepare("SELECT duration_months FROM packages WHERE package_id = ?");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        
        $end_date = date('Y-m-d', strtotime($start_date . " + " . $package['duration_months'] . " months"));
        
        // Create booking
        $stmt = $conn->prepare("INSERT INTO bookings (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$member['member_id'], $package_id, $start_date, $end_date]);
        
        $_SESSION['success'] = "Package booked successfully!";
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $error = "Booking failed: " . $e->getMessage();
    }
}

// Get the specific package if package_id is set
$package = null;
if (isset($_GET['package_id'])) {
    $package_id = $_GET['package_id'];
    $stmt = $conn->prepare("SELECT * FROM packages WHERE package_id = ?");
    $stmt->execute([$package_id]);
    $package = $stmt->fetch();
}

// Get all packages if no specific package is selected
if (!$package) {
    $packages = $conn->query("SELECT * FROM packages")->fetchAll();
}

$pageTitle = "Book a Package";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
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

        .booking-container {
            padding: 30px;
            max-width: 1200px;
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

        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-control:focus {
            background-color: var(--darker);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 90, 31, 0.25);
            outline: none;
        }

        .form-label {
            color: var(--text-light);
            margin-bottom: 8px;
            display: block;
            font-weight: 600;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
        }

        .list-group-item {
            background-color: var(--darker);
            color: var(--text-light);
            border-color: rgba(255,255,255,0.1);
        }

        @media (max-width: 768px) {
            .booking-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Book a Package</h2>
            <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if($package): ?>
            <form method="POST">
                <input type="hidden" name="package_id" value="<?= $package['package_id'] ?>">
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h4><?= htmlspecialchars($package['name']) ?></h4>
                        <p><?= htmlspecialchars($package['description']) ?></p>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Duration:</strong> <?= $package['duration_months'] ?> month<?= $package['duration_months'] > 1 ? 's' : '' ?></li>
                            <li class="list-group-item"><strong>Price:</strong> $<?= number_format($package['price'], 2) ?></li>
                            <li class="list-group-item"><strong>Features:</strong> <?= htmlspecialchars($package['features']) ?></li>
                        </ul>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn">Confirm Booking</button>
                    <a href="book_package.php" class="btn btn-outline-primary">Back to Packages</a>
                </div>
            </form>
        <?php else: ?>
            <div class="row">
                <?php foreach($packages as $p): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($p['description']) ?></p>
                            <ul class="list-group">
                                <li class="list-group-item"><strong>Duration:</strong> <?= $p['duration_months'] ?> month<?= $p['duration_months'] > 1 ? 's' : '' ?></li>
                                <li class="list-group-item"><strong>Price:</strong> $<?= number_format($p['price'], 2) ?></li>
                            </ul>
                        </div>
                        <div class="card-footer" style="background-color: var(--dark); border-top: 1px solid rgba(255,255,255,0.1);">
                            <a href="book_package.php?package_id=<?= $p['package_id'] ?>" class="btn w-100">Book Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>