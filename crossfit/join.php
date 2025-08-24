<?php
ob_start();
session_start();
require_once 'config/database.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Validate inputs
        if (empty($full_name)) throw new Exception("Full name is required");
        if (empty($email)) throw new Exception("Email is required");
        if (empty($username)) throw new Exception("Username is required");
        if (empty($password)) throw new Exception("Password is required");
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
        
        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters");
        }
        
        // Check for existing username
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already taken");
        }
        
        // Check for existing email
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already registered");
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, 'member')");
        $stmt->execute([$email, $username, $hashed_password]);
        $user_id = $conn->lastInsertId();
        
        // Insert member
        $stmt = $conn->prepare("INSERT INTO members (user_id, full_name, join_date) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $full_name]);
        
        $conn->commit();
        
        $success = "Registration successful! You can now login.";
        
    } catch (PDOException $e) {
        $conn->rollBack();
        if ($e->errorInfo[1] == 1062) {
            $error = "Username or email already exists";
        } else {
            $error = "Registration error: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join CrossFit</title>
    <style>
        :root {
            --primary: #FF5A1F;
            --dark: #121212;
            --darker: #0A0A0A;
            --text-light: #FFFFFF;
            --danger: #dc3545;
            --success: #28a745;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--darker);
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .join-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-danger {
            background-color: rgba(220,53,69,0.2);
            border: 1px solid rgba(220,53,69,0.3);
            color: var(--danger);
        }
        
        .alert-success {
            background-color: rgba(40,167,69,0.2);
            border: 1px solid rgba(40,167,69,0.3);
            color: var(--success);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 5px;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="join-card">
        <h2>Join CrossFit</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" class="form-control" name="full_name" 
                       value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" name="username" 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required minlength="3">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" name="password" required minlength="8">
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            Already have an account? <a href="login.php" style="color: var(--primary);">Login</a>
        </p>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>