<?php
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect();

$term = $_GET['term'] ?? 0;
$class = $_GET['class'] ?? 0;
$res = $pdo->prepare("SELECT sessionid FROM semester WHERE id = ?");
$res->execute([$term]);
$session = $res->fetchColumn() ?? 0;

$res = $pdo->prepare("SELECT   st.firstname
                             , st.lastname
                             , st.image
                             , cl.classname
                        FROM student st
                             JOIN student_class stc ON st.id = stc.studentid 
                             JOIN class cl ON stc.classid = cl.id
                             JOIN semester sm ON stc.semesterid = sm.id
                        WHERE sm.id = ?
                          AND cl.id = ?
                        ORDER BY cl.id, st.id
                        ");
$res ->execute( [ $term, $class ] );
$results = $res->fetchAll();
$kstud = count($results);

$out = '';
$prev = '';
foreach ($results as $r) {
    if ($r['classname'] != $prev) {
        if ($prev != '') {
            $out .= "</div>\n";
        }
        $out .= "<div class='w3-container w3-dark-gray w3-padding w3-large'>{$r['classname']}&emsp;($kstud students)</div>
                     <div class='w3-row'>
                ";
        $prev = $r['classname'];
    }
    $out .= "<div class='w3-col w3-quarter w3-sand w3-card-4 w3-center w3-padding w3-margin-bottom'>
                <img src = '/images/{$r['image']}' height='160'>
                <div class='w3-panel w3-pale-blue w3-center w3-padding'>
                    {$r['firstname']}<br>
                    {$r['lastname']}
                </div>
            </div>";
}
$out .= '</div>';

function allClassOptions($pdo, $current='0')
{
    $opts = "<option value='0'>- select class -</option>\n";
    $res = $pdo->prepare("SELECT DISTINCT 
                               c.id
                             , classname
                        FROM class c
                             JOIN student_class stc ON c.id = stc.classid
                        WHERE stc.semesterid = ?
                        ORDER BY SUBSTRING(classname,6)+0, classname
                        ");
    $res->execute([$_GET['term']]);
    foreach ($res as $r)  {
        $opts .= "<option value='{$r['id']}'>{$r['classname']}</option>\n";
    }
    return $opts;
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
    <div class='w3-container'>
    <div class='w3-container'>
        <h1>Student Images</h1>
    </div>
    <form class='w3-bar w3-margin-bottom'>
        <label class='w3-bar-item w3-margin-left'>Term</label>
        <select class='w3-bar-item w3-border' name='term' id='semesterid' onchange='this.form.submit()'>
            <?= semesterOptions($pdo, $session, $term)?>
        </select>
        <label class='w3-bar-item w3-margin-left'>Class</label>
        <select class='w3-bar-item w3-border' name='class' id='class' onchange='this.form.submit()'>
            <?= allClassOptions($pdo, $class)?>
        </select>
    </form>

    <?= $out ?>
    
    </div>
</body>
</html>