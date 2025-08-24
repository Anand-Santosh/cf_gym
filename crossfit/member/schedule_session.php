<?php
ob_start();
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get assigned trainer
$trainer = $conn->query("
    SELECT t.* FROM trainers t
    JOIN bookings b ON t.trainer_id = b.trainer_id
    WHERE b.member_id = (SELECT member_id FROM members WHERE user_id = {$_SESSION['user_id']})
    AND b.status = 'active'
    LIMIT 1
")->fetch();

if (!$trainer) {
    header("Location: dashboard.php");
    exit();
}

// Get available sessions
$available_sessions = $conn->query("
    SELECT * FROM trainer_availability 
    WHERE trainer_id = {$trainer['trainer_id']}
    AND start_time > NOW()
    AND booked = 0
    ORDER BY start_time
")->fetchAll();

// Handle session booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_id = $_POST['session_id'];
    $notes = trim($_POST['notes']);
    
    try {
        $conn->beginTransaction();
        
        // Book the session
        $stmt = $conn->prepare("UPDATE trainer_availability SET booked = 1 WHERE id = ?");
        $stmt->execute([$session_id]);
        
        // Create session record
        $member_id = $conn->query("SELECT member_id FROM members WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
        $stmt = $conn->prepare("INSERT INTO training_sessions (trainer_id, member_id, session_time, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$trainer['trainer_id'], $member_id, $session['start_time'], $notes]);
        
        $conn->commit();
        
        $_SESSION['success'] = "Session booked successfully!";
        header("Location: schedule_session.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Booking failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Training Session - CrossFit Revolution</title>
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
            --info: #17a2b8;
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

        .container {
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
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .bg-info {
            background-color: var(--info) !important;
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

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-sm {
            padding: 5px 15px;
            font-size: 12px;
        }

        .btn-outline-danger {
            background-color: transparent;
            border: 2px solid var(--danger);
            color: var(--danger);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger);
            color: white;
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

        .alert-info {
            background-color: rgba(23, 162, 184, 0.2);
            border: 1px solid rgba(23, 162, 184, 0.3);
            color: #17a2b8;
        }

        .table {
            width: 100%;
            color: var(--text-light);
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .table th {
            text-align: left;
            font-weight: 600;
            color: var(--primary);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }

        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .form-control:focus {
            background-color: var(--darker);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 90, 31, 0.25);
            outline: none;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: var(--dark);
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>Schedule Training Session</h2>
            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-primary">
                <h5 class="mb-0">Available Sessions with <?= htmlspecialchars($trainer['full_name']) ?></h5>
            </div>
            <div class="card-body">
                <?php if(empty($available_sessions)): ?>
                    <div class="alert alert-info">No available sessions at this time. Please check back later.</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($available_sessions as $session): ?>
                            <tr>
                                <td><?= date('M j, Y g:i a', strtotime($session['start_time'])) ?></td>
                                <td>60 minutes</td>
                                <td>
                                    <button class="btn btn-sm" onclick="openModal('modal<?= $session['id'] ?>')">
                                        Book Session
                                    </button>
                                    
                                    <!-- Booking Modal -->
                                    <div class="modal" id="modal<?= $session['id'] ?>">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Booking</h5>
                                                <button type="button" onclick="closeModal('modal<?= $session['id'] ?>')">&times;</button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <p>You're booking a session with <?= htmlspecialchars($trainer['full_name']) ?> on <?= date('M j, Y g:i a', strtotime($session['start_time'])) ?>.</p>
                                                    
                                                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Session Notes (Optional)</label>
                                                        <textarea class="form-control" name="notes" rows="3" placeholder="Any specific goals or focus areas for this session?"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline" onclick="closeModal('modal<?= $session['id'] ?>')">Cancel</button>
                                                    <button type="submit" class="btn">Confirm Booking</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info">
                <h5 class="mb-0">Your Upcoming Sessions</h5>
            </div>
            <div class="card-body">
                <?php 
                $upcoming_sessions = $conn->query("
                    SELECT s.*, t.full_name as trainer_name 
                    FROM training_sessions s
                    JOIN trainers t ON s.trainer_id = t.trainer_id
                    WHERE s.member_id = (SELECT member_id FROM members WHERE user_id = {$_SESSION['user_id']})
                    AND s.session_time > NOW()
                    ORDER BY s.session_time
                ")->fetchAll();
                ?>
                
                <?php if(empty($upcoming_sessions)): ?>
                    <div class="alert alert-info">No upcoming sessions scheduled.</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Trainer</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($upcoming_sessions as $session): ?>
                            <tr>
                                <td><?= date('M j, Y g:i a', strtotime($session['session_time'])) ?></td>
                                <td><?= htmlspecialchars($session['trainer_name']) ?></td>
                                <td><?= htmlspecialchars($session['notes']) ?></td>
                                <td>
                                    <form action="cancel_session.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this session?')">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>