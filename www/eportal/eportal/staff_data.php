<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect();

 #$_SESSION['staff_id'] = ;      ### testing only
                                                                                    
################################################################################
#  Check suoeradmin is logged in                                               #
################################################################################
    if (getAdminType($pdo) != 1) {
        header("Location: login.php");
        exit;
    }

$formhead = '';
$fname = '';
$lname = '';
$oname = '';
$phone = '';
$email = '';
$error = [];
$staffnumber='';
$disabled = '';

################################################################################
#  Process posted form data                                                    #
################################################################################
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $post = array_map('trim', $_POST['stud']);
        $error = [];
        foreach ($post as $k=>$v) {
            if ($v == '' && $k != 'oname') {
                $error[] = 'Fields cannot be blank';
                break;
            }
        }
        $post['login_disabled'] = $_POST['stud']['login_disabled'] ?? 0;
        if ($error) {
            $fname = $post['fname'];
            $lname = $post['lname'];
            $oname = $post['oname'];
            $phone = $post['phone'];
            $email = $post['email'];
            $disabled = $post['login_disabled'];
            $staffnumber = $post['staffnumber'];
        }
        else {
            if ($_POST['staff_id'] == 0) {              // new staff
                $post['password'] = password_hash('mabest', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO staff 
                                       (firstName, lastName, otherName, emailAddress,phoneNo, password, staffnumber,  dateCreated, login_disabled)
                                       VALUES (:fname, :lname, :oname, :email,:phone, :password,:staffnumber,curdate(), :login_disabled )
                                      ");
                $stmt->execute($post);
            }
            else {                                                                         // edited staff
                $post['id'] = $_POST['staff_id'];                                        
                $stmt = $pdo->prepare("UPDATE staff
                                       SET firstname = :fname
                                         , lastname = :lname
                                         , othername = :oname
                                         , staffnumber = :staffnumber
                                         , phoneno = :phone
                                         , emailaddress = :email
                                         , login_disabled = :login_disabled
                                       WHERE id = :id
                                      ");
                $stmt->execute($post);
            }

            header("Location: ?");
            exit;
        }
    }
    else {
        if (isset($_GET['edit'])) {
            if ($_GET['edit'] == 0) {
                $formhead = 'New Staff';
                $staffnumber = staffnumber($pdo);
            }
            else {
                $formhead = 'Edit Staff';
                $res = $pdo->prepare("SELECT firstname
                                           , lastname
                                           , othername
                                           , staffnumber
                                           , phoneno
                                           , emailaddress
                                           , login_disabled
                                      FROM staff
                                      WHERE id = ?
                                     ");
                $res->execute([$_GET['edit']]);
                $staff = $res->fetch();
                if ($staff) {
                    $fname = $staff['firstname'];
                    $lname = $staff['lastname'];
                    $oname = $staff['othername'];
                    $phone = $staff['phoneno'];
                    $email = $staff['emailaddress'];
                    $disabled = $staff['login_disabled'];
                    $staffnumber = $staff['staffnumber'];
                }
            }
        }
    }
################################################################################
#  get list of staff                                                           #
################################################################################
    $res = $pdo->query("SELECT concat(st.firstname, ' ', st.lastname) as name
                             , staffnumber
                             , emailaddress
                             , phoneno
                             , st.id                          
                             , login_disabled                          
                        FROM staff st 
                        ORDER BY name 
                       ");
    $staff = $res->fetchAll();
    $sdata = "";
    $n = 1;
    foreach ($staff as $rec) {
            $disabl = array_pop($rec);
            if ($disabl) {
                $rec['name'] .= "<i class='fa fa-circle w3-right w3-text-red'></i>";
            }
            $id = array_pop($rec);
            $sdata .= "<tr><td class='w3-center'>$n</td>
                            <td>" . join('</td><td>', $rec) . "</td>
                            <td><a href='?edit={$id}'><i class='fa fa-edit'></i></td>
                       </tr>\n";
            ++$n;
    }                  
################################################################################
#  function to generate new unique staff no                                    #
################################################################################

    function staffnumber($pdo)
    {
        $res = $pdo->query("SELECT substr(staffnumber, 3) as sno
                            FROM staff
                            ");
        $result = $res->fetchAll();
        $matrics = array_column($result, 'sno');
        do {
            $sno = substr(uniqid(rand(), true), 0, 3);
        } while (in_array($sno, $matrics));
        return "MAB$sno";
    }    
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
</script>
<title>Mabest Academy</title>
<style type='text/css'>
    .errormsg {
        color: red;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding'>
        <h1>Staff Data</h1>
    </header>
    
<?php if (isset($_GET['edit'])) {  ?>
    <form method='POST' class='w3-container w3-padding w3-margin w3-border' enctype='multipart/form-data' id='form1'>
        <h3><?=$formhead?></h3>
        <p class='errormsg'><?=join('<br>', $error)?></p>
        <input type='hidden' name='staff_id' value='<?= $_GET['edit']??0 ?>' >
        <input type='hidden' name='image' value='<?= $image ?>' >
        <div class='w3-row-padding w3-light-gray'>
            <div class='w3-col w3-padding m4'>First name <input class='w3-input' name='stud[fname]' value='<?=$fname?>'></div>
            <div class='w3-col w3-padding m4'>Last name <input class='w3-input' name='stud[lname]' value='<?=$lname?>'></div>
            <div class='w3-col w3-padding m4'>Other name <input class='w3-input' name='stud[oname]' value='<?=$oname?>'></div>
        </div>
        <div class='w3-row-padding w3-light-gray'>
            <div class='w3-col w3-padding m4'>Email <input type='email' class='w3-input' name='stud[email]' value='<?=$email?>'></div>
            <div class='w3-col w3-padding m4'>Phone <input class='w3-input' name='stud[phone]' value='<?=$phone?>'></div>
            <div class='w3-col w3-padding m4'>Staff No <input class='w3-input' name='stud[staffnumber]' value='<?=$staffnumber?>'></div>
        </div>
        <div class='w3-row-padding w3-light-gray'>
            <div class='w3-col w3-padding m4 <?= ($disabled ? 'w3-text-red' : '' )?>'><label>Log in disabled<br> <input type='checkbox'  name='stud[login_disabled]' value='1'
                  <?= ($disabled ? 'checked' : '')  ?>
                  ></label>
            </div>

            <div class='w3-col m8 w3-padding w3-center rm12'><button class='w3-button w3-indigo'>Save</button></div>
        </div>
    </form>
<?php } ?>
    
    <div class='w3-content w3-responsive'>
        <table class='w3-table'>
            <tr class='w3-border-bottom'>
                <th>&nbsp;</th>
                <th>Name</th>
                <th>Staff No</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            <tr><td colspan='5'>&nbsp;</td>
                <td><a href='?edit=0' class='w3-button w3-green' title='Add new staff'><i class='fa fa-plus'></i></a>            
            <?=$sdata?>
        </table>
    </div>
</body>
</html>