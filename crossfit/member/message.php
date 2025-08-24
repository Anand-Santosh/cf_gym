<?php
ob_start();
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

$trainer_id = $_GET['trainer_id'] ?? null;

// Get trainer info
$stmt = $conn->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    header("Location: dashboard.php");
    exit();
}

// Get messages
$messages = $conn->query("
    SELECT * FROM messages 
    WHERE (sender_id = {$_SESSION['user_id']} AND receiver_id = {$trainer['user_id']})
    OR (sender_id = {$trainer['user_id']} AND receiver_id = {$_SESSION['user_id']})
    ORDER BY sent_at DESC
")->fetchAll();

// Handle new message
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $trainer['user_id'], $content]);
        
        header("Location: message.php?trainer_id=$trainer_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Trainer - CrossFit Revolution</title>
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

        .card-body {
            padding: 20px;
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

        .rounded-circle {
            border-radius: 50% !important;
        }

        .text-muted {
            color: var(--text-dark) !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }

        .message-container {
            height: 400px;
            overflow-y: auto;
            padding: 15px;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 12px;
            position: relative;
            word-wrap: break-word;
        }

        .sent {
            background-color: var(--primary);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .received {
            background-color: var(--dark);
            border: 1px solid rgba(255,255,255,0.1);
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 0.75rem;
            margin-top: 5px;
            opacity: 0.8;
        }

        .sent .message-time {
            color: rgba(255,255,255,0.7);
        }

        .received .message-time {
            color: var(--text-dark);
        }

        .input-group {
            display: flex;
            width: 100%;
        }

        .form-control {
            flex: 1;
            padding: 12px 15px;
            background-color: var(--dark);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            border-radius: 50px 0 0 50px;
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 90, 31, 0.25);
        }

        .input-group button {
            border-radius: 0 50px 50px 0;
            border-left: none;
        }

        .trainer-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
        }

        .trainer-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .message-bubble {
                max-width: 85%;
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
            <h2>Message Trainer</h2>
            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
        </div>
        
        <div class="card">
            <div class="trainer-info">
                <img src="../assets/images/trainers/<?= htmlspecialchars($trainer['image'] ?? 'default.jpg') ?>" 
                     class="trainer-img" alt="<?= htmlspecialchars($trainer['full_name']) ?>">
                <div>
                    <h5><?= htmlspecialchars($trainer['full_name']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($trainer['specialization']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="message-container">
                <?php if(empty($messages)): ?>
                    <div class="text-center py-5 text-muted">
                        No messages yet. Start the conversation!
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $message): ?>
                        <div class="message-bubble <?= $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                            <div><?= htmlspecialchars($message['content']) ?></div>
                            <div class="message-time">
                                <?= date('M j, g:i a', strtotime($message['sent_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST">
            <div class="input-group">
                <input type="text" class="form-control" name="content" placeholder="Type your message..." required>
                <button class="btn" type="submit">Send</button>
            </div>
        </form>
    </div>

    <script>
        // Auto-scroll to bottom of messages
        const messageContainer = document.querySelector('.message-container');
        messageContainer.scrollTop = messageContainer.scrollHeight;
        
        // Focus on input field when page loads
        document.querySelector('input[name="content"]').focus();
    </script>
</body>
</html>
<?php ob_end_flush(); ?>