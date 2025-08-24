<?php
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'trainer') {
    header("Location: ../index.php");
    exit();
}

// Get trainer info
$stmt = $conn->prepare("SELECT * FROM trainers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainer = $stmt->fetch();

// Get stats
$assignedMembers = $conn->query("SELECT COUNT(DISTINCT member_id) FROM bookings WHERE trainer_id = {$trainer['trainer_id']}")->fetchColumn();
$upcomingSessions = $conn->query("SELECT COUNT(*) FROM training_sessions WHERE trainer_id = {$trainer['trainer_id']} AND session_time > NOW()")->fetchColumn();

// Check if messages table exists and has the correct structure
try {
    // Get unread message count (simplified query)
    $unreadMessages = $conn->query("
        SELECT COUNT(*) FROM messages 
        WHERE receiver_id = {$_SESSION['user_id']} 
        AND read_status = 0
    ")->fetchColumn();
} catch (PDOException $e) {
    // If messages table doesn't exist or has different structure
    $unreadMessages = 0;
}

// Get recent messages (simplified query)
try {
    $recentMessages = $conn->query("
        SELECT m.*, mem.full_name as sender_name 
        FROM messages m 
        LEFT JOIN members mem ON m.sender_id = mem.user_id 
        WHERE m.receiver_id = {$_SESSION['user_id']} 
        ORDER BY m.created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $recentMessages = [];
}

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message_content = $_POST['message_content'];
    
    try {
        // Simplified insert without role columns
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message_content]);
        
        $messageSuccess = "Message sent successfully!";
        
        // Refresh page to show new message
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $messageError = "Failed to send message: " . $e->getMessage();
    }
}

// Get assigned members for messaging
$assignedMembersList = $conn->query("
    SELECT DISTINCT m.member_id, m.full_name, m.user_id 
    FROM members m 
    JOIN bookings b ON m.member_id = b.member_id 
    WHERE b.trainer_id = {$trainer['trainer_id']}
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - CrossFit Revolution</title>
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
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--darker);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .specialization-badge {
            background-color: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
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

        .btn-home {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-home:hover {
            background-color: var(--primary);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .stat-card.members {
            border-left-color: var(--primary);
        }

        .stat-card.sessions {
            border-left-color: var(--success);
        }

        .stat-card.messages {
            border-left-color: var(--info);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.members .stat-icon {
            color: var(--primary);
        }

        .stat-card.sessions .stat-icon {
            color: var(--success);
        }

        .stat-card.messages .stat-icon {
            color: var(--info);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 1rem;
            display: block;
        }

        .stat-card a {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
        }

        /* Message alert badge */
        .alert-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border-color: var(--primary);
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .action-card h5 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        /* Messaging Section */
        .messaging-section {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .message-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }

        .message-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: background-color 0.3s ease;
        }

        .message-item:hover {
            background-color: rgba(255,255,255,0.05);
        }

        .message-item.unread {
            background-color: rgba(23, 162, 184, 0.1);
            border-left: 3px solid var(--info);
        }

        .message-sender {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .message-content {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .message-time {
            font-size: 0.8rem;
            color: var(--text-dark);
        }

        .message-form {
            background-color: var(--darker);
            padding: 1.5rem;
            border-radius: 10px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-light);
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            color: var(--text-dark);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.3);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #d4edda;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #f8d7da;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div>
                <h1><i class="bi bi-person-badge"></i> Trainer Dashboard</h1>
                <p style="color: var(--text-dark);">Welcome back, <?= htmlspecialchars($trainer['full_name']) ?></p>
                <div class="specialization-badge"><?= htmlspecialchars($trainer['specialization']) ?></div>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="btn btn-home">
                    <i class="bi bi-house"></i> Home
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card members">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <h3><?= $assignedMembers ?></h3>
                <span class="stat-label">Assigned Members</span>
                <a href="members.php"></a>
            </div>
            
            <div class="stat-card sessions">
                <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
                <h3><?= $upcomingSessions ?></h3>
                <span class="stat-label">Upcoming Sessions</span>
                <a href="schedule.php"></a>
            </div>
            
            <div class="stat-card messages">
                <div class="stat-icon"><i class="bi bi-chat-left-text"></i></div>
                <h3><?= $unreadMessages ?></h3>
                <span class="stat-label">New Messages</span>
                <?php if($unreadMessages > 0): ?>
                    <div class="alert-badge"><?= $unreadMessages ?></div>
                <?php endif; ?>
                <a href="messages.php"></a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="actions-grid">
            <div class="action-card">
                <i class="bi bi-calendar-plus action-icon" style="color: var(--primary);"></i>
                <h5>Set Availability</h5>
                <a href="availability.php" class="btn btn-outline">Go</a>
            </div>
            
            <div class="action-card">
                <i class="bi bi-clipboard-data action-icon" style="color: var(--success);"></i>
                <h5>Member Progress</h5>
                <a href="progress.php" class="btn btn-outline">Go</a>
            </div>
            
            <div class="action-card">
                <i class="bi bi-journal-text action-icon" style="color: var(--info);"></i>
                <h5>Training Plans</h5>
                <a href="plans.php" class="btn btn-outline">Go</a>
            </div>
        </div>

        <!-- Messaging Section -->
        <div class="messaging-section">
            <h2 style="margin-bottom: 1.5rem;"><i class="bi bi-chat-dots"></i> Messaging System</h2>
            
            <?php if(isset($messageSuccess)): ?>
                <div class="alert alert-success"><?= $messageSuccess ?></div>
            <?php endif; ?>
            
            <?php if(isset($messageError)): ?>
                <div class="alert alert-danger"><?= $messageError ?></div>
            <?php endif; ?>
            
            <!-- Recent Messages -->
            <h3 style="margin-bottom: 1rem;">Recent Messages</h3>
            <div class="message-list">
                <?php if(count($recentMessages) > 0): ?>
                    <?php foreach($recentMessages as $message): ?>
                        <div class="message-item <?= $message['read_status'] == 0 ? 'unread' : '' ?>">
                            <div class="message-sender">From: <?= htmlspecialchars($message['sender_name']) ?></div>
                            <div class="message-content"><?= htmlspecialchars($message['message_content']) ?></div>
                            <div class="message-time"><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-dark); text-align: center; padding: 2rem;">No messages yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Send Message Form -->
            <h3 style="margin-bottom: 1rem;">Send Message</h3>
            <form method="POST" class="message-form">
                <div class="form-group">
                    <label for="receiver_id" class="form-label">Send to Member</label>
                    <select class="form-select" id="receiver_id" name="receiver_id" required>
                        <option value="">Select a Member</option>
                        <?php foreach($assignedMembersList as $member): ?>
                            <option value="<?= $member['user_id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message_content" class="form-label">Message</label>
                    <textarea class="form-control" id="message_content" name="message_content" rows="4" placeholder="Type your message here..." required></textarea>
                </div>
                
                <button type="submit" name="send_message" class="btn">
                    <i class="bi bi-send"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <script>
        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Add click effect to action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                    const link = this.querySelector('a');
                    if (link) {
                        window.location.href = link.href;
                    }
                }
            });
        });

        // Auto-scroll to message form if there's an error
        <?php if(isset($messageError)): ?>
            document.querySelector('.message-form').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        <?php endif; ?>
    </script>
</body>
</html>