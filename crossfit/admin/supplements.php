<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Get all supplements
$supplements = $conn->query("SELECT * FROM supplements ORDER BY name")->fetchAll();
?>

<h2>Manage Supplements</h2>

<a href="add_supplement.php" class="btn btn-primary mb-3">Add New Supplement</a>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($supplements as $supplement): ?>
            <tr>
                <td><?= $supplement['supplement_id'] ?></td>
                <td><?= $supplement['name'] ?></td>
                <td>$<?= number_format($supplement['price'], 2) ?></td>
                <td><?= $supplement['stock'] ?></td>
                <td>
                    <a href="view_supplement.php?id=<?= $supplement['supplement_id'] ?>" class="btn btn-sm btn-info">View</a>
                    <a href="edit_supplement.php?id=<?= $supplement['supplement_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="delete_supplement.php" method="POST" style="display:inline;">
                        <input type="hidden" name="supplement_id" value="<?= $supplement['supplement_id'] ?>">
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