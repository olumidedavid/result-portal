<?php
session_start();
include 'db_inc.php';
$pdo = pdoConnect();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

$search = $_GET['search'] ?? '';

$res = $pdo->query("SELECT concat(st.firstname, ' ', st.lastname) as name
                    FROM student st
                    ORDER BY name
                   ");
$pupilsarr = array_column($res->fetchAll(),'name');
$pupils = '';
foreach ($pupilsarr as $p) {
    $pupils .= "<option value='$p'>";
}


$res = $pdo->prepare("SELECT concat(st.firstname, ' ', st.lastname) as studentname
                             , ss.sessionname
                             , sm.semestername
                             , cl.classname
                             , r.exam
                             , round(sum( CASE exam
                                              WHEN 'Exam' THEN score*100/70
                                              ELSE score * 10
                                              END
                                     )) as score
                             , count(distinct subjectid) as subcount
                             , avgscore
                        FROM result r
                             JOIN course c ON r.courseid = c.id 
                             JOIN student_class stc ON r.studentclassid = stc.id
                             JOIN class cl ON stc.classid = cl.id
                             JOIN student st ON stc.studentid = st.id
                             JOIN semester sm ON stc.semesterid = sm.id
                             JOIN session ss ON sm.sessionid = ss.id
                             JOIN (
                                    SELECT stc.studentid
                                         , round(avg( CASE exam
                                                          WHEN 'Exam' THEN score*100/70
                                                          ELSE score * 10
                                                          END
                                                 )) as avgscore
                                    FROM result r
                                         JOIN student_class stc ON r.studentclassid = stc.id
                                    GROUP BY studentid
                                  ) av ON st.id = av.studentid
                        WHERE concat(st.firstname, ' ', st.lastname) LIKE ?
                        GROUP BY st.id, ss.id, sm.id, r.exam
                        ORDER BY st.firstname, ss.sessionname, sm.semestername, r.exam
                        ");
$res->execute([ $search.'%' ]);
$data = [];
foreach ($res as $r) {
    if (!isset($data[$r['studentname']][$r['sessionname']][$r['semestername']])) {
        $data[$r['studentname']][$r['sessionname']][$r['semestername']] = [  'class'=> $r['classname'],
                                                                             'avg'  => $r['avgscore'],
                                                                             'exams'=> [ 'CA1'=>0, 'CA2'=>0, 'CA3'=>0, 'Exam'=>0 ]
                                                                          ];
    }
    $pcent = round($r['score']/$r['subcount'], 0);
    $data[$r['studentname']][$r['sessionname']][$r['semestername']]['exams'][$r['exam']] = $pcent;
}


function chart ($data)
{
    $exams = [ 'CA1', 'CA2', 'CA3', 'Exam' ];
    $out = "<svg width='100%' viewBox='0 0 240 155'>
            <rect x='0' y='0' width='240' height='155' fill='#fff' stroke='#000'/>";
            for ($y=100; $y>0; $y-=10) {
                $strk = $y==60 ? '#ccc' : '#eee';
                $out .= "<path d='M 0 $y l 240 0' stroke='$strk'/>\n";
            }
            $x = 0;
            $sx1 = $sy1 = -1;
            foreach ($data as $trm => $tdata) {
                $x1 = $x*80;
                foreach ($exams as $ex) {
                    $out .= "<rect x='$x1' y='110' width='20' height='15' fill='#607d8b' stroke='#eee'/>\n";
                    $tx = $x1 + 10;
                    $out .= "<text x='$tx' y='120' text-anchor='middle' fill='#fff' style='font-size:6px;'>$ex</text>\n";
                    $x1 += 20;
                }
                $x1 = $x * 80;
                foreach ($exams as $ex) {
                    $out .= "<rect x='$x1' y='125' width='20' height='15' fill='#eee' stroke='#607d8b'/>\n";
                    $tx = $x1 + 10;
                    $score = $tdata['exams'][$ex];
                    if ($score) {
                        $sx = $tx;
                        $sy = 110 - $score;
                        if ($sx1 != -1) {
                            $out .= "<path d='M $sx $sy L $sx1 $sy1' stroke='#8F1FCF' />\n";
                        }
                        $out .= "<circle cx='$sx' cy='$sy' r='2' fill='#8F1FCF' />\n";
                        $sx1 = $sx;
                        $sy1 = $sy;
                    }
                    $out .= "<text x='$tx' y='136' text-anchor='middle' fill='#000' style='font-size:6px;'>{$score}%</text>\n";
                    $x1 += 20;
                }
                $x1 = $x * 80;
                $out .= "<rect x='$x1' y='140' width='80' height='15' fill='#607d8b' stroke='#eee'/>\n";
                $tx = $x1 + 40;
                $out .= "<text x='$tx' y='149' text-anchor='middle' fill='#fff' style='font-size:6px;'>{$tdata['class']}</text>\n";
                ++$x;
                $avy = 110 - $tdata['avg'];
                $out .= "<path d='M 0 $avy l 240 0' stroke='#54BC54' />\n";     // student average
            }
            $out .= "<path d='M 80 0 l 0 110 M 160 0 l 0 110' stroke='#607d8b' />\n";  // term dividers
    $out .= "</svg>
           ";
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Student Exam History</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
    $().ready(function() {
        
    })
</script>
<style type='text/css'>
.bluegray {
    background-color: #607d8b;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding w3-margin'>
        <h1><img src='logo1.png' border='0' width='69' height='65' alt='logo'>Student Exam History</h1>
    </header>
    <div class='w3-bar w3-light-gray'>
    <form id='form1'>
        <label class='w3-bar-item' for='search'>Search Name </label>
        <input class='w3-input w3-bar-item w3-border ' name='search' id='search' value='<?=$search?>' list='pupils' >
        <button class='w3-button w3-bar-item w3-blue-gray w3-margin-left'>Show history</button>
        <datalist id='pupils'><?=$pupils?></datalist>
    </form>
    </div>
    <div class='w3-container'>
    
    <?php
    if ($data && $search)
        foreach ($data as $stname => $stdata) {
            echo "
                <div class='w3-container w3-padding w3-dark-gray'>
                     <h3>$stname</h3>
                </div>
                <div class='w3-row-padding w3-stretch'>
                ";
            foreach ($stdata as $sess => $sessdata) {
                 
                echo "
                <div class='w3-col s12 m4 w3-center w3-margin-top'>
                     <div class='w3-large w3-blue-gray'>$sess</div>" .
                     chart($sessdata) .
                     "</div>";
            }
            echo "</div>\n";
        }
    ?>
    </div>
</body>
</html>
