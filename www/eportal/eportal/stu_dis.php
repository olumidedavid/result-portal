<?php
require 'db_inc.php';
$pdo = pdoConnect();


$res = $pdo->query("SELECT cl.classname
                         , ss.sessionname
                         , sm.semestername
                         , count(stc.studentid) tot_students
                         , group_concat(stc.studentid  ORDER BY stc.studentid SEPARATOR ', ') as students
                    FROM class cl 
                         LEFT JOIN (
                             student_class stc
                             JOIN semester sm on stc.semesterid = sm.id
                             JOIN session ss ON sm.sessionid = ss.id  
                             ) on stc.classid = cl.id
                    GROUP BY classname, sessionname, semestername
                    ORDER BY substr(classname,6,2)+0, classname, sessionname, semestername
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
    <h1>Student Distribution</h1>
</div>
     <table border='1'  class='w3-table-all'>
         <tr class='w3-black'>
             <th>Class</th>
             <th>Session</th>
             <th>Term</th>
             <th>Num<br>students</th>
             <th style='width: 40%'>Student IDs</th>
         </tr>    
             <?php
                 
                 foreach ($res as $row) {
                     echo "<tr><td>" . join('</td><td>', $row) . "</td></tr>\n";
                 }
             ?>
     </table>
</div>
</body>
</html>
