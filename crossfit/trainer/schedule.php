<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if ($_SESSION['role'] !== 'trainer') {
    header("Location: ../../index.php");
    exit();
}

// Get trainer info
$stmt = $conn->prepare("SELECT * FROM trainers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainer = $stmt->fetch();

// Get assigned members with schedule
$scheduled_sessions = $conn->query("
    SELECT m.full_name, b.start_date, b.end_date, b.status 
    FROM bookings b
    JOIN members m ON b.member_id = m.member_id
    WHERE b.trainer_id = {$trainer['trainer_id']}
    ORDER BY b.start_date
")->fetchAll();
?>

<h2>Your Training Schedule</h2>

<div class="card mt-4">
    <div class="card-body">
        <?php if(empty($scheduled_sessions)): ?>
            <p>You don't have any scheduled sessions yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($scheduled_sessions as $session): ?>
                        <tr>
                            <td><?= $session['full_name'] ?></td>
                            <td><?= date('M j, Y', strtotime($session['start_date'])) ?></td>
                            <td><?= date('M j, Y', strtotime($session['end_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $session['status'] == 'active' ? 'success' : 
                                    ($session['status'] == 'completed' ? 'secondary' : 'warning') 
                                ?>">
                                    <?= ucfirst($session['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>

<?php
require_once '../../includes/footer.php';
?>