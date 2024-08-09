<?php
session_start();
require 'db_inc.php';
require 'functions_ba.php';
$pdo = pdoConnect();

 
 $semester = $_GET['semester'] ?? 0;                                                              
                     
################################################################################
#  Check admin is logged in                                                    #
################################################################################
    if (getAdminType($pdo) == 0) {
        header("Location: login.php");
        exit;
    }


    $res = $pdo->prepare("SELECT 
                               cl.classname
                             , sb.subjectname
                             , GROUP_CONCAT(DISTINCT concat(sf.firstname, '&nbsp;', sf.lastname) SEPARATOR ', ') as teachers
                             , count(distinct stc.studentid) as students
                             , count(distinct IF(exam='CA1',r.studentclassid,null)) as CA1
                             , count(distinct IF(exam='CA2',r.studentclassid,null)) as CA2
                             , count(distinct IF(exam='CA3',r.studentclassid,null)) as CA3
                             , count(distinct IF(exam='Exam',r.studentclassid,null)) as Exam
                          FROM staff sf 
                             JOIN staff_course sfc ON sf.id = sfc.staffid
                             JOIN course c ON sfc.courseid = c.id
                             JOIN subject sb ON c.subjectid = sb.id
                             JOIN level l ON c.levelid = l.id
                             JOIN class cl ON l.id = cl.levelid
                             JOIN student_class stc ON cl.id = stc.classid
                                                    AND stc.semesterid = ?
                             JOIN student st ON stc.studentid = st.id 
                                             AND leavingdate IS NULL
                             LEFT JOIN result r ON stc.id = r.studentclassid
                             AND r.courseid = c.id
                          GROUP BY cl.id, subjectname
                          ORDER BY SUBSTRING(classname, 5)+0, cl.id, subjectname
                        ");
//    $res = $pdo->prepare("SELECT 
//                               cl.classname
//                             , sb.subjectname
//                             , concat(sf.firstname, ' ', sf.lastname) as name
//                             , count(distinct stc.studentid) as students
//                             , count(distinct IF(exam='CA1',r.studentclassid,null)) as CA1
//                             , count(distinct IF(exam='CA2',r.studentclassid,null)) as CA2
//                             , count(distinct IF(exam='CA3',r.studentclassid,null)) as CA3
//                             , count(distinct IF(exam='Exam',r.studentclassid,null)) as Exam
//                          FROM staff sf 
//                             JOIN staff_course sfc ON sf.id = sfc.staffid
//                             JOIN course c ON sfc.courseid = c.id
//                             JOIN subject sb ON c.subjectid = sb.id
//                             JOIN level l ON c.levelid = l.id
//                             JOIN class cl ON l.id = cl.levelid
//                             JOIN student_class stc ON cl.id = stc.classid
//                                                    AND stc.semesterid = ?
//                             JOIN student st ON stc.studentid = st.id 
//                                             AND leavingdate IS NULL
//                             LEFT JOIN result r ON stc.id = r.studentclassid
//                             AND r.courseid = c.id
//                          GROUP BY classname, subjectname, name
//                        ");
    $res->execute([$semester])
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<title>Results Status</title>
</head>
<body>
<div class='w3-container w3-responsive'>
    <div class='w3-container w3-blue-gray'>
        <h1>Results Status</h1>
    </div>

    <form class='w3-bar w3-light-gray'>
        <label class='w3-bar-item w3-margin-left'>Term</label>
        <select class='w3-bar-item w3-border' name='semester' onchange='this.form.submit()'>
            <?= semesterOptions($pdo, 0, $semester)?>
        </select>
        <label class='w3-bar-item w3-right w3-yellow w3-center' style='width: 130px'>Some results</label>
        <label class='w3-bar-item w3-right w3-pale-green w3-center' style='width: 130px'>All results</label>
    </form>
    

     <table border='1'  class='w3-table-all'>
         <tr class='w3-black'>
             <th>Class</th>
             <th>Subject</th>
             <th>Teacher</th>
             <th>Students</th>
             <th>CA1</th>
             <th>CA2</th>
             <th>CA3</th>
             <th>Exam</th>
         </tr>    
             <?php
                 $prev = '';
                 $exams = ['CA1', 'CA2', 'CA3', 'Exam'];
                 foreach ($res as $row) {
                     if ($row['classname'] != $prev) {
                         echo "<tr class='w3-gray w3-text-white'><td colspan='8'>{$row['classname']}</td></tr>\n";
                         $prev = $row['classname'];
                     }
                     //echo "<tr><td>&nbsp;</td><td>" . join('</td><td>', array_slice($row,1,3)) . "</td>";
                     echo "<tr><td>&nbsp;</td><td style='width:18%;'>{$row['subjectname']}</td>
                                              <td style='width:50%;'>{$row['teachers']}</td>
                                              <td style='width:7%;'>{$row['students']}</td>";
                     foreach ($exams as $e) {
                         if ($row['students'] == $row[$e]) {
                             $bgd = 'w3-pale-green';
                         }
                         elseif ($row[$e] == 0) {
                             $bgd = 'w3-dark-gray';
                         }
                         else $bgd = 'w3-yellow';
                         echo "<td class='$bgd'>{$row[$e]}</td>" ;
                     }
                 }
                 echo "</tr>\n";
             ?>
     </table>
</div>
</body>
</html>