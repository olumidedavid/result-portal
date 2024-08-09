<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect('mabest_biotime');

################################################################################
#  HANDLE AJAX REQUESTS                                                        #
################################################################################
    if (isset($_GET['ajax'])) {
        if ($_GET['ajax'] == 'getstudents') {
            $res = $pdo->prepare("SELECT DISTINCT
                                           s.id
                                         , concat(s.firstname, ' ', s.lastname) as sname
                                    FROM class cl
                                         JOIN level l ON cl.levelid = l.id
                                         JOIN course c ON l.id = c.levelid
                                         JOIN staff_course sc ON sc.courseid = c.id
                                         JOIN student_class stc ON stc.classid = cl.id
                                         JOIN  student s ON stc.studentid = s.id
                                    WHERE stc.semesterid = ?
                                          AND cl.id = ?
                                          AND sc.staffid = ?
                                    ORDER BY s.id
                                    ");
            $res->execute([ $_GET['semester'], $_GET['class'], $_GET['staff'] ]);
            $result = $res->fetchAll();
            exit (json_encode(array_column($result, 'sname', 'id')));
        }
        
        elseif ($_GET['ajax'] == 'getscores') {
            $res = $pdo->prepare("SELECT 
                                       a.type
                                     , a.id as assid
                                     , a.assessname
                                     , sc.id as stcid
                                     , e.grade
                                  FROM assessment a
                                     CROSS JOIN student_class sc ON sc.classid = ?
                                                                AND sc.semesterid = ?
                                                                AND sc.studentid = ?
                                     LEFT JOIN eot_assessment e ON e.studentclassid = sc.id
                                                               AND e.assessmentid = a.id
                                     ORDER by type, assid
                                    ");
            $res->execute([ $_GET['class'], $_GET['semester'], $_GET['student'] ]);
            $data = $res->fetchAll(PDO::FETCH_GROUP);
            $out = "<tr class='w3-border-bottom'><th>Type</th><th>Domain</th><th>Grade</th><tr>\n";
            
            foreach ($data as $type => $ass) {
                $out .= "<tr><th colspan='3'>$type</th></tr>\n";
                foreach ($ass as $rec) {
                    $stcid = $rec['stcid'];
                    $out .= "<tr>
                                <td>&nbsp;</td>
                                <td>{$rec['assessname']}</td>
                                <td><input type='text' class='score' name='grade[{$rec['stcid']}][{$rec['assid']}]' value='{$rec['grade']}'></td></tr>\n;
                             </tr>    
                            ";
                }
            }
            
                       
            $comments = getEOTComments($pdo, $_GET['student'], $_GET['semester']);
            $comm = "<b class='w3-small'>Class Teacher</b>
                    <textarea class='w3-input w3-border score' name='comment[$stcid][T]'> {$comments['teacher']}</textarea>
                    <b class='w3-small'>Head of School</b>
                    <textarea class='w3-input w3-border score' name='comment[$stcid][H]'> {$comments['head']}</textarea>
                    ";
                    
            $comm .= "<div 'class='w3-panel w3-padding w3-margin w3-center'>
                         <br>
                         <button class='w3-button w3-green w3-right'>Save</button>&nbsp;
                         <a href=#' class='w3-button w3-grey' id='cancelbtn'>Cancel</a>&nbsp;
                      </div>\n";

            
            
            $results = [ 'ass' => $out, 'comm' => $comm];
            exit(json_encode($results));
        }
        
        else exit('');
    }

################################################################################
#  Handle processing of posted data                                            #
################################################################################
    if ($_SERVER['REQUEST_METHOD']=='POST')  { 
        if (isset($_POST['grade'])) {
            $data = [];
            $placers = [];
            foreach ($_POST['grade'] as $id => $scores) {
                foreach ($scores as $assid => $score) {
                    if ($score == '') $score = null;
                    array_push($data, $id, $assid, $score);
                    $placers[] = "(?,?,?)";
                    
                }
            }
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO eot_assessment (studentclassid, assessmentid, grade)
                                       VALUES " . join(',', $placers)." ON DUPLICATE KEY UPDATE grade = VALUES(grade)
                                       ");
                $stmt->execute($data);
                
                $stmt = $pdo->prepare("INSERT INTO eot_comment (studentclassid, type, comments)
                                       VALUES (?, ?, ?)
                                       ON DUPLICATE KEY UPDATE
                                            comments = VALUES(comments)
                                      ");
                $types = [ 'T'=>'teacher', 'H'=>'head'];
                foreach ($_POST['comment'] as $id => $coms) {
                    foreach ($coms as $type => $comment) {
                        $stmt->execute([ $id, $types[$type], $comment]);
                    }
                }
                $pdo->commit();
            }
            catch(PDOException $e) {
                $pdo->rollBack();
                throw $e;
            }
            header("Refresh: 0");
            exit;
        }
    }

################################################################################
#  NOTE:                                                                       #
#  The teacher entering their result is expected to be logged in and their     #
#  staff id store in the $_SESSION variable. At this point, if they are not    #
#  loggend in they should be transferred to the login page.                    #
#                                                                              #
#  For testing, you can set the staff id below                                        #
################################################################################

   # $staff = $_SESSION['staff_id'] ?? 16;                                       # testing only

  $staff = $_SESSION['staff_id'] ?? 0;                                        # Live 
         
    if (!$staff) {                                                             
        header("Location: login.php");                                         
        exit;                                                                  
    }                                                                          
                                                                                     

################################################################################
#  Get current semester                                                        #
################################################################################
//    if (!$semester) {
//        $res = $pdo->query("SELECT sm.id as sessionid
//                            FROM semester sm
//                            WHERE curdate() BETWEEN sm.date_from AND sm.date_until     
//                            ");
//        $row = $res->fetch();
//    }
//    $semester = $row['semesterid'] ?? $semester;
    
################################################################################
#  Get list of teachers classes for current semester                           #
################################################################################
    $semester = $_GET['semesterid'] ?? -1;
    
    $res = $pdo->prepare("SELECT DISTINCT
                                   cl.id
                                 , cl.classname
                            FROM staff s 
                                 JOIN staff_course sc on sc.staffid = s.id
                                 JOIN course c ON sc.courseid = c.id
                                 JOIN level l ON c.levelid = l.id
                                 JOIN class cl ON cl.levelid = l.id
                                 JOIN student_class stc ON stc.classid = cl.id
                            WHERE stc.semesterid = ?
                                  AND s.id = ?
                            ORDER BY c.levelid, cl.classname
                            ");
    $res->execute([$semester, $staff]);
    $classlist = "";
    foreach ($res as $r) {
        $classlist .= "<li class='w3-border w3-light-gray w3-hover-pale-green class-item' data-id='{$r['id']}'>{$r['classname']}</li>\n";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
    $().ready(function() {
        
        $(".class-item").click( function() {
           if ($("#changed").val() == 1) {
             alert("Save or cancel changes first");
             return;
           }
           $(".class-item").each(function(k,v) {
                if ($(v).hasClass("w3-pale-green")) {
                    $(v).removeClass("w3-pale-green").addClass("w3-light-gray")
                }
            })
            $(this).removeClass("w3-light-gray").addClass("w3-pale-green")
            $("#stud-list").html("")
            $("#class").val( $(this).data("id"))
            $.get(
                "",
                {"ajax":"getstudents", "class":$(this).data("id"), "semester":$("#semesterid").val(), "staff":$("#staff").val()  },
                function(resp) {
                    $.each(resp, function(k,v) {
                        let itm = $("<li>", {"class":"w3-border w3-light-gray w3-hover-pale-green stud-item", "data-id":k, "html":v})
                        $(itm).on('click', function() { studentClick(this) });
                        $("#stud-list").append(itm)
                    })
                },
                "JSON"
            )
        })
    })
    
    function studentClick(obj) 
    {
         if ($("#changed").val() == 1) {
             alert("Save or cancel changes first");
             return;
         }
         $(".stud-item").each(function(k,v) {
             if ($(v).hasClass("w3-pale-green")) {        
                 $(v).removeClass("w3-pale-green").addClass("w3-light-gray")        
             }        
         })        
         $(obj).removeClass("w3-light-gray").addClass("w3-pale-green")        
         $("#assess-table").html("")        
         var lvl = $("#class").val()        
         var staff = $("#staff").val()        
         var student = $(obj).data("id")        
         var sem = $("#semesterid").val()        
         $.get(        
             "",        
             {"ajax":"getscores", "class":lvl, "semester":sem, "student":student},        
             function(resp) {        
                 $("#assess-table").html(resp.ass)
                 $("#comm-div").html(resp.comm)
                 $("#cancelbtn").click( function() {
                     $("#changed").val(0)
                 })
                 $(".score").change(function() {
                     $("#changed").val(1)
                 })        
             },        
             "JSON"
         )        
    }
</script>
<title>Mabest Academy</title>
<style type='text/css'>
/*    input {
        width: 40px;
        text-align: right;
    }                      */
    li {
        cursor: pointer;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding'>
        <h1>Assessment Entry</h1>
    </header>

    <form class='w3-bar w3-light-gray'>
        <label class='w3-bar-item w3-margin-left'>Term</label>
        <select class='w3-bar-item w3-border' name='semesterid' id='semesterid' onchange='this.form.submit()'>
            <?= semesterOptions($pdo, 0, $semester)?>
        </select>
        <input type='hidden' id='staff' value='<?=$staff?>'>
        <input type='hidden' id='class' value=''>
        <input type='hidden' id='changed' value='0'>
    </form>
    
    <div class='w3-row'>
        <div class='w3-col s6 m3'>
            <div class='w3-panel w3-dark-gray'>
                <h3>Class</h3>
            </div>
            <ul class='w3-ul w3-padding'>
                <?= $classlist ?>
            </ul>
        </div>
        <div class='w3-col s6 m3'>
            <div class='w3-panel w3-dark-gray'>
                <h3>Student</h3>
            </div>
            <ul class='w3-ul w3-padding' id='stud-list'>
            </ul>
        </div>
        <div class='w3-col s12 m6'>
            <div class='w3-panel w3-dark-gray'>
                <h3>Student Assessment Grades</h3>
            </div>
            <form method='POST'>
                <table class='w3-table w3-striped' id='assess-table'>
                </table>
                <b>Comments</b><br>
                <div class='w3-container w3-padding' id='comm-div'>
                </div>
                
            </form>
        </div>
    </div>
</body>
</html>
