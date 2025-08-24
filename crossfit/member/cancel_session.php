<?php
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_id = $_POST['session_id'];
    
    try {
        $conn->beginTransaction();
        
        // Verify the session belongs to this member
        $stmt = $conn->prepare("
            SELECT s.id FROM training_sessions s
            JOIN members m ON s.member_id = m.member_id
            WHERE s.id = ? AND m.user_id = ?
        ");
        $stmt->execute([$session_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Session not found");
        }
        
        // Get session time for availability update
        $session = $conn->query("SELECT trainer_id, session_time FROM training_sessions WHERE id = $session_id")->fetch();
        
        // Delete the session
        $stmt = $conn->prepare("DELETE FROM training_sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        
        // Mark the time slot as available again
        $stmt = $conn->prepare("
            UPDATE trainer_availability 
            SET booked = 0 
            WHERE trainer_id = ? 
            AND start_time = ?
        ");
        $stmt->execute([$session['trainer_id'], $session['session_time']]);
        
        $conn->commit();
        
        $_SESSION['success'] = "Session cancelled successfully";
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Failed to cancel session: " . $e->getMessage();
    }
}

header("Location: schedule_session.php");
exit();