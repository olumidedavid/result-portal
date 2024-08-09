<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';

$pdo = pdoConnect();

// Check if student is logged in, otherwise redirect to login page
if (!isset($_SESSION['student_id']) && !isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

$clid = $_GET['classid'] ?? -1;

if (isset($_SESSION['student_id'])) {
    $student = $_SESSION['student_id'];
    $staff = $_GET['staffid'] ?? 0;
} elseif (isset($_SESSION['staff_id'])) {
    $staff = $_SESSION['staff_id'];
    $student = $_GET['studentid'] ?? 0;
} else {
    header("Location: login.php");
    exit;
}

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

$session = $row['sessionid'] ?? 0;
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

switch ($termno) {
    case 1:
        $term_headings = "<th>1st<br>Term<br>100</th>
                                  <th></th>
                                  <th></th>";
        break;
    case 2:
        $term_headings = "<th>1st<br>Term<br>&nbsp;</th>
                                  <th>2nd<br>Term<br>100</th>
                                  <th></th>";
        break;
    default:
        $term_headings = "<th>1st<br>Term<br>&nbsp;</th>
                                  <th>2nd<br>Term<br>&nbsp;</th>
                                  <th>3rd<br>Term<br>100</th>";
}

$report_title = $termno == 3 ? "End of Year Results" : "End of Term Results";

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
$res->execute([$session, $student, $termno, $clid]);
$data = [];
$r = $res->fetch();
if ($r) {
    $studentname = $r['stname'];
    $studentlevel = $r['classname'];
    $studentsession = $r['sessionname'];
    $studentterm = "- Term $termno";
    $passport = "images/" . $r['image'];
    $level = $r['level'];

    do {
        if (!isset($data[$r['subjectid']])) {
            $data[$r['subjectid']] = [
                'name' => $r['subjectname'],
                'exams' => ['CA1' => '', 'CA2' => '', 'CA3' => '', 'Exam' => ''],
                'scores' => [1 => 0, 0, 0],
                'avg' => 0
            ];
        }
        if ($r['term'] == $termno && isset($data[$r['subjectid']]['exams'][$r['exam']])) {
            $data[$r['subjectid']]['exams'][$r['exam']] = $r['score'];
        }
        $data[$r['subjectid']]['scores'][$r['term']] += $r['score'];
    } while ($r = $res->fetch());

    $avgs = classAverageScores($pdo, $clid, $session, $termno);
    foreach ($avgs as $s => $av) {
        if (isset($data[$s]))
            $data[$s]['avg'] = round($av, 0);
    }
}

$res = $pdo->prepare("SELECT COUNT(DISTINCT stc.studentid) AS pupils
                                FROM student_class stc 
                                     JOIN semester sm ON sm.id = stc.semesterid
                                     JOIN result r ON stc.id = r.studentclassid
                                WHERE sm.id = ?
                                  AND stc.classid = ?
                        ");
$res->execute([$semester, $clid]);
$pupil_count = $res->fetchColumn();

$tdata = '';
$n = 1;
$grand_total = 0;
$subject_count = 0;
foreach ($data as $subid => $subdata) {
    $tdata .= "<tr><td>$n</td><td>{$subdata['name']}</td>";
    foreach ($subdata['exams'] as $s) {
        $tdata .= "<td>" . ($s == '' ? '&ndash;' : $s) . "</td>";
    }
    foreach ($subdata['scores'] as $t => $s) {
        if ($s == 0) $s = '';
        $tdata .= "<td>" . ($t <= $termno ? $s : '') . "</td>";
    }
    $temp = array_filter($subdata['scores']);
    $total = $temp ? round(array_sum($temp) / count($temp)) : 0;
    $grand_total += $total;
    if ($total) {
        list($grade, $comment) = getGradeComment($pdo, $total, $level);
        $subject_count++;
        $status = determineStatus($pdo, $student, $subid, $termno, $total); // Example function
    } else {
        $grade = '-';
        $comment = '-';
        $status = '-';
    }
    $clr = GRADE_COLOUR[$grade] ?? '#000';
    $tdata .= "<td>$total</td><td>{$subdata['avg']}</td><td style='color:$clr; font-weight: 600;'>$grade</td><td>$comment</td></tr>\n";
    ++$n;
}

$grade_list = '';

$res = $pdo->query("SELECT GROUP_CONCAT( grade, concat('&nbsp;(',comments,')'), '&nbsp;', concat(lomark,'&nbsp;-&nbsp;',himark)
                               ORDER BY id SEPARATOR ', ')
                        FROM examgrade
                        WHERE level_group = ($level > 5)
                        ");
$grade_list = $res->fetchColumn();

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
$res->execute([$student, $semester]);
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student result for <?= $studentname ?></title>
    <link rel="stylesheet" type="text/css" href="fontawesome/css/all.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        .res-holder, .assess-tbl {
            margin: 0 auto;
            padding: 20px;
            width: 70%;
            height: 80%;
            font-family: 'Courier New', Courier, monospace;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
        }

        .res-holder h1 {
            font-size: 20px;
            text-align: center;
            color: #00c;
        }

        .res-holder th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .res-holder th {
            background-color: #f2f2f2;
        }

        .res-holder td {
            background-color: #fff;
        }

        .res-holder img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            margin: 0 auto;
        }

        .res-holder .info {
            text-align: center;
            margin-bottom: 20px;
        }

        .res-holder .info p {
            margin: 5px;
            font-size: 18px;
            color: #333;
        }

        .res-holder .info p:first-child {
            font-weight: bold;
            font-size: 22px;
        }

        .res-holder .info p:last-child {
            font-style: italic;
        }

        .grade-list {
            margin: 20px auto;
            padding: 10px;
            width: 70%;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
        }

        .grade-list h3 {
            font-size: 18px;
            text-align: center;
            margin-bottom: 10px;
            color: #00c;
        }

        .grade-list p {
            margin: 5px;
            font-size: 16px;
            color: #333;
        }

        .grade-list ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .grade-list ul li {
            margin-bottom: 5px;
        }

        .grade-list ul li i {
            margin-right: 10px;
            color: #00c;
        }

        .grade-list ul li span {
            font-weight: bold;
        }

        .assess-tbl {
            width: 50%;
            margin: 20px auto;
        }

        .stamp {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            text-align: center;
            border-radius: 50%;
            background-color: #4CAF50; /* Green */
            color: white;
            font-size: 20px;
            font-weight: bold;
            line-height: 150px;
        }
    </style>
</head>
<body>
<div class="w3-container">
    <div class="w3-bar w3-border w3-light-grey">
        <a href="resultindex.php" class="w3-bar-item w3-button">Home</a>
        <a href="studentresult.php?studentid=<?= $student ?>&semesterid=<?= $semester ?>&classid=<?= $clid ?>&staffid=<?= $staff ?>"
           class="w3-bar-item w3-button">Print Student Result</a>
        <a href="uploadresult.php?studentid=<?= $student ?>&semesterid=<?= $semester ?>&classid=<?= $clid ?>&staffid=<?= $staff ?>"
           class="w3-bar-item w3-button">Upload Student Result</a>
        <a href="login.php" class="w3-bar-item w3-button">Logout</a>
    </div>
</div>

<div class="res-holder">
    <img src="<?= $passport ?>" alt="Student Image">
    <div class="info">
        <p><?= $studentname ?></p>
        <p><?= $studentlevel ?> - <?= $studentsession ?> <?= $studentterm ?></p>
    </div>

    <h1><?= $report_title ?></h1>

    <table class="w3-table w3-bordered">
        <thead>
        <tr>
            <th>S/N</th>
            <th>Subject</th>
            <th>CA 1</th>
            <th>CA 2</th>
            <th>CA 3</th>
            <th>Exam</th>
            <?= $term_headings ?>
            <th>Total</th>
            <th>AVG</th>
            <th>Grade</th>
            <th>Comments</th>
        </tr>
        </thead>
        <tbody>
        <?= $tdata ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="8"><b>Grand Total</b></td>
            <td><b><?= $grand_total ?></b></td>
            <td></td>
            <td></td>
        </tr>
        </tfoot>
    </table>

    <div class="grade-list">
        <h3>Grade List</h3>
        <ul>
            <li><i class="fas fa-check"></i><span>A</span> - Excellent (75 - 100)</li>
            <li><i class="fas fa-check"></i><span>B</span> - Very Good (65 - 74)</li>
            <li><i class="fas fa-check"></i><span>C</span> - Credit (55 - 64)</li>
            <li><i class="fas fa-check"></i><span>D</span> - Pass (50 - 54)</li>
            <li><i class="fas fa-check"></i><span>E</span> - Fair (45 - 49)</li>
            <li><i class="fas fa-check"></i><span>F</span> - Fail (0 - 44)</li>
        </ul>
    </div>

    <div class="assessments">
        <h2>Affective Assessment</h2>
        <?= $afflist ?>
        <h2>Psychomotor Assessment</h2>
        <?= $psychlist ?>
    </div>

    <div class="stamp">
        <?php
        // Determine overall status (PASSED or REPEAT)
        $overall_status = determineOverallStatus($pdo, $student, $semester, $termno); // Example function

        if ($overall_status === 'PASSED') {
            echo '<span style="font-size: 24px;">PASSED</span>';
        } elseif ($overall_status === 'REPEAT') {
            echo '<span style="font-size: 24px;">REPEAT</span>';
        }
        ?>
    </div>

</div>
</body>
</html>
