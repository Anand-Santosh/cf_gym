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

// Get fitness advice
$advice_list = $conn->query("
    SELECT * FROM fitness_advice 
    WHERE trainer_id = {$trainer['trainer_id']}
    AND (member_id IS NULL OR member_id = (SELECT member_id FROM members WHERE user_id = {$_SESSION['user_id']}))
    ORDER BY created_at DESC
")->fetchAll();

// Handle new advice request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question = trim($_POST['question']);
    
    try {
        $member_id = $conn->query("SELECT member_id FROM members WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
        
        $stmt = $conn->prepare("INSERT INTO advice_requests (trainer_id, member_id, question) VALUES (?, ?, ?)");
        $stmt->execute([$trainer['trainer_id'], $member_id, $question]);
        
        $_SESSION['success'] = "Your question has been sent to your trainer!";
        header("Location: trainer_advice.php");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to send question: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Advice - CrossFit Revolution</title>
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
            color: white;
        }

        .bg-info {
            background-color: var(--info) !important;
            color: white;
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

        .form-label {
            color: var(--text-light);
            margin-bottom: 8px;
            display: block;
            font-weight: 600;
        }

        .rounded-circle {
            border-radius: 50% !important;
        }

        .text-muted {
            color: var(--text-dark) !important;
        }

        .list-group-item {
            background-color: var(--darker);
            color: var(--text-light);
            border-color: rgba(255,255,255,0.1);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .list-group-item:last-child {
            margin-bottom: 0;
        }

        .row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .trainer-card {
            text-align: center;
        }

        .trainer-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            display: block;
        }

        @media (max-width: 992px) {
            .row {
                grid-template-columns: 1fr;
            }
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>Fitness Advice</h2>
            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div>
                <div class="card mb-4">
                    <div class="card-header bg-primary">
                        <h5 class="mb-0">Ask Your Trainer</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="question" class="form-label">Your Question</label>
                                <textarea class="form-control" id="question" name="question" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn">Send Question</button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info">
                        <h5 class="mb-0">Advice from <?= htmlspecialchars($trainer['full_name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($advice_list)): ?>
                            <div class="alert alert-info">No advice available yet. Ask your trainer a question!</div>
                        <?php else: ?>
                            <div>
                                <?php foreach($advice_list as $advice): ?>
                                <div class="list-group-item">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <h6 style="margin: 0;"><?= htmlspecialchars($advice['title']) ?></h6>
                                        <small><?= date('M j, Y', strtotime($advice['created_at'])) ?></small>
                                    </div>
                                    <p style="margin-bottom: 5px;"><?= htmlspecialchars($advice['content']) ?></p>
                                    <?php if($advice['member_id']): ?>
                                        <small class="text-muted">Personalized advice for you</small>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="card trainer-card">
                    <div class="card-body">
                        <img src="../assets/images/trainers/<?= htmlspecialchars($trainer['image'] ?? 'default.jpg') ?>" 
                             class="trainer-img" alt="<?= htmlspecialchars($trainer['full_name']) ?>">
                        <h5><?= htmlspecialchars($trainer['full_name']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($trainer['specialization']) ?></p>
                        <p><?= htmlspecialchars($trainer['bio']) ?></p>
                        <a href="message.php?trainer_id=<?= $trainer['trainer_id'] ?>" class="btn btn-outline">
                            Send Message
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>