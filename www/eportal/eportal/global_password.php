<?php
session_start();
require 'db_inc.php';
require 'functions_ba.php';
$pdo = pdoConnect();
                                                                                
                                                                                    
################################################################################
#  Check suoeradmin is logged in                                               #
################################################################################
    if (getAdminType($pdo) != 1) {
        header("Location: login.php");
        exit;
    }
                                                                                    
################################################################################
#  update the passwords                                                        #
################################################################################

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE student
                            SET password = ?
                            WHERE NOT login_disabled
                                  AND leavingdate IS NULL
                          ");
    $stmt->execute([ $hash ]);
    
    header("Location: admin_index.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Mabest Academy</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
</script>
</head>
<body>
    <header class='w3-container w3-padding w3-center'>
        <h1>Global Password Update</h1>
    </header>

    <div class='w3-content w3-border w3-round-large w3-black w3-center'>
        <div class='w3-panel'>
           
                <img src='logo1.png'  alt='caution_logo.jpg'>
              
        </div>
        <div class='w3-panel w3-padding'>
        <form method='POST'>
            <label for='password'>Enter new global password</label><br>
            <input type='password' name='password' id='password' class='w3-inpur'>
            
            <p>Clicking the button will change all students' passwords to the new value.<br>
            Those students whose login-disabled flag is set will be excluded.</p>
            
            <button class='w3-button w3-border w3-border-white w3-round'>Update</button>
        </form>
        </div>
        
    </div>
</body>
</html>
