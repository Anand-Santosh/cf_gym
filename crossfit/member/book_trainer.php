<?php
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member info
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Handle trainer booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trainer_id = $_POST['trainer_id'];
    $package_id = $_POST['package_id'];
    $start_date = $_POST['start_date'];
    $session_time = $_POST['session_time'];
    $notes = $_POST['notes'] ?? '';
    
    try {
        // Get package duration
        $stmt = $conn->prepare("SELECT duration_months FROM packages WHERE package_id = ?");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        
        $end_date = date('Y-m-d', strtotime($start_date . " + " . $package['duration_months'] . " months"));
        
        // Create booking
        $stmt = $conn->prepare("INSERT INTO bookings (member_id, trainer_id, package_id, start_date, end_date, session_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$member['member_id'], $trainer_id, $package_id, $start_date, $end_date, $session_time, $notes]);
        
        $_SESSION['success'] = "Trainer booked successfully!";
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $error = "Booking failed: " . $e->getMessage();
    }
}

// Get all trainers
$trainers = $conn->query("SELECT * FROM trainers")->fetchAll();

// Get all packages
$packages = $conn->query("SELECT * FROM packages")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Trainer - CrossFit Revolution</title>
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.4);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.6);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .booking-form {
            background-color: var(--darker);
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-light);
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            color: var(--text-dark);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #f8d7da;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #d4edda;
        }

        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .btn-group {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .booking-form {
                padding: 1.5rem;
            }
        }

        /* Trainer cards preview */
        .trainer-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .trainer-card {
            background-color: var(--darker);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .trainer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .trainer-info {
            padding: 1.5rem;
        }

        .trainer-specialization {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
            display: block;
        }

        .select-trainer-btn {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .select-trainer-btn:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Book a Trainer</h1>
            <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="booking-form">
            <form method="POST" id="bookingForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="trainer_id" class="form-label">Select Trainer</label>
                        <select class="form-select" id="trainer_id" name="trainer_id" required>
                            <option value="">Choose Trainer</option>
                            <?php foreach($trainers as $trainer): ?>
                            <option value="<?= $trainer['trainer_id'] ?>">
                                <?= htmlspecialchars($trainer['full_name']) ?> - <?= htmlspecialchars($trainer['specialization']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="package_id" class="form-label">Select Package</label>
                        <select class="form-select" id="package_id" name="package_id" required>
                            <option value="">Choose Package</option>
                            <?php foreach($packages as $package): ?>
                            <option value="<?= $package['package_id'] ?>" data-price="<?= $package['price'] ?>">
                                <?= htmlspecialchars($package['name']) ?> ($<?= number_format($package['price'], 2) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_time" class="form-label">Preferred Session Time</label>
                        <select class="form-select" id="session_time" name="session_time" required>
                            <option value="">Select Time</option>
                            <option value="morning">Morning (6AM - 12PM)</option>
                            <option value="afternoon">Afternoon (12PM - 5PM)</option>
                            <option value="evening">Evening (5PM - 10PM)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes" class="form-label">Special Requests (Optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any specific goals or focus areas?"></textarea>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn">Book Trainer</button>
                    <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>

        <h2>Available Trainers</h2>
        <div class="trainer-preview">
            <?php foreach($trainers as $trainer): ?>
            <div class="trainer-card">
                <div class="trainer-info">
                    <h3><?= htmlspecialchars($trainer['full_name']) ?></h3>
                    <span class="trainer-specialization"><?= htmlspecialchars($trainer['specialization']) ?></span>
                    <p><?= htmlspecialchars($trainer['bio']) ?></p>
                    <button type="button" class="select-trainer-btn" data-trainer-id="<?= $trainer['trainer_id'] ?>">
                        Select This Trainer
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Function to set the selected trainer
        document.querySelectorAll('.select-trainer-btn').forEach(button => {
            button.addEventListener('click', function() {
                const trainerId = this.getAttribute('data-trainer-id');
                document.getElementById('trainer_id').value = trainerId;
                
                // Scroll to form
                document.getElementById('bookingForm').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add some form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (startDate < today) {
                e.preventDefault();
                alert('Please select a future date for your training start.');
            }
        });
    </script>
</body>
</html>