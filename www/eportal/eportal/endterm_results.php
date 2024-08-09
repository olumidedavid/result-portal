<?php
session_start();                                                                
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect();

                                                                                   
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
    
    if ($clid == -1) {
        $res = $pdo->prepare("SELECT classid
                              FROM student_class
                              WHERE studentid = ?
                                AND semesterid = ?
                             ");
        $res->execute([$student, $semester]);
        $clid = $res->fetchColumn();
    }
    
    switch($termno) {
        case 1: $term_headings = "<th>1st<br>Term<br>100</th>
                                  <th></th>
                                  <th></th>";
                                  break;
        case 2: $term_headings = "<th>1st<br>Term<br>&nbsp;</th>
                                  <th>2nd<br>Term<br>100</th>
                                  <th></th>";
                                  break;
        default: $term_headings = "<th>1st<br>Term<br>&nbsp;</th>
                                  <th>2nd<br>Term<br>&nbsp;</th>
                                  <th>3rd<br>Term<br>100</th>";
    }
    
    $report_title = $termno == 3 ? "End of Year Results" : "End of Term Results";
    ################################################################################
    #  Get scores and put in array with required output structure                  #
    ################################################################################
    $res = $pdo->prepare("SELECT st.id as stid
                                 , concat_ws(' ', st.lastname, st.firstname, st.othername) as stname
                                 , st.image
                                 , cl.classname
                                 , sc.classid
                                 , l.id as level
                                 , sn.sessionname
                                 , sm.semestername
                                 , sm.semestername+0 as term
                                 , c.subjectid
                                 , s.subjectname
                                 , exam
                                 , score
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
                                 ) ON r.studentclassid = sc.id AND r.courseid = c.id
                                 
                            WHERE sn.id = ?
                              AND studentid = ?
                              AND sm.semestername+0 <= ?
                              AND cl.id = ?
                            ORDER BY c.levelid, sc.id, c.subjectid, sc.semesterid, exam
                            ");
    $res->execute( [ $session, $student, $termno, $clid ] );
    $data = [];
    // get data common to all rows from first row
    $r = $res->fetch();
    if ($r) {
        $studentname = $r['stname'];
        $studentlevel = $r['classname'];
        $studentsession = $r['sessionname'];
        $studentterm = "- Term $termno";
        $passport = "images/" . $r['image'];                                                                      ### provide image path here
        $level = $r['level'];
        // then process the rest of the row data in the first and remaining rows
        do {
            if (!isset($data[ $r['subjectid'] ])) {
                $data[ $r['subjectid'] ] = [ 'name' => $r['subjectname'],
                                             'exams' => ['CA1'=>'', 'CA2'=>'', 'CA3'=>'', 'Exam'=>''],
                                             'scores'  => [ 1=>0, 0, 0 ],
                                             'avg' => 0
                                           ];
            }   
            if ($r['term'] == $termno && isset($data[$r['subjectid'] ]['exams'][ $r['exam']])) {
                $data[ $r['subjectid'] ]['exams'][ $r['exam'] ] = $r['score'];
            }
            $data[ $r['subjectid'] ]['scores'][$r['term']] += $r['score'];
        } while ($r = $res->fetch());
    // get the avg scores for the class
        $avgs = classAverageScores($pdo, $clid, $session, $termno);
        foreach ($avgs as $s => $av) {
            if (isset($data[$s]))
                $data[$s]['avg'] = round($av,0);
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
            
    ################################################################################
    #  Loop through the data array to construct the output table rows              #
    ################################################################################
        $tdata = '';
        $n = 1;
        $grand_total = 0;
        $subject_count = 0;
        foreach ($data as $subid => $subdata) {
            $tdata .= "<tr><td>$n</td><td>{$subdata['name']}</td>";
            foreach ($subdata['exams'] as $s) {
                $tdata .= "<td>" . ($s=='' ? '&ndash;' : $s) . "</td>";
            }
            foreach ($subdata['scores'] as $t => $s) {
                if ($s==0) $s = '';
                $tdata .= "<td>" . ($t <= $termno ? $s : '') . "</td>";
            }
            $temp = array_filter($subdata['scores']);
            $total = $temp ? round(array_sum($temp)/count($temp)) : 0;
            $grand_total += $total;
            if ($total) {
                list($grade, $comment) = getGradeComment($pdo, $total, $level);
                $subject_count++;
            }
            else {
                $grade = '-';
                $comment = '-';
            }
            $clr = GRADE_COLOUR[$grade] ?? '#000';
            $tdata .= "<td>$total</td><td>{$subdata['avg']}</td><td style='color:$clr; font-weight: 600;'>$grade</td><td>$comment</td></tr>\n";
            ++$n;
        }
    }
    else {
        $studentname = '';
        $studentlevel = '';
        $studentsession = '';
        $studentsemester = '';
        $studentterm = '';
        $passport = '';
        $level = '4';
        $pupil_count = 0;
        $grand_total = 0;
        $subject_count = 1;
//      $clid = 0;
        $tdata = "<tr><td colspan='13'>No results found</td></tr>\n";
    }

    ################################################################################
    #  Get list of gradings                                                        #
    ################################################################################
    $res = $pdo->query("SELECT GROUP_CONCAT( grade, concat('&nbsp;(',comments,')'), '&nbsp;', concat(lomark,'&nbsp;-&nbsp;',himark)
                               ORDER BY id SEPARATOR ', ')
                        FROM examgrade
                        WHERE level_group = ($level > 5)
                        ");
    $grade_list = $res->fetchColumn();

################################################################################
#  Get end of term assessments                                                 #
################################################################################
$res = $pdo->prepare("SELECT    a.type
                              , a.assessname
                              , e.grade
                        FROM student_class stc
                               JOIN eot_assessment e ON e.studentclassid = stc.id
                               JOIN assessment a ON e.assessmentid = a.id 
                               JOIN semester sm ON sm.id = stc.semesterid
                        WHERE stc.studentid = ? 
                            AND sm.id = ? 
                        ");
$res->execute( [ $student, $semester ] );
$ass_data = $res->fetchAll(PDO::FETCH_GROUP);
$afflist = $psychlist = '';

if ($ass_data) {
    
    $afflist = "<table class='w3-table assess-tbl' >
                <tr><th>Domain</th><th>Grade</th></tr>\n";
    foreach ($ass_data['Affective'] as $agrades) {
        $afflist .= "<tr><td>{$agrades['assessname']}</td><td>{$agrades['grade']}</td></tr>\n";
    }
    $afflist .= "</table>\n";
    
    $psychlist = "<table class='w3-table assess-tbl' >
                  <tr><th>Domain</th><th>Grade</th></tr>\n";
    foreach ($ass_data['Psychomotor'] as $pgrades) {
        $psychlist .= "<tr><td>{$pgrades['assessname']}</td><td>{$pgrades['grade']}</td></tr>\n";
    }
    $psychlist .= "</table>\n";
}

################################################################################
#  Get end of term comments                                                 #
################################################################################

$comments = getEOTComments($pdo, $student, $semester);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>End Term Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
    $().ready( function() {
    })
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
    #result-tbl th:nth-child(13) {
        text-align: left;
    }
    #result-tbl td {
        text-align: center;
        padding: 8px 2px;
    }
    #result-tbl td:nth-child(2),
    #result-tbl td:nth-child(13) {
        text-align: left;
    }
    #result-tbl td:nth-child(12) {
        padding-left: 25px;
        text-align: left;
    } 
    #result-tbl td:nth-child(1),
    #result-tbl td:nth-child(6),
    #result-tbl td:nth-child(9) {
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
            <div class='w3-col'>
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
                    <?=$pupil_count?>
                </div>
            </div>
-->            
            <div class='w3-col w3-third'>
                <div class='w3-panel w3-blue summaryhead' >Percentage</div>
                <div class='w3-panel w3-xlarge summaryhead'>
                    <?=round($grand_total/$subject_count, 2).'%'?>
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
                <th>CA 1<br>&nbsp;<br>10</th>
                <th>CA 2<br>&nbsp;<br>10</th>
                <th>CA 3<br>&nbsp;<br>10</th>
                <th>Exam<br>&nbsp;<br>70</th>
                <?=$term_headings?>
            <!--    <th>1st<br>Term<br>&nbsp;</th>
                <th>2nd<br>Term<br>&nbsp;</th>
                <th>3rd<br>Term<br>100</th>        -->
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
        <h4><b>Assessments</b></h4>
        <div class='w3-row-padding w3-small'>
            <div class='w3-col m5 w3-padding'>
                <b>Affective</b><br>
                <?= $afflist ?>
            </div>
            <div class='w3-col m1 w3-padding'>
                &nbsp;
            </div>
            <div class='w3-col m6 w3-padding'>
                <b>Psychomotor</b><br>
                <?= $psychlist ?>
                <br>
                <b>Comments</b><br>
                <div class='w3-container'>
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
