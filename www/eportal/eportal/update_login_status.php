<?php
session_start();
include 'db_inc.php';
$pdo = pdoConnect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentId = $_POST['student_id'];
    $loginDisabled = $_POST['login_disabled'];

    // Update the login status in the student table
    $stmt = $pdo->prepare("UPDATE student SET login_disabled = :login_disabled WHERE id = :student_id");
    $stmt->execute(['login_disabled' => $loginDisabled, 'student_id' => $studentId]);

    // You can return a response if needed
    echo json_encode(['success' => true]);
} else {
    // Invalid request method
    echo json_encode(['error' => 'Invalid request method']);
}
?>
