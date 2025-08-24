<?php
ob_start();
session_start();
require_once '../config/database.php';

// Verify member access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member information with username
$stmt = $conn->prepare("SELECT m.*, u.email, u.username FROM members m JOIN users u ON m.user_id = u.user_id WHERE m.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Initialize variables
$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $username = trim($_POST['username']);

    try {
        // Validate inputs
        if (empty($full_name) || empty($username)) {
            throw new Exception("Full name and username are required");
        }

        // Check for duplicate username (excluding current user)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already exists. Please choose a different one.");
        }

        // Begin transaction for multiple updates
        $conn->beginTransaction();

        // Update members table
        $stmt = $conn->prepare("UPDATE members SET full_name = ?, phone = ?, gender = ?, address = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $phone, $gender, $address, $_SESSION['user_id']]);

        // Update users table (username)
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
        $stmt->execute([$username, $_SESSION['user_id']]);

        $conn->commit();
        
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        if ($e->errorInfo[1] == 1062) {
            $error = "The username you entered is already in use. Please choose a different one.";
        } else {
            $error = "Database error: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

$pageTitle = "My Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
            --danger: #dc3545;
            --success: #28a745;
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

        .profile-container {
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
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-header.bg-danger {
            background-color: var(--danger) !important;
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

        .btn-outline-danger {
            background-color: transparent;
            border: 2px solid var(--danger);
            color: var(--danger);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger);
            color: white;
        }

        .form-control, .form-select {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-control:focus, .form-select:focus {
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

        textarea.form-control {
            min-height: 100px;
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

        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary);
        }

        .text-muted {
            color: var(--text-dark) !important;
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 15px;
            }
            
            .profile-img {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="../assets/images/members/<?= htmlspecialchars($member['image'] ?? 'default.jpg') ?>" 
                             alt="Profile" class="profile-img">
                        <h5 class="my-3"><?= htmlspecialchars($member['full_name']) ?></h5>
                        <p class="text-muted mb-1">Member since <?= date('F j, Y', strtotime($member['join_date'])) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php elseif(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($member['email']) ?>" readonly>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Join Date</label>
                                    <input type="text" class="form-control" value="<?= date('F j, Y', strtotime($member['join_date'])) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($member['username']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($member['full_name']) ?>" required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($member['phone']) ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select</option>
                                        <option value="male" <?= $member['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= $member['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
                                        <option value="other" <?= $member['gender'] == 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($member['address']) ?></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-danger">
                        <h5 class="mb-0">Account Settings</h5>
                    </div>
                    <div class="card-body">
                        <a href="change_password.php" class="btn btn-outline-danger">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>