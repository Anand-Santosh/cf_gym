<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Get all packages
$packages = $conn->query("SELECT * FROM packages ORDER BY price")->fetchAll();
?>

<h2>Manage Packages</h2>

<a href="add_package.php" class="btn btn-primary mb-3">Add New Package</a>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Duration</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($packages as $package): ?>
            <tr>
                <td><?= $package['package_id'] ?></td>
                <td><?= $package['name'] ?></td>
                <td><?= $package['duration_months'] ?> month<?= $package['duration_months'] > 1 ? 's' : '' ?></td>
                <td>$<?= number_format($package['price'], 2) ?></td>
                <td>
                    <a href="view_package.php?id=<?= $package['package_id'] ?>" class="btn btn-sm btn-info">View</a>
                    <a href="edit_package.php?id=<?= $package['package_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="delete_package.php" method="POST" style="display:inline;">
                        <input type="hidden" name="package_id" value="<?= $package['package_id'] ?>">
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