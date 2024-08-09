<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect('mabest_biotime') ;

################################################################################
#  Check admin is logged in                                                    #
################################################################################
    if (!getAdminType($pdo)) {
        header("Location: login.php");
        exit;
    }


$session = $_GET['session'] ?? 2;

$passed = getPassedStudents($pdo, $session);

$res = $pdo->prepare("SELECT DISTINCT
                               classname
                             , studentid
                             , concat(firstname, ' ', lastname) as name
                        FROM student_class stc
                             JOIN class c ON stc.classid = c.id
                             JOIN student st ON stc.studentid = st.id
                        JOIN semester sm ON stc.semesterid = sm.id
                        WHERE sessionid = ?
                        ORDER BY substring(classname,6,2)+0, c.id, firstname
                        ");
$res->execute( [ $session ] );
$results = $res->fetchAll();

$classes = array_column($results, 'classname', 'studentid');

$prev = '';
$list = '';
$kp = $kr = 0;
foreach ($results as $r) {
    if ($r['classname'] != $prev) {
        list($pk, $ks, $passrate) = classPassRate($classes, $passed, $r['classname']);
        $list .= "<tr><td colspan='2' class='class-name '>{$r['classname']}</td>
                      <td class='class-pcent'>$passrate &emsp; $pk / $ks</td>
                  </tr>";
        $prev = $r['classname'];
    }
    $name = ucwords(strtolower($r['name']));
    if (isset($passed[$r['studentid']])) {
        $list .= "<tr><td>&nbsp;</td><td class='name pass-name'>$name</td>";
        $list .= "<td class='pass caps'><span class='pass'>Passed</span></td></tr>\n";
        ++$kp;
    }
    else {
        $list .= "<tr><td>&nbsp;</td><td class='name fail-name'>$name</td>";
        $list .= "<td class='fail caps'>Repeat</td></tr>\n";
        ++$kr;
    }
}
if ($kp || $kr) {
    $ppc = number_format($kp*100 / ($kp + $kr), 1);
    $rpc = number_format($kr*100 / ($kp + $kr), 1);
} else {
    $rpc = $ppc = 0;
}
/*
function getPassedStudents($pdo, $session)
{
    $passed = [];

    $query = "SELECT 
                   studentid
                 , sum(core = 1) as core1
                 , sum(core = 2) as core2
            FROM (
                    SELECT classid
                         , studentid
                         , subjectid
                         , core
                         , sum(score) as tot
                         , count(distinct stc.semesterid) as terms
                         , CASE WHEN classid <= 9 
                               THEN 39
                               ELSE 49
                               END as passscore
                    FROM student_class stc
                         JOIN semester sm ON stc.semesterid = sm.id
                         JOIN result r ON stc.id = r.studentclassid
                         JOIN course c ON r.courseid = c.id
                         JOIN subject sb ON c.subjectid = sb.id
                    WHERE core > 0 AND
                          sessionid = ?
                    GROUP BY studentid, subjectid
                    HAVING sum(score) / count(distinct stc.semesterid) > passscore
                 ) tots
            GROUP BY studentid
            HAVING sum(core = 1) = 2 AND sum(core = 2) >= 3
            ";
    $res = $pdo->prepare($query);
    $res->execute( [ $session ] );
    foreach ($res as $r) {
        $passed[$r['studentid']] = 1;
    }
    return $passed;
}

function stamp($id, $passed)
{
    $sz = 100;
    $theta = -20;
    if ($passed[$id] ?? 0) {
        $txt = 'PASSED';
        $pass = 1;
    }
    else {
        $txt = 'REPEAT';
        $pass = 0;
    }
    if ($pass) {
        return "<svg width='$sz' height='$sz' viewBox='0 0 100 100'>
        <g transform='rotate($theta 50 50)'>
                <circle cx='50' cy='50' r='47' fill='#027cbc' opacity='0.75'/>
                <circle cx='50' cy='50' r='47' fill='none' stroke='#F2D335' opacity='0.75' stroke-width='5'/>
                <text x='50' y='58' class='svgtext' text-anchor='middle' fill='#ffffff' style='font-size: 20px; font-weight:600'>$txt</text>
        </g>
        </svg>";
    }
    else {
        return "<svg width='$sz' height='$sz' viewBox='0 0 100 100'>
        <g transform='rotate($theta 50 50)'>
            <circle cx='50' cy='50' r='47' fill='#CF8505' opacity='0.75'/>
            <circle cx='50' cy='50' r='47' fill='none' stroke='#027cbc' opacity='0.45' stroke-width='5'/>
            <text x='50' y='58' class='svgtext' text-anchor='middle' fill='#ffffff' style='font-size: 20px; font-weight:600;'>$txt</text>
        </g>
        </svg>";
    }
}
*/
function sessionOpts($pdo, $current)
{
    $opts = "<option value=''>- select session -</option>\n";
    $res = $pdo->query("SELECT id
                             , sessionname
                        FROM session
                        ORDER BY date_from DESC
                       ");
    foreach ($res as $r) {
        $sel = $r['id'] == $current ? 'selected' : '';
        $opts .= "<option $sel value='{$r['id']}'>{$r['sessionname']}</option>\n";
    }
    return $opts;
}

function classPassRate($classes, $passed, $clname)
{
    $students = array_keys($classes, $clname);
    $ks = count($students);
    $kp = count(array_intersect(array_keys($passed), $students));
    return [$kp, $ks, sprintf('%0.1f %s', $kp * 100 / $ks, '<small>%</small>')];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>sample</title>
<meta charset="utf-8">
<style type='text/css'>
    th {
        background-color: black;
        color: white;
        padding: 8px 4px;;
    }
    td {
        padding: 4px;
        font-size: 14pt;
    }
    .list {
        width: 90%;
        margin: 20px auto;
        border-collapse: collapse;
    }
    .name {
        font-variant: small-caps;
        font-size: 14pt;
    }
    .class-name {
        background-color: #ccc;
        color: #585858;
        font-weight: 600;
    }
    .class-pcent {
        background-color: #666;
        color: #FFF;
        font-weight: 600;
        padding: 8px 16px;
    }
    .caps {
        font-variant: small-caps;
    }
    .pass {
        background-color: #07ABF6;
        color: white;
        text-align: center;
    }
    .fail {
        background-color: #CF8505;
        color: white;
        text-align: center;
    }
    .fail-name {
        background-color: #FBC66A;
    }
    .pass-name {
        background-color: #ADE3FC;
    }
    .svgtext {
        font-size: 40px;
        font-weight: 700;
        font-family: arial, helvetica, sans-serif;
    }
    .percent {
        font-size: 24pt;
    }
    .res_pcent {
        width: 70%;
        margin: 20px auto;
        border-collapse: collapse;
        text-align: center;
    }
    .head {
        padding: 0 16px;
    }
    form {
        background-color: #808080;
        color: white;
        padding: 16px;
        margin: 0 16px;
    }
    small {
        font-size: 75%;
    }
    hr {
        height: 20px;
        background-image: linear-gradient(#c0c0c0, #ffffff);
        border: none;
    }
</style>
</head>
<body>
    <div class='head'>
        <h1>End of Year Passes</h1>
    </div>
    <form>
    Session 
    <select name='session' onchange='this.form.submit()'>
       <?=sessionOpts($pdo, $session)?>
    </select>
    </form>
    
    <table class='res_pcent' border='0'>
        <caption class='percent'>Overall School Performance</caption>
        <tr><td><?=stamp(1)?></td><td><?=stamp(0)?></td></tr>
        <tr><td class='percent'><?=$ppc?> %</td><td class='percent'><?=$rpc?> %</td></tr>
    </table>
    <hr>
    <table class='list' border='0'>
        <caption class='percent'>Class Performances</caption>
        <tr><th>Class</th>
            <th>Name</th>
            <th>Status</th>
        </tr>
        <?=$list?>
    </table>
</body>
</html>

<!--

TABLE: subject
+----+-------------------------------+--------------+------+
| id | subjectname                   | departmentid | core |
+----+-------------------------------+--------------+------+
| 1  | English Language              | 2            | 1    |   1 = Primary core subject
| 2  | Mathematics                   | 1            | 1    |   
| 3  | Biology                       | 1            | 2    |   2 = Secondary (other relevant subject)
| 4  | Physics                       | 1            | 2    |   
| 5  | Chemistry                     | 1            | 2    |   
| 6  | Geography                     | 2            | 2    |   
| 7  | Further Mathematics           | 1            | 2    |
| 8  | Agricultural Science          | 1            |      |
| 9  | Economics                     | 3            | 2    |   
| 10 | Home Economics                |              |      |
| 11 | Technical Drawing             | 1            |      |
| 12 | Yoruba                        | 2            | 2    |
| 13 | French                        | 2            | 2    |
| 14 | Christian Religious Studies   | 2            |      |
| 15 | Basic Science and Technology  | 1            |      |
| 16 | History                       | 2            | 2    |
| 17 | Government                    | 3            |      |
| 18 | Civic Education               | 3            |      |
| 19 | Business Studies              | 3            | 2    |   
| 20 | Checkpoint Science            | 1            |      |
| 21 | Christian Religious Knowledge | 2            |      |
| 22 | Cultural and Creative Arts    | 2            |      |
| 23 | National Value Education      | 2            |      |
| 24 | Pre-vocational Studies        | 3            |      |
| 25 | Data Processing               | 3            |      |
| 26 | Literature-in-English         | 2            | 2    |   
| 27 | Catering Craft Practice       |              |      |
| 28 | Animal Husbandry              |              |      |
| 29 | Financial Accounting          | 3            | 2    |   
| 30 | Commerce                      | 3            |      |
| 31 | Dying and Bleaching           |              |      |
| 32 | Business Studies (IGCSE)      | 3            | 2    |   
| 33 | Music                         | 1            |      |
+----+-------------------------------+--------------+------+

-->