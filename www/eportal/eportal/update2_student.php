<?php
include 'db_inc.php';

// Get form data
$matricNo = $_POST['matricNo'];
$dob = $_POST['dob'];
$phone = $_POST['phone'];
$email = $_POST['email'];

// Validate form data (basic validation)
if (empty($matricNo) || empty($dob) || empty($phone) || empty($email)) {
    die('All fields are required.');
}

// Update student details
try {
    $pdo = pdoConnect();
    $sql = "UPDATE student 
            SET DOB = :dob, Phone = :phone, Email = :email 
            WHERE matricNo = :matricNo";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':dob', $dob);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':matricNo', $matricNo);
    $stmt->execute();

    if ($stmt->rowCount()) {
        echo "Student details updated successfully.";
    } else {
        echo "No student found with the provided Matric Number.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
