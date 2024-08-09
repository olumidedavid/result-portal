<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect(); 

################################################################################
#  NOTE:                                                                       #
#  The teacher entering their result is expected to be logged in and their     #
#  staff id store in the $_SESSION variable. At this point, if they are not    #
#  loggend in they are transferred to the login page.                          #
#  Admins can access all classes/students
################################################################################


    $staff = $_SESSION['staff_id'] ?? 0;    
    if (!$staff) {                      
        header("Location: login.php");  
        exit;                           
    }                                   
    $isAdmin = getAdminType($pdo);
                                                                                     
################################################################################
#  HANDLE AJAX REQUESTS                                                        #
################################################################################
    if (isset($_GET['ajax'])) {
        if ($_GET['ajax'] == 'getsubjects') {
            $params = [ $_GET['semester'], $_GET['level']];
            $extra = '';
            if (!$isAdmin) {
                $extra = 'AND sc.staffid = ?';
                $params[] = $_GET['staff'];
            }

            $res = $pdo->prepare("SELECT DISTINCT
                                           s.id
                                         , s.subjectname
                                    FROM course c 
                                         JOIN subject s ON c.subjectid = s.id
                                         JOIN level l ON c.levelid = l.id
                                         JOIN class cl ON cl.levelid = l.id
                                         JOIN student_class stc ON stc.classid = cl.id
                                         JOIN staff_course sc ON sc.courseid = c.id
                                    WHERE stc.semesterid = ?
                                          AND cl.id = ?
                                          $extra
                                    ORDER BY s.id
                                    ");
            $res->execute($params);
            $result = $res->fetchAll();
            exit (json_encode(array_column($result, 'subjectname', 'id')));
        }
        
        elseif ($_GET['ajax'] == 'getscores') {
            $params = [ $_GET['subject'], $_GET['level'], $_GET['semester'] ];
            $extra = '';
            if (!$isAdmin) {
                $extra = 'AND sfc.staffid = ?';
                $params[] = $_GET['staff'];
            }

            $res = $pdo->prepare("SELECT stc.id as stcid
                                         , st.id as stid
                                         , concat(st.firstname, ' ', st.lastname) as stname
                                         , r.exam 
                                         , r.score
                                         , c.id as course
                                    FROM student_class stc
                                         JOIN student st ON stc.studentid = st.id
                                         JOIN class cl ON stc.classid = cl.id
                                         JOIN level l ON cl.levelid = l.id
                                         JOIN course c ON l.id = c.levelid
                                         JOIN staff_course sfc ON sfc.courseid = c.id
                                         LEFT JOIN result r ON r.courseid = c.id
                                                            AND r.studentclassid = stc.id
                                    WHERE c.subjectid = ?
                                      AND stc.classid = ?
                                      AND stc.semesterid = ?
                                      $extra
                                    ORDER BY st.id, exam
                                    ");
            $res->execute($params);
            $out = "<table class='w3-table w3-striped' >
                    <tr class='w3-border-bottom'><th>Name</th><th>CA1<br>10</th><th>CA2<br>10</th><th>CA3<br>10</th><th>Exam<br>70</th><tr>\n";
            $data = [];
            
            foreach ($res as $r) {
                $course = $r['course'];
                if (!isset($data[$r['stcid']])) {
                    $data[$r['stcid']] = [ 'name' => $r['stname'], 
                                           'scores' => [ 'CA1'=>'', 'CA2'=>'', 'CA3'=>'', 'Exam'=>'' ]
                                         ];
                }
                if ($r['exam']) $data[$r['stcid']]['scores'][$r['exam']] = $r['score'];
            }
            foreach ($data as $stcid => $sdata) {
                $name = ucwords(strtolower($sdata['name'])) ;
                $out .= "<tr><td>{$name}</td>";
                $dis = '';
                foreach ($sdata['scores'] as $exam => $scr) {
                    $dis = '';
            #       $dis = $scr ? 'disabled' : '';                                                             // comment out to stop disabling
                    $mx = $exam=='Exam' ? 70:10;                                                               
                    $out .= "<td><input type='number'  name='score[$stcid][$exam]' value='$scr' class='score' $dis min='0' max='$mx'></td>"; 
                }
                $out .= "</tr>\n";
            }
            $out .= "<tr>
                       <td>&nbsp;</td>
                       <td colspan='4'>
                           <span class='w3-button w3-grey' id='cancelbtn'>Cancel</span>
                           <button class='w3-button w3-green'>Save</button>
                       </td>
                       </tr>
                       </table>
                       <input type='hidden' name='course' value='$course'>\n";
            exit($out);
        }
        
        else exit('');
    }


################################################################################
#  Handle processing of posted data                                            #
################################################################################
    if ($_SERVER['REQUEST_METHOD']=='POST')  {
        if (isset($_POST['score'])) {
            $data = [];
            $places = [];
            foreach ($_POST['score'] as $id => $scores) {
                foreach ($scores as $exam => $score) {
                    if ($score !== '') {
                        array_push($data, $id, $_POST['course'], $exam, $score, date('Y-m-d'));
                        $places[] = "(?,?,?,?,?)";
                    }
                }
            }
            $stmt = $pdo->prepare("INSERT INTO result (studentclassid, courseid, exam, score, dateadded)
                                   VALUES " . 
                                   join(',', $places) .
                                   "ON DUPLICATE KEY UPDATE
                                        score = VALUES(score)
                                  ");
            $stmt->execute($data);
            header("Refresh: 0");
            exit;
        }
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
    
    $params = [$semester];
    $extra = '';
    if (!$isAdmin) {
        $extra = 'AND s.id = ?';
        $params[] = $staff;
    }
     
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
                                 $extra 
                            ORDER BY SUBSTRING(classname, 6)+0, classname
                            ");
    $res->execute($params);
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
            $("#subj-list").html("")
            $("#stud-table").html("")
            $("#level").val( $(this).data("id"))
            $.get(
                "",
                {"ajax":"getsubjects", "level":$(this).data("id"), "semester":$("#semesterid").val(), "staff":$("#staff").val()  },
                function(resp) {
                    $.each(resp, function(k,v) {
                        let itm = $("<li>", {"class":"w3-border w3-light-gray w3-hover-pale-green subj-item", "data-id":k, "html":v})
                        $(itm).on('click', function() { subjectClick(this) });
                        $("#subj-list").append(itm)
                    })
                },
                "JSON"
            )
        })
    })
    
    function subjectClick(obj) 
    {
         if ($("#changed").val() == 1) {
             alert("Save or cancel changes first");
             return;
         }
         $(".subj-item").each(function(k,v) {
             if ($(v).hasClass("w3-pale-green")) {        
                 $(v).removeClass("w3-pale-green").addClass("w3-light-gray")        
             }        
         })        
         $(obj).removeClass("w3-light-gray").addClass("w3-pale-green")        
         $("#stud-table").html("")        
         var lvl = $("#level").val()        
         var staff = $("#staff").val()        
         var subj = $(obj).data("id")        
         var sem = $("#semesterid").val()        
         $.get(        
             "",        
             {"ajax":"getscores", "level":lvl, "semester":sem, "subject":subj, "staff":staff},        
             function(resp) {        
                 $("#stud-table").html(resp)
                 $("#cancelbtn").click( function() {
                     $("#changed").val(0)
                 })
                 $(".score").change(function() {
                     $("#changed").val(1)
                 })        
             },        
             "TEXT"
         )        
    }
</script>
<title>Results Entry</title>
<style type='text/css'>
    input {
        width: 40px;
        text-align: right;
    }
    li {
        cursor: pointer;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding'>
        <h1>Results Entry</h1>
    </header>

    <form class='w3-bar w3-light-gray'>
        <label class='w3-bar-item w3-margin-left'>Term</label>
        <select class='w3-bar-item w3-border' name='semesterid' id='semesterid' onchange='this.form.submit()'>
            <?= semesterOptions($pdo, 0, $semester)?>
        </select>
        <input type='hidden' id='staff' value='<?=$staff?>'>
        <input type='hidden' id='level' value=''>
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
                <h3>Subject</h3>
            </div>
            <ul class='w3-ul w3-padding' id='subj-list'>
            </ul>
        </div>
        <div class='w3-col s12 m6'>
            <div class='w3-panel w3-dark-gray'>
                <h3>Student Scores</h3>
            </div>
            <form method='POST' id='stud-table'>
                <table class='w3-table w3-striped' >
                </table>
            </form>
        </div>
    </div>
</body>
</html>
