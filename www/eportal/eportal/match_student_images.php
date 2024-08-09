<?php
include 'db_inc.php';
$pdo = pdoConnect();

#
#  CREATE A TEMP TABLE OF IMAGE NAMES
#

$pdo->exec("CREATE TEMPORARY TABLE image (image varchar(50))");

$images = glob('images/*.jpg');
$images = array_map('basename', $images);

foreach ($images as $i) {
    $imgdata[] = "('$i')";
}

$pdo->exec("INSERT INTO image (image) VALUES " . join(',', $imgdata));

#
#  QUERY TO MATCH STUDENT RECORDSS AGAINST STORED IMAGES
#

$res = $pdo->query("select coalesce(i.image, '<i>Missing</i>') as image_name
                          , concat(firstname, ' ', lastname) as student
                          , classname
                     from image i 
                          RIGHT JOIN (
                              student s 
                              JOIN student_class stc ON s.id = stc.studentid
                                                     and stc.semesterid = 10     
                              JOIN class c ON stc.classid = c.id
                          ) ON i.image = s.image
                     ");
$idata = '';
foreach ($res as $r) {
    $idata .= "<tr><td>" . join('</td><td>', $r) . "</td></tr>\n";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<title>Class Teachers</title>
</head>
<body>
    <div class='w3-container w3-bottombar w3-padding'>
        <h1>Match Student Images</h1>
    </div>
    <div class='w3-content'>
    <table class='w3-table-all w3-margin-top w3-small'>
        <tr class='w3-black'><th>Image Name</th><th>Student Name</th><th>Class</th></tr>
        <?=$idata?>
    </table>
    </div>
</body>
</html>
