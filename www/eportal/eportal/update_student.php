<?php
include 'db_inc.php';
session_start();

if (!isset($_SESSION['student_id'])) {
    echo '<div class="w3-panel w3-red">You are not logged in.</div>';
    exit;
}

$pdo = pdoConnect();
$student_id = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dob = $_POST['dob'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($dob) || empty($phone) || empty($email)) {
        echo '<div class="w3-panel w3-red">All fields are required.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE student SET DOB = ?, Phone = ?, Email = ? WHERE id = ?");
            $stmt->execute([$dob, $phone, $email, $student_id]);
            echo '<div class="w3-panel w3-green">Details updated successfully.</div>';
        } catch (PDOException $e) {
            echo '<div class="w3-panel w3-red">Error: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
