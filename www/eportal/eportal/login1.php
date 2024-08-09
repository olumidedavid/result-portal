<?php
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect('mabest_biotime');
session_start();

$user = $_POST['user'] ?? '';
$password = $_POST['password'] ?? '';
$npassword = $_POST['npassword'] ?? '';
$cpassword = $_POST['cpassword'] ?? '';
$error = '';

unset($_SESSION['staff_id'], $_SESSION['student_id']);

if ($_SERVER['REQUEST_METHOD']=='POST') {
    
    $res = $pdo->prepare("SELECT 'staff' as logintype
                                 , id
                                 , password
                                 , login_disabled
                            FROM staff 
                            WHERE staffnumber = ?
                            UNION ALL
                            SELECT 'student' as logintype
                                 , id
                                 , password
                                 , login_disabled
                            FROM student 
                            WHERE matricNo = ?
                            ");
    $res->execute([ $user, $user ]);
    $row = $res->fetch();
    
    if ($row) {
        if ($row['login_disabled']) {
            $error = 'Login not allowed';
        }
        elseif (!password_verify($password, $row['password'])) {
            $error = 'Invalid login';
        }
    }
    else $error = 'Invalid login';
    
    if (trim($npassword) != '') {
        if ($npassword != $cpassword) {
            $error = 'Invalid password confirmation';
        }
    }
    if (!$error) {
        switch ($row['logintype']) {
            case 'student':
                    $_SESSION['student_id'] = $row['id'];
                    if ($npassword) {
                        $stmt = $pdo->prepare("UPDATE student
                                               SET password = ?
                                               WHERE id = ?
                                               ");
                        $hash = password_hash($npassword, PASSWORD_DEFAULT);
                        $stmt->execute( [ $hash, $_SESSION['student_id'] ] );
                    }
                    header("Location: student_index.php");
                    exit;
            case 'staff':
                    $_SESSION['staff_id'] = $row['id'];
                    if ($npassword) {
                        $stmt = $pdo->prepare("UPDATE staff
                                               SET password = ?
                                               WHERE id = ?
                                               ");
                        $hash = password_hash($npassword, PASSWORD_DEFAULT);
                        $stmt->execute( [ $hash, $_SESSION['staff_id'] ] );
                    }
                    if (getAdminType($pdo)==1) {
                        header("Location: admin_index.php");
                        exit;    
                    } else {
                        header("Location: teacher_index.php");
                        exit;
                    }
                    break;        
        }
    }
}

?>
<html>
<head>
<title>MABEST ACADEMY</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
    $().ready( function() {
        $("#pwdchange").click( function() {
            $("#newpwd").show();
        })
    })
</script>
<style type='text/css'>
    #newpwd {
        display: none;
    }
    #pwdchange {
        cursor: pointer;
    }
    #pwdchange:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
 
    <header class='w3-row'>
        <div class='w3-col s3 m1'><img class='w3-image w3-left w3-padding' src='logo1.png' alt='logo'></div>
        <div class='w3-col s9 m11 w3-padding'>
            <h2>Staff and Student Sign In</h2>
        </div>
    </header>
    
    <div class='w3-border w3-round-large w3-content w3-light-gray w3-padding'>
        <form method='post'>
            <div class='w3-panel w3-center w3-dark-gray'>
                <h3>Sign In</h3>
            </div>
            <label for='user'><b>Registration No or Staff No</b></label>
            <input type='text' class='w3-input w3-border' name='user' id='user' value='<?=$user?>'>
            <br>
            <label for='password'><b>Password</b></label>
            <input type='password' class='w3-input w3-border' name='password' id='password' value='<?=$password?>'>
            <div class='w3-container w3-margin-top w3-padding w3-dark-gray' id='newpwd'>
                <label for='npassword'><b>New Password</b></label>
                <input type='password' class='w3-input w3-border' name='npassword' id='npassword' value='<?=$npassword?>'>
                <br>
                <label for='cpassword'><b>Confirm Password</b></label>
                <input type='password' class='w3-input w3-border' name='cpassword' id='cpassword' value='<?=$cpassword?>'>
            </div>
            <div class='w3-panel'>
                <input type='submit' class='w3-button w3-indigo w3-right' value='Sign in'>
                <br>
                <span id='pwdchange' class='w3-text-blue'>Change password</span>
            </div>
        </form>
    <div class='w3-panel w3-red'>
        <?=$error?>
    </div> 
    </div> 
</body>
</html>

