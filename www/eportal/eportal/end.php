<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect();

// Simulated login for testing purposes
// $_SESSION['student_id'] = 1; // Uncomment this line for testing

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

switch($termno) {
    case 1: $term_headings = "<th>1st<br>Term<br>100</th><th></th><th></th>";
            break;
    case 2: $term_headings = "<th>1st<br>Term<br>&nbsp;</th><th>2nd<br>Term<br>100</th><th></th>";
            break;
    default: $term_headings = "<th>1st<br>Term<br>&nbsp;</th><th>2nd<br>Term<br>&nbsp;</th><th>3rd<br>Term<br>100</th>";
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
$res->execute( [ $session, $student, $termno, $clid ] );
$data = [];
$core_subjects = ['Math', 'English']; // Define core subjects here
$core_scores = [];

$r = $res->fetch();
if ($r) {
    $studentname = $r['stname'];
    $studentlevel = $r['classname'];
    $studentsession = $r['sessionname'];
    $studentterm = "- Term $termno";
    $passport = "images/" . $r['image'];
    $level = $r['level'];

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

        // Collect scores for core subjects
        if (in_array($r['subjectname'], $core_subjects)) {
            $core_scores[$r['subjectname']][] = $r['score'];
        }
    } while ($r = $res->fetch());

    // Calculate core subjects average
    $core_total = 0;
    $core_count = 0;
    foreach ($core_scores as $subject_scores) {
        $core_total += array_sum($subject_scores);
        $core_count += count($subject_scores);
    }
    $core_average = $core_count ? $core_total / $core_count : 0;

    // Determine PASSED or REPEAT
    $status = $core_average >= 50 ? 'PASSED' : 'REPEAT';

    $avgs = classAverageScores($pdo, $clid, $session, $termno);
    foreach ($avgs as $s => $av) {
        if (isset($data[$s]))
            $data[$s]['avg'] = round($av,0);
    }

    $res = $pdo->prepare("SELECT COUNT(DISTINCT stc.studentid) AS pupils
                                FROM student_class stc 
                                     JOIN semester sm ON sm.id = stc.semesterid
                                     JOIN result r ON stc.id = r.studentclassid
                                WHERE sm.id = ?
                                  AND stc.classid = ?
                        ");
    $res->execute([ $semester, $clid ]);
    $pupil_count = $res->fetchColumn();

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
} else {
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
    $tdata = "<tr><td colspan='13'>No results found</td></tr>\n";
}

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
                               JOIN assessments a ON a.id = e.assessid
                        WHERE stc.studentid = ?
                          AND stc.semesterid = ?
                        ORDER BY a.type, a.assessname, a.id
                        ");
$res->execute([$student, $semester]);
$assess = $res->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);

$assessdata = '';
foreach ($assess as $type => $vals) {
    $assessdata .= '<tr><th>' . strtoupper($type) . '</th>';
    foreach ($vals as $a => $v) {
        $assessdata .= "<td>$v</td>";
    }
    $assessdata .= "</tr>\n";
}

$effort_hdr = '';
$effort_ftr = '';
$ps_data = '';
$pt_data = '';
if (isset($assess['EFFORTS'])) {
    $efforts = count($assess['EFFORTS']);
    if ($efforts) {
        $cols = array_fill(0, $efforts, 0);
        $effort_hdr = "<th>EFFORTS</th>";
        foreach ($assess['EFFORTS'] as $h => $v) {
            $effort_hdr .= "<th colspan=2>" . ($h+1) . "</th>";
        }
        foreach ($assess['EFFORTS'] as $p => $s) {
            $cols[$p] += $s;
        }
        $effort_ftr = "<th>EFFORTS</th>";
        foreach ($cols as $c) {
            $effort_ftr .= "<th colspan=2>$c</th>";
        }
        $effort_hdr .= "<th colspan=2>TOTAL</th>";
        $effort_ftr .= "<th colspan=2>TOTAL</th>";
        $ps_data = '<tr><th>POS</th><th></th><th colspan=2>' . implode("</th><th></th><th colspan=2>", array_keys($assess['EFFORTS'])) . "</th></tr>\n";
        $pt_data = "<tr><th>TERM</th><th></th><th colspan=2>" . implode("</th><th></th><th colspan=2>", array_values($assess['EFFORTS'])) . "</th></tr>\n";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $report_title ?>: <?= $studentname ?> - <?= $studentsession ?><?= $studentterm ?></title>
    <style>
        table {width: 100%; border-collapse: collapse;}
        th, td {border: 1px solid black; padding: 4px; text-align: center;}
        th {background-color: #f2f2f2;}
        .status {font-weight: bold; font-size: 18px;}
        .status.passed {color: green;}
        .status.repeat {color: red;}
    </style>
</head>
<body>
    <h1><?= $report_title ?></h1>
    <table>
        <tr>
            <td rowspan="3"><img src="<?= $passport ?>" alt="Photo" width="100" height="100"></td>
            <th>Student Name</th>
            <td><?= $studentname ?></td>
            <th>Class</th>
            <td><?= $studentlevel ?></td>
        </tr>
        <tr>
            <th>Session</th>
            <td><?= $studentsession ?></td>
            <th>Term</th>
            <td><?= $studentterm ?></td>
        </tr>
        <tr>
            <th colspan="2">Total Pupils in Class</th>
            <td colspan="2"><?= $pupil_count ?></td>
        </tr>
        <tr>
            <th colspan="2">Status</th>
            <td colspan="3" class="status <?= strtolower($status) ?>"><?= $status ?></td>
        </tr>
    </table>
    <h2>Subject Scores</h2>
    <table>
        <thead>
            <tr>
                <th>S/N</th>
                <th>Subject</th>
                <?= $term_headings ?>
                <th>Term Total</th>
                <th>Class Avg</th>
                <th>Grade</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            <?= $tdata ?>
        </tbody>
    </table>
    <h2>Assessments</h2>
    <table>
        <?= $assessdata ?>
        <tr><?= $effort_hdr ?></tr>
        <?= $ps_data ?>
        <tr><?= $effort_ftr ?></tr>
        <?= $pt_data ?>
    </table>
    <p>Grade Key: <?= $grade_list ?></p>
</body>
</html>
