<?php
include 'db_inc.php';
$pdo = pdoConnect();

$res = $pdo->query("SELECT DATABASE()");
$dbname = $res->fetchColumn();

$out = '';
$res = $pdo->query("SHOW TABLES FROM $dbname");
while (list($tname) = $res->fetch(PDO::FETCH_NUM)) {
     $out .= "<h4>$tname</h4>";
     $res2 = $pdo->query("SHOW CREATE TABLE $tname");
     $out .= "<div class='sql'><pre>" . $res2->fetchColumn(1) . "</pre></div>\n";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Example</title>
<meta charset="utf-8">
<style type='text/css'>
.sql {
    width: 600px;
    border: 1px solid gray;
    background-color: #F0F0F0;
    padding: 8px;
    margin-left: 48px;
    font-family: monspace;
    font-size: 10pt;
}
</style>
</head>
<body>
<h1>Table Definitions - <?=$dbname?></h1>
<hr>
<?=$out?>
</body>
</html>
