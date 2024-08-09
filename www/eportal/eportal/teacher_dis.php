<?php
require 'db_inc.php';
$pdo = pdoConnect();


$res = $pdo->query("SELECT cl.classname
                         , sb.subjectname
                         , group_concat(sf.firstname, ' ', sf.lastname separator '<br>') as teach
                    FROM class cl 
                         JOIN level l ON cl.levelid = l.id
                         JOIN course c ON l.id = c.levelid
                         JOIN subject sb ON c.subjectid = sb.id
                         LEFT JOIN staff_course sfc ON c.id = sfc.courseid
                         LEFT JOIN staff sf ON sfc.staffid = sf.id
                             AND sf.staffnumber <>'MAB001'
                    group by classname, subjectname     
                    ORDER BY substring(classname, 6, 2)+0, classname, subjectname
                    ");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<title>Mabest Academy</title>
</head>
<body>
<div class='w3-content'>
<div class='w3-panel w3-blue-gray'>
    <h1>Class Teachers</h1>
</div>
     <table border='0'  class='w3-table-all'>
         <tr class='w3-black'>
             <th>Class</th>
             <th>Subject</th>
             <th>Teachers</th>
         </tr>    
             <?php
                 $prev = '';
                 foreach ($res as $row) {
                     if ($row['classname'] != $prev) {
                         echo "<tr class='w3-gray w3-text-white'><td colspan='3'>{$row['classname']}</td></tr>\n";
                         $prev = $row['classname'];
                     }
                     echo "<tr><td>&nbsp;</td><td>{$row['subjectname']}</td><td>{$row['teach']}</td></tr>\n";
                 }
             ?>
     </table>
</div>
</body>
</html>