<?php
include 'db_inc.php';
include 'functions_ba.php';
session_start();                                                                
$pdo = pdoConnect();


//unset($_SESSION['staff_id'], $_SESSION['student_id']);                              # testing only
//                                                                                
//$_SESSION['staff_id'] = 22;                                                         # testing only
//$_SESSION['student_id'] = 42;                                                       # testing only

################################################################################
#  NOTE:                                                                       #
#  The student viewing their reult is expected to be logged in and their       #
#  student id store in the $_SESSION variable. At this point, if they are not  #
#  loggend in they should be transferred to the login page.                    #
#                                                                              #
#  For testing, simulate login by setting the required SESSION variable above  #
################################################################################


     $clid = $_GET['classid'] ?? -1;
    
     if (isset($_SESSION['student_id'])) {
         $student = $_SESSION['student_id'];
         $staff = $_GET['staffid'] ?? 0;
     }
     elseif (isset($_SESSION['staff_id'])) {
         $staff = $_SESSION['staff_id'];
         $student = $_GET['studentid'] ?? 0;
     }
     else header("Location: login.php");
  

################################################################################
#  Get current session                                                         #
################################################################################
    $semester = $_GET['semesterid'] ?? 0;
    
    $res = $pdo->prepare("SELECT ss.sessionname
                             , ss.id as sessionid
                             , sm.semestername
                             , sm.semestername+0 as termno
                             , sm.id as smid
                        FROM session ss
                             JOIN semester sm ON sm.sessionid = ss.id
                        WHERE sm.id = ?     
                        ");
    $res->execute([$semester]);
    $row = $res->fetch();

//    $sessionname = $row['sessionname'] ?? '';
    $session = $row['sessionid'] ?? 0;
//    $semestername = $row['semestername'] ?? '';
    $termno = $row['termno'] ?? 0;
    
    switch($termno) {
        case 1: $term_headings = "<th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
                                  <th>&nbsp;&nbsp;&nbsp;&nbsp;CA&nbsp;&nbsp;&nbsp;&nbsp;</th>";
                                  break;
        default: $term_headings = "<th>Last<br>&nbsp;Term&nbsp;</th>
                                  <th>&nbsp;&nbsp;&nbsp;CA&nbsp;&nbsp;&nbsp;</th>" ;
    }
    
    $report_title = "Mid-Term Results";
    
    if ($clid <= 0) {
        $res = $pdo->prepare("SELECT classid
                             FROM student_class
                             WHERE semesterid = ?
                                   AND studentid = ?
                            ");
        $res->execute( [ $semester, $student ]);
        $clid = $res->fetchColumn();
    }

################################################################################
#  Get scores and put in array with required output structure                  #
################################################################################

        $studentname = '';
        $studentlevel = '';
        $studentsession = '';
        $studentsemester = '';
        $studentterm = '';
        $passport = '';
        $level = '4';
        $pupil_count = 0;
        $grand_total = 0;
        $subject_count = 0;

    $res = $pdo->prepare("SELECT st.id as stid
                                 , concat_ws(' ', st.lastname, st.firstname, st.othername) as stname
                                 , st.image
                                 , cl.classname
                                 , sc.classid
                                 , l.id as level
                                 , c.subjectid
                                 , s.subjectname
                                 , sn.sessionname
                                 , score*10 as ca
                            FROM result r
                                 JOIN 
                                 (
                                 student_class sc 
                                 JOIN class cl ON sc.classid = cl.id
                                 JOIN level l ON cl.levelid = l.id
                                 JOIN course c ON c.levelid = l.id
                                 JOIN student st ON sc.studentid = st.id
                                 JOIN semester sm ON sc.semesterid = sm.id
                                 JOIN session sn ON sm.sessionid = sn.id
                                 JOIN subject s ON c.subjectid = s.id
                                 ) ON r.studentclassid = sc.id AND exam = 'CA1' and r.courseid = c.id
                            WHERE sn.id = ?
                              AND studentid = ?
                              AND sm.semestername+0 = ?
                              
                            ORDER BY c.levelid, sc.id, c.subjectid, sc.semesterid, exam
                            ");
    $res->execute( [ $session, $student, $termno ] );
    $data = [];
    $subject_count = 0;
    // get data common to all rows from first row
    $r = $res->fetch();
    if ($r) {
        $studentname = $r['stname'];
        $studentlevel = $r['classname'];
        $studentsession = $r['sessionname'];
        $studentterm = "- Term $termno";
        $level = $r['level'];
        $passport = "images/" . $r['image'];
        // then process the rest of the row data in the first and remaining rows
        do {
            if (!isset($data[ $r['subjectid'] ])) {
                $data[ $r['subjectid'] ] = [ 'name' => $r['subjectname'],
                                             'ca' => 0,
                                             'last'  => 0,
                                             'avg' => 0, 
                                             'terms' => 0
                                           ];
            }   
            $data[ $r['subjectid'] ]['ca'] = $r['ca'];
            $subject_count += ($r['ca'] > 0);
        } while ($r = $res->fetch());
//        $subject_count = count($data);
//        if ($subject_count == 0) $subject_count = 1;
        
################################################################################
#  get prev terms' totals
################################################################################ 
        $res = $pdo->prepare("SELECT c.subjectid
                                     , round(sum(score) ) as lastterm
                                     , count(distinct sm.id) as terms
                                FROM result r 
                                     JOIN course c ON r.courseid = c.id
                                     JOIN student_class stc ON r.studentclassid = stc.id
                                     JOIN semester sm ON stc.semesterid = sm.id
                                WHERE sm.sessionid = ?
                                      AND stc.studentid = ?
                                      AND sm.semestername+0 <= ?
                                GROUP BY c.subjectid
                                ");
        $t1 = $termno - 1;
        $res->execute([ $session, $student, $t1 ]);
        foreach ($res as $r) {
            $data[$r['subjectid']]['last'] = $r['lastterm'];
            $data[$r['subjectid']]['terms'] = $r['terms'];
        }
################################################################################
#  get the avg scores for the class                                            #
################################################################################
        $avgs = classAverageScores($pdo, $clid, $session, $termno);
        foreach ($avgs as $s => $av) {
            if (isset($data[$s]))
                $data[$s]['avg'] = $av;
        }   
################################################################################
#  Get pupil count                                                             #
################################################################################
        $res = $pdo->prepare("SELECT COUNT(DISTINCT stc.studentid) AS pupils
                                FROM student_class stc 
                                     JOIN semester sm ON sm.id = stc.semesterid
                                     JOIN result r ON stc.id = r.studentclassid
                                WHERE sm.id = ?
                                  AND stc.classid = ?
                                ");
        $res->execute([ $semester, $clid ]);
        $pupil_count = $res->fetchColumn();    
    }
    else {
//        $studentname = '';
//        $studentlevel = '';
//        $studentsession = '';
//        $studentsemester = '';
//        $studentterm = '';
//        $passport = '';
//        $level = '4';
//        $pupil_count = 0;
//        $grand_total = 0;
//        $subject_count = 1;
//        $clid = 0;
    }
        
################################################################################
#  Loop through the data array to construct the output table rows              #
################################################################################
        $tdata = '';
        $n = 1;
        $grand_total = 0;
//        $subject_count = count($data);
//        if ($subject_count==0) $subject_count = 1;
        foreach ($data as $subid => $subdata) {
            $tdata .= "<tr><td>$n</td>
                           <td>{$subdata['name']}</td>";
            $tdata .= "<td>{$subdata['ca']}</td>";
            $total = round(($subdata['last'] + $subdata['ca'])/($subdata['terms']+1));
            $grand_total += $total;
            if ($total > 0) {
                list($grade, $comment) = getGradeComment($pdo, $total, $level);
            }
            else {
                $grade = '-';
                $comment = '-';
            }
            $clr = GRADE_COLOUR[$grade] ?? '#000';
            $tdata .= "<td>$total</td><td>{$subdata['avg']}</td><td style='color:$clr; font-weight: 600;'>$grade</td><td>$comment</td></tr>\n";
            ++$n;
        }
################################################################################
#  Get list of gradings                                                        #
################################################################################

$grade_list = getGradeList($pdo, $level);

################################################################################
#  Get end of term comments                                                    #
################################################################################

$comments = getEOTComments($pdo, $student, $semester);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Mid-Term Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
//    $().ready( function() {
//        $("#btn-print").click( function() {
//            window.print()
//        }
//    })
</script>
<style type='text/css'>
    #result-tbl {
        width: 100%;
    }
    #result-tbl tr:nth-child(2n) {
        background-color: #eee;
    }
    #result-tbl th {
        text-align: center;
        padding: 4px;
    }
    #result-tbl th:nth-child(2),
    #result-tbl th:nth-child(8) {
        text-align: left;
    }
    #result-tbl td {
        text-align: center;
        padding: 8px 2px;
    }
    #result-tbl td:nth-child(2),
    #result-tbl td:nth-child(7) {
        text-align: left;
    }
    #result-tbl td:nth-child(6) {
        padding-left: 25px;
        text-align: left;
    } 
    #result-tbl td:nth-child(1),
    #result-tbl td:nth-child(2),
    #result-tbl td:nth-child(5) {
        border-right: 1px solid gray;
    }
    
    .assess-tbl th {
        border-top: 1px solid gray;
        border-bottom: 1px solid gray;
    }
    #address {
        font-family: times ;
        font-size: 20px;
    }
    #bgd {
        width : 100vw;
        height: 100vh;
        z-index: -5;
        position: fixed;
        top: 0;
        left: 0;
        background-image: url("logo1.png"); 
        opacity: 0.2;
        background-repeat: no-repeat;
        background-attachment: fixed;
        background-position: center;
        background-size: 600px 600px;
    }
    .summaryhead {
        width: 65%;
        text-align: center;
        border: 1px solid blue;
    }
    @media print {
        .noprint {
            visibility: hidden;
        }
    }
</style>
</head>
<body>
<div class='w3-container'>
    <header class='w3-row'>
        <div class='w3-col m2'><img class='w3-image w3-left w3-padding' src='logo1.png' alt='logo'></div>
        <div class='w3-col m8 w3-padding w3-center' id='address'>
            <strong>MABEST ACADEMY</strong><br> 
            <small><i>Mentoring Future Leaders, Transforming The Society...</i></small><br>                
            Omolayo Estate, Oke-Ijebu Road, Akure, Ondo State, Nigeria            
            <h2><?=$report_title?></h2>
        </div>
        <div class='w3-col m2'><img class='w3-image w3-right w3-padding' src='<?= $passport ?>' width='160' alt='student photo'></div>
    </header>

    <form class='w3-bar w3-light-gray noprint'>
        <label class='w3-bar-item'>Term</label>
        <select class='w3-bar-item w3-border' name='semesterid' onchange='this.form.submit()'>
            <?= semesterOptions($pdo, 0, $semester)?>
        </select>
            <?php  if (isset($_SESSION['staff_id']))  {   ?>
                        <label class='w3-bar-item'>Class</label>
                        <select class='w3-bar-item w3-border' name='classid' id='classid' onchange='this.form.submit()'>
                            <?= classOptions($pdo, $session, $staff, $clid)?>
                        </select>
                        <label class='w3-bar-item'>Student</label>
                        <select class='w3-bar-item w3-border' name='studentid' id='studentid' onchange='this.form.submit()'>
                            <?= studentOptions($pdo, $semester, $clid, $staff, $student)?>
                        </select>
            <?php  }  ?>
        <button class='w3-button w3-bar-item w3-blue-gray w3-right' onclick='window.print()'>Print</button>
    </form>
    
    <div id='bgd'>&nbsp;</div>
    <div class='w3-container w3-padding ' id='wrapper'>
        <div class='w3-row'>
            <div class='w3-col w3-large'>
                <b>Name: <?= $studentname ?></b>
            </div>
        </div>    
        <div class='w3-row'>
            <div class='w3-col w3-third'>
                Class: <?= $studentlevel ?><br>
                Session: <?= $studentsession ?> <?=$studentterm?><br>
            </div>
 <!--           
           <div class='w3-col w3-quarter w3-center'>
                <div class='w3-panel w3-blue summaryhead' >Pupils in class</div>
                <div class='w3-panel w3-xlarge summaryhead'>
                    <?php #$pupil_count ?>
                </div>
            </div>
            
-->
            <div class='w3-col w3-third'>
                <div class='w3-panel w3-blue summaryhead' >Percentage</div>
                <div class='w3-panel w3-xlarge summaryhead'>
                    <?=$subject_count == 0 ? '0%' : round($grand_total/$subject_count, 0).'%'?>
                </div>
            </div>
            
            <div class='w3-col w3-third w3-center'>
                <div class='w3-panel w3-blue summaryhead' >Score</div>
                <div class='w3-panel w3-xlarge summaryhead'>
                    <?=sprintf('%d/%d', $grand_total, $subject_count*100)?>
                </div>
            </div>
        </div>
        <div class='w3-responsive'>
        <table border='0'  id='result-tbl'>
            <tr class='w3-border-bottom w3-dark-gray'>
                <th>&nbsp;</th>
                <th>Subject</th>
                <th>&nbsp;&nbsp;&nbsp;&nbsp;CA&nbsp;&nbsp;&nbsp;&nbsp;</th>
                <th>Total</th>
                <th>Class<br>Avg</th>
                <th>Grade</th>
                <th>Comment</th>
            </tr>
            <?= $tdata ?>
        </table>
        </div>
        <div class='w3-panel w3-padding w3-small'>
             <b>Grades: </b><i><?= $grade_list ?></i>
        </div>
        <div class='w3-row w3-small'>
            <div class='w3-col w3-half'>&nbsp;</div>
            <div class='w3-col w3-half'>
                <h4><b>Comments</b></h4>
                <b class='w3-small'>Teacher</b>
                <div class='w3-padding w3-border' ><?= $comments['teacher'] ?></div >
                <b class='w3-small'>Head</b>
                <div class='w3-padding w3-border' ><?= $comments['head'] ?></div >
            </div>
        </div>
    </div>
</div>

</body>
</html>
