<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../../index.php");
    exit();
}

// Get member info
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Get all supplement orders for this member
$orders = $conn->query("
    SELECT o.*, s.name as supplement_name, s.price 
    FROM supplement_orders o
    JOIN supplements s ON o.supplement_id = s.supplement_id
    WHERE o.member_id = {$member['member_id']}
    ORDER BY o.pickup_date DESC
")->fetchAll();
?>

<h2>My Supplement Orders</h2>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Supplement</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Pickup Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $order): ?>
            <tr>
                <td><?= $order['supplement_name'] ?></td>
                <td><?= $order['quantity'] ?></td>
                <td>$<?= number_format($order['price'], 2) ?></td>
                <td>$<?= number_format($order['price'] * $order['quantity'], 2) ?></td>
                <td><?= date('M j, Y', strtotime($order['pickup_date'])) ?></td>
                <td>
                    <span class="badge bg-<?= 
                        $order['status'] == 'pending' ? 'warning' : 
                        ($order['status'] == 'ready' ? 'success' : 'secondary') 
                    ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($orders)): ?>
            <tr>
                <td colspan="6" class="text-center">You haven't placed any orders yet.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="supplements.php" class="btn btn-primary">Order More Supplements</a>
<a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>

<?php
require_once '../../includes/footer.php';
?>