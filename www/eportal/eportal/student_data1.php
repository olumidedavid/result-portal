<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect();

 # $_SESSION['staff_id'] = ;      ### testing only
                                                                                    
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
$dob = '';
$phone = '';
$email = '';
$error = [];
$matricno = '';
$image = '';
$disabled = '';

################################################################################
#  Process posted form data                                                    #
################################################################################
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $post = array_map('trim', $_POST['stud']);
        $error = [];
        $img_types = [ 'image/gif', 'image/jpeg', 'image/png' ];
        foreach ($post as $k=>$v) {
            if ($v == '' && $k != 'oname' && $k != 'email') {
                $error[] = 'Fields cannot be blank';
                break;
            }
        }
        $post['login_disabled'] = $_POST['stud']['login_disabled'] ?? 0;
        $post['image'] = $_POST['image'];
        if ($_FILES['image']['error'] != 4) {
            if ($_FILES['image']['error'] != 0 || !in_array($_FILES['image']['type'], $img_types)) {
                $error[] = 'Problem uploading image file';
            }
            else {
                $ext = pathinfo($_FILES['image']['name'])['extension'];
                $newname = md5($_FILES['image']['name']).time().".$ext";
                if (move_uploaded_file($_FILES['image']['tmp_name'], "images/$newname")) {
                    $post['image'] = $newname;
                }
            }                
        }
        if ($error) {
            $fname = $post['fname'];
            $lname = $post['lname'];
            $oname = $post['oname'];
            $dob = $post['dob'];
            $phone = $post['phone'];
            $email = $post['email'];
            $disabled = $post['login_disabled'];
            $matricno = $post['matricno'];
        }
        else {
            if ($_POST['student_id'] == 0) {              // new student
                $post['password'] = password_hash('codeastro', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO student 
                                       (firstName, lastName, otherName, matricNo, password, DOB, Phone, Email, image, dateCreated, login_disabled)
                                       VALUES (:fname, :lname, :oname, :matricno, :password, :dob, :phone, :email,  :image, curdate(), :login_disabled )
                                      ");
                $stmt->execute($post);
            }
            else {                                                                         // edited student
                $post['id'] = $_POST['student_id'];                                        
                $stmt = $pdo->prepare("UPDATE student
                                       SET firstname = :fname
                                         , lastname = :lname
                                         , othername = :oname
                                         , matricno = :matricno
                                         , dob = :dob
                                         , phone = :phone
                                         , email = :email
                                         , image = :image
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
                $formhead = 'New Student';
                $matricno = matricno($pdo);
            }
            else {
                $formhead = 'Edit Student';
                $res = $pdo->prepare("SELECT firstname
                                           , lastname
                                           , othername
                                           , matricno
                                           , dob
                                           , phone
                                           , email
                                           , image
                                           , login_disabled
                                      FROM student
                                      WHERE id = ?
                                     ");
                $res->execute([$_GET['edit']]);
                $student = $res->fetch();
                if ($student) {
                    $fname = $student['firstname'];
                    $lname = $student['lastname'];
                    $oname = $student['othername'];
                    $dob = $student['dob'];
                    $phone = $student['phone'];
                    $email = $student['email'];
                    $matricno = $student['matricno'];
                    $disabled = $student['login_disabled'];
                    $image = $student['image'];
                }
            }
        }
    }
################################################################################
#  get list of students                                                        #
################################################################################
    $res = $pdo->query("SELECT coalesce(classname, 'Not yet allocated') as class
                             , concat(st.firstname, ' ', st.lastname) as name
                             , matricno
                             , dob
                             , st.id
                             , login_disabled                          
                        FROM student st 
                             LEFT JOIN (
                                 student_class stc 
                                 JOIN semester sm ON stc.semesterid = sm.id
                                                   AND curdate() BETWEEN sm.date_from AND sm.date_until
                                 JOIN class cl ON cl.id = stc.classid
                                 ) ON stc.studentid = st.id
                        ORDER BY cl.id, dob DESC
                       ");
    $students = $res->fetchAll(PDO::FETCH_GROUP);
    $sdata = "";
    foreach ($students as $class => $studs) {
        $sdata .= "<tr class='w3-light-gray'><td colspan='5'>$class</td></tr>";
        $n = 1;
        foreach ($studs as $rec) {
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
    }                  
################################################################################
#  function to generate new unique matricno                                    #
################################################################################

    function matricno($pdo)
    {
        $res = $pdo->query("SELECT substring_index(matricno, '/', -1) as mno
                            FROM student
                            ");
        $result = $res->fetchAll();
        $matrics = array_column($result, 'mno');
        do {
            $mno = substr(uniqid(rand(), true), 0, 5);
        } while (in_array($mno, $matrics));
        return 'MAB/' . date('Y') . "/$mno";
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
        <h1>Student Data</h1>
    </header>
    
<?php if (isset($_GET['edit'])) {  ?>
    <form method='POST' class='w3-container w3-padding w3-margin w3-border' enctype='multipart/form-data' id='form1'>
        <h3><?=$formhead?></h3>
        <p class='errormsg'><?=join('<br>', $error)?></p>
        <input type='hidden' name='student_id' value='<?= $_GET['edit']??0 ?>' >
        <input type='hidden' name='image' value='<?= $image ?>' >
        <div class='w3-row-padding w3-light-gray'>
            <div class='w3-col w3-padding m4'>First name <input class='w3-input' name='stud[fname]' value='<?=$fname?>'></div>
            <div class='w3-col w3-padding m4'>Last name <input class='w3-input' name='stud[lname]' value='<?=$lname?>'></div>
            <div class='w3-col w3-padding m4'>Other name <input class='w3-input' name='stud[oname]' value='<?=$oname?>'></div>
        </div>
        <div class='w3-row-padding w3-light-gray'>
            <div class='w3-col w3-padding m4'>Date of Birth <input type='date' class='w3-input' name='stud[dob]' value='<?=$dob?>'></div>
            <div class='w3-col w3-padding m4'>Email <input type='email' class='w3-input' name='stud[email]' value='<?=$email?>'></div>
            <div class='w3-col w3-padding m4'>Phone <input class='w3-input' name='stud[phone]' value='<?=$phone?>'></div>
        </div>
        <div class='w3-row-padding w3-light-gray'>
            <div class='w3-col w3-padding m4'>Matric No <input class='w3-input' name='stud[matricno]' value='<?=$matricno?>'></div>
            <div class='w3-col w3-padding m4'>Photo <input type='file' class='w3-input' name='image'></div>
            <div class='w3-col w3-padding m4 <?= ($disabled ? 'w3-text-red' : '' )?>'><label>Log in disabled<br> <input type='checkbox'  name='stud[login_disabled]' value='1'
                          <?= ($disabled ? 'checked' : '')  ?>
                          ></label>
            </div>
        </div>
        <div class='w3-row-padding w3-light-gray'>
            <div class='w3-col w3-padding w3-center rm12'><button class='w3-button w3-indigo'>Save</button></div>
        </div>
    </form>
<?php } ?>
    
    <div class='w3-content w3-responsive'>
        <table class='w3-table'>
            <tr class='w3-border-bottom'>
               <th>Class</th>
                <th>Name</th>
              <th>Matric No</th>
               <th>DOB</th>
               <th>Action</th>
            </tr>
            <tr><td colspan='4'>&nbsp;</td>
                <td><a href='?edit=0' class='w3-button w3-green' title='Add new student'><i class='fa fa-plus'></i></a>            
            <?=$sdata?>
        </table>
    </div>
</body>
</html>