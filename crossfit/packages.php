<?php
require_once 'includes/header.php';

// Get all packages from database
require_once 'config/database.php';
$packages = $conn->query("SELECT * FROM packages")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --primary: #FF5A1F; /* Vibrant orange */
        --primary-dark: #E04A14;
        --dark: #121212;
        --darker: #0A0A0A;
        --light: #F8F9FA;
        --text-dark: #E0E0E0;
        --text-light: #FFFFFF;
    }

    body {
        background-color: var(--dark);
        color: var(--text-dark);
        font-family: 'Montserrat', sans-serif;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: 'Oswald', sans-serif;
        font-weight: 700;
        letter-spacing: 1px;
        color: var(--text-light);
    }

    .package-section {
        padding: 80px 0;
        background-color: var(--darker);
    }

    .package-title {
        position: relative;
        margin-bottom: 50px;
        text-align: center;
    }

    .package-title h2 {
        font-size: 2.5rem;
        text-transform: uppercase;
    }

    .package-title h2:after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -15px;
        width: 80px;
        height: 4px;
        background: var(--primary);
        transform: translateX(-50%);
    }

    .package-card {
        background-color: var(--dark);
        border-radius: 10px;
        overflow: hidden;
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        height: 100%;
    }

    .package-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
    }

    .card-header {
        background-color: var(--primary);
        padding: 20px;
        text-align: center;
        border-bottom: none;
    }

    .card-header h4 {
        font-size: 1.5rem;
        margin: 0;
    }

    .card-body {
        padding: 30px;
    }

    .card-title {
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: var(--primary);
    }

    .text-muted {
        color: var(--text-dark) !important;
        opacity: 0.7;
    }

    .card-text {
        margin-bottom: 20px;
        min-height: 60px;
    }

    .list-group-item {
        background-color: transparent;
        color: var(--text-dark);
        border-color: rgba(255,255,255,0.1);
        padding: 12px 0;
    }

    .card-footer {
        background-color: rgba(0,0,0,0.2);
        border-top: 1px solid rgba(255,255,255,0.1);
        padding: 20px;
        text-align: center;
    }

    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
        padding: 10px 25px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(255, 90, 31, 0.4);
    }

    .btn-secondary {
        background-color: #333;
        border-color: #333;
        padding: 10px 25px;
        cursor: not-allowed;
    }

    /* Removed the popular-badge and popular-card styles since we're not using them */
</style>

<section class="package-section">
    <div class="container">
        <div class="package-title">
            <h2>Our Membership Packages</h2>
        </div>
        
        <div class="row">
            <?php foreach($packages as $package): ?>
            <div class="col-md-4 mb-4">
                <div class="card package-card">
                    <div class="card-header">
                        <h4><?= htmlspecialchars($package['name']) ?></h4>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title">$<?= number_format($package['price'], 2) ?> 
                            <small class="text-muted">/ <?= $package['duration_months'] ?> month<?= $package['duration_months'] > 1 ? 's' : '' ?></small>
                        </h5>
                        <p class="card-text"><?= htmlspecialchars($package['description']) ?></p>
                        
                        <ul class="list-group list-group-flush mb-3">
                            <?php 
                            $features = explode(',', $package['features']);
                            foreach($features as $feature): 
                            ?>
                                <li class="list-group-item"><?= trim(htmlspecialchars($feature)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="card-footer">
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'member'): ?>
                            <a href="member/book_package.php?package_id=<?= $package['package_id'] ?>" class="btn btn-primary">Book Now</a>
                        <?php elseif(isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-secondary" disabled>Login as member to book</button>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary">Login to Book</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>