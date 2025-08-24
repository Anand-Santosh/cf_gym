<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Get all trainers
$trainers = $conn->query("
    SELECT t.*, u.email 
    FROM trainers t
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.full_name
")->fetchAll();
?>

<h2>Manage Trainers</h2>

<a href="add_trainer.php" class="btn btn-primary mb-3">Add New Trainer</a>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Specialization</th>
                <th>Experience</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($trainers as $trainer): ?>
            <tr>
                <td><?= $trainer['trainer_id'] ?></td>
                <td><?= $trainer['full_name'] ?></td>
                <td><?= $trainer['email'] ?></td>
                <td><?= $trainer['specialization'] ?></td>
                <td><?= $trainer['experience_years'] ?> years</td>
                <td>
                    <a href="view_trainer.php?id=<?= $trainer['trainer_id'] ?>" class="btn btn-sm btn-info">View</a>
                    <a href="edit_trainer.php?id=<?= $trainer['trainer_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="delete_trainer.php" method="POST" style="display:inline;">
                        <input type="hidden" name="trainer_id" value="<?= $trainer['trainer_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>

<?php
require_once '../../includes/footer.php';
?>