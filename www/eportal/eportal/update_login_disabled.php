<?php
session_start();
include 'db_inc.php';

$pdo = pdoConnect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $loginDisabled = $_POST['login_disabled'];

    // Validate and sanitize input (you should do more thorough validation based on your requirements)
    $studentId = filter_var($studentId, FILTER_VALIDATE_INT);
    $loginDisabled = filter_var($loginDisabled, FILTER_VALIDATE_INT);

    if ($studentId !== false && ($loginDisabled === 0 || $loginDisabled === 1)) {
        $stmt = $pdo->prepare("UPDATE student SET login_disabled = :login_disabled WHERE id = :student_id");
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindParam(':login_disabled', $loginDisabled, PDO::PARAM_INT);
        $stmt->execute();

        // Return a success response (you might want to handle errors more gracefully)
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Return an error response if the request is invalid
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>
