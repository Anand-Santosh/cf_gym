<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Get all members
$members = $conn->query("
    SELECT m.*, u.email, u.created_at 
    FROM members m
    JOIN users u ON m.user_id = u.user_id
    ORDER BY m.join_date DESC
")->fetchAll();
?>

<h2>Manage Members</h2>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Join Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($members as $member): ?>
            <tr>
                <td><?= $member['member_id'] ?></td>
                <td><?= $member['full_name'] ?></td>
                <td><?= $member['email'] ?></td>
                <td><?= $member['phone'] ?></td>
                <td><?= date('M j, Y', strtotime($member['join_date'])) ?></td>
                <td>
                    <a href="view_member.php?id=<?= $member['member_id'] ?>" class="btn btn-sm btn-info">View</a>
                    <a href="edit_member.php?id=<?= $member['member_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="delete_member.php" method="POST" style="display:inline;">
                        <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
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