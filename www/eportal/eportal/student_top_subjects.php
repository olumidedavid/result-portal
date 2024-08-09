<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect();

unset($_SESSION['student_id']);

if (!isset($_SESSION['staff_id']) && !isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['student_id'])) {
    $extra = "AND st.id = ?";
} else $extra = '';

$semester = $_GET['semester'] ?? '';
$class =    $_GET['class'] ?? '';
$midend = $_GET['midend'] ?? 'M';
switch ($midend) {
    case 'M': $res = $pdo->prepare("SELECT subjectname
                                         , stname
                                         , score
                                         , @seq := IF(stname = @prevs, @seq+1, 1) as seq
                                         , @rank := IF(score = @prev, @rank, @seq) as 'rank'
                                         , @prev := score as prev
                                         , @prevs := stname as prevs
                                    FROM (
                                            SELECT cl.classname
                                                 , sb.subjectname
                                                 , concat(st.firstname, ' ', st.lastname) as stname
                                                 , round( sum( case exam when 'Exam' then score/70*100
                                                                         else score*10
                                                                         end 
                                                              ) / COUNT(distinct exam) ) as score
                                            FROM student_class stc 
                                                 JOIN student st ON stc.studentid = st.id
                                                 JOIN class cl ON stc.classid = cl.id
                                                 JOIN level l ON cl.levelid = l.id
                                                 JOIN course c ON l.id = c.levelid
                                                 JOIN subject sb ON c.subjectid = sb.id
                                                 JOIN result r ON r.courseid = c.id AND r.studentclassid = stc.id
                                            WHERE stc.semesterid = ?
                                                    AND exam = 'CA1'
                                                    AND cl.id = ?
                                                    $extra
                                            GROUP BY st.id, sb.id
                                            ORDER BY stname, score DESC
                                            LIMIT 9223372036854775807        -- MariaDB bug workaround
                                            ) ordered
                                         JOIN (SELECT @prevs:='', @prev:=0, @seq:=0, @rank:=0) init
                                    ");
              $chkm = 'checked';
              $chke = '';
              break;
    default:  $res = $pdo->prepare("SELECT subjectname
                                         , stname
                                         , score
                                         , @seq := IF(stname = @prevs, @seq+1, 1) as seq
                                         , @rank := IF(score = @prev, @rank, @seq) as 'rank'
                                         , @prev := score as prev
                                         , @prevs := stname as prevs
                                    FROM (
                                            SELECT   sb.subjectname
                                                 , concat(st.firstname, ' ', st.lastname) as stname
                                                 , round( sum( score )) as score
                                            FROM student_class stc 
                                                 JOIN student st ON stc.studentid = st.id
                                                 JOIN class cl ON stc.classid = cl.id
                                                 JOIN level l ON cl.levelid = l.id
                                                 JOIN course c ON l.id = c.levelid
                                                 JOIN subject sb ON c.subjectid = sb.id
                                                 JOIN result r ON r.courseid = c.id AND r.studentclassid = stc.id
                                            WHERE stc.semesterid = ?
                                                    AND cl.id = ?
                                                    $extra
                                            GROUP BY st.id, sb.id
                                            ORDER BY stname, score DESC
                                            LIMIT 9223372036854775807        -- MariaDB bug workaround
                                         ) ordered 
                                         JOIN (SELECT @prevs:='', @prev:=0, @seq:=0, @rank:=0) init
                                    ");
              $chke = 'checked';
              $chkm = '';
              break;       
}

if ($extra) {
    $res->execute([ $semester, $class, $_SESSION['student_id'] ]);
} else {
    $res->execute([ $semester, $class ]);
}
$data = [];
foreach ($res as $r) {
    if (!isset($data[$r['stname']])) {
        $data[$r['stname']]['subjects'] = [];
    }
    
    $data[$r['stname']]['subjects'][] = [ 'name' => $r['subjectname'], 'score' => $r['score'], 'rank' => $r['rank'] ];
}


function clsOpts($db, $current='')
{
    $opts = "<option value=''>- select class -</option>\n";
    $res = $db->query("SELECT id
                             , classname
                        FROM class
                        ORDER BY substring(classname, 6, 2)+0     
                       ");
    foreach ($res as $r) {
        $sel = $r['id'] == $current ? 'selected' : '';
        $opts .= "<option $sel value='{$r['id']}'>{$r['classname']}</option>\n";
    }
    return $opts;
}

function nameBar($name, $val)
{
    $wid = 400;
    $ht = 32;
    $bar = "<svg width='90%' viewBox='0 0 $wid $ht'>\n
            <defs>
            <linearGradient id='pchi' x1='0' y1='0' x2='0' y2='1'>
                <stop offset='0%' stop-color='#54BC54'/>
                <stop offset='10%' stop-color='#54BC54'/>
                <stop offset='15%' stop-color='#eee'/>
                <stop offset='20%' stop-color='#54BC54'/>
                <stop offset='100%' stop-color='#0B7604'/>
                </lineargradient>
            <linearGradient id='pclo' x1='0' y1='0' x2='0' y2='1'>
                <stop offset='0%' stop-color='#E02222'/>
                <stop offset='10%' stop-color='#E02222'/>
                <stop offset='15%' stop-color='#eee'/>
                <stop offset='20%' stop-color='#E02222'/>
                <stop offset='100%' stop-color='#A91723'/>  
                </lineargradient>
            <linearGradient id='pcmid' x1='0' y1='0' x2='0' y2='1'>
                <stop offset='0%' stop-color='#F2D335'/>
                <stop offset='10%' stop-color='#F2D335'/>
                <stop offset='15%' stop-color='#eee'/>
                <stop offset='20%' stop-color='#F2D335'/>
                <stop offset='100%' stop-color='#EC9807'/>
                </lineargradient>
            <style type='text/css'>
                rect { opacity: 0.2; }
            </style>
            </defs>
            ";
    $max = 100;
    $pix = $wid/$max;
    if ($val > $max) $val = $max;
// percentage labels
//    for ($p=25; $p<=75; $p+=25) {
//        $ty=8;
//        $tx = $p * $pix;
//        $bar .= "<path d='M $tx $ty l 0 4' stroke='#AAA' />
//            <text x='$tx' y='$ty' class='pcent' >{$p}%</text>\n";
//    }
    // draw bar
    $w = $val * $pix;
    $h = $ht-12;
    
    if ($val >= 80) $barfill = 'url(#pchi)';
    elseif ($val >= 50) $barfill = 'url(#pcmid)';
    else $barfill = 'url(#pclo)';
//    $barfill = 'url(#pchi)';
//    $bar .= "<rect x='0' y='12' width='$wid' height='$h' stroke='#444' fill='#DDD' />\n";
    $bar .= "<rect x='0' y='12' width='$w' height='$h' fill='$barfill' />\n";
    $ty = $ht-4;
    $bar .= "<text x='4' y='$ty' fill='#000'>$name</text>\n";
    $bar .= "</svg>\n";
    return $bar;
}

function legend()
{
    $out = "<svg width='200' height='22' >
                <style type='text/css'>
                    .lo {fill: #E02222; opacity: 0.2;}
                    .mid {fill: #F2D335; opacity: 0.2;}
                    .hi {fill: #54BC54; opacity: 0.2;}
                </style>
                <rect x='15' y='0' width='50' height='32' class='lo'/>
                <rect x='75' y='0' width='50' height='32' class='mid'/>
                <rect x='135' y='0' width='50' height='32' class='hi'/>
                <text x='40' y='16' text-anchor='middle' fill='#000' font-size='12'> &lt; 50 </text>
                <text x='100' y='16' text-anchor='middle' fill='#000' font-size='12'> 50-79 </text>
                <text x='160' y='16' text-anchor='middle' fill='#000' font-size='12'> 80+ </text>
            </svg>
    ";
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Student Top Subjects</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
    $().ready(function() {
        
    })
</script>
<style type='text/css'>
table {
    width: 94%;
    margin: 0 auto;
    }

</style>
</head>
<body>
    <header class='w3-container w3-padding w3-margin'>
        <h1><img src='logo1.png' height='65' alt='logo'>Student Top Subjects</h1>
    </header>
    <div class='w3-bar w3-light-gray'>
    <form id='form1'>
        <label class='w3-bar-item' for='search'>Class </label>
        <select class='w3-input w3-bar-item w3-border ' name='class' id='class' > 
            <?= clsOpts($pdo, $class) ?>
        </select>
        <label class='w3-bar-item' for='search'>Term </label>
        <select class='w3-input w3-bar-item w3-border ' name='semester' id='semester' > 
            <?= semesterOptions($pdo, 0, $semester) ?>
        </select>
        <span class='w3-bar-item'>
            &emsp;<input type='radio' name='midend' value = 'M' <?=$chkm?>> Mid-term
            &emsp;<input type='radio' name='midend' value = 'E' <?=$chke?>> End-term
        </span>
        &emsp;<button class='w3-button w3-bar-item w3-blue-gray w3-margin-left'>Show lists</button>
    </form>
    </div>
    <div class='w3-padding w3-center'
        <span class='w3-bar-item'><?=legend()?></span>
        </div>
    <div class='w3-container w3-margin-top'>
    <?php
    if ($data) {
        echo "<div class='w3-row'>";
        foreach ($data as $studname => $studdata) {
            
            $studs = array_pad($studdata['subjects'], 3, ['name'=>'&nbsp;', 'score'=>'&nbsp;']);
                     
            echo "<div class='w3-col w3-card s12 m4 w3-margin-top'>
                 <div class='w3-panel w3-margin w3-padding w3-large w3-center w3-blue-gray'>$studname</div>
                 <table>";
            
//            for ($i=0; $i<3; $i++) {
//                $stud = $studs[$i];
//                echo "
//                     <tr><td><i class='fa fa-trophy' style='color: {$awards[$i]};'></i>&ensp;
//                          {$stud['name']}</td>
//                          <td>{$stud['score']}</td>
//                     </tr>
//                ";
//            } 

            foreach ($studdata['subjects'] as $k => $sub) {
//                if (isset($awards[$stud['rank']])) {
//                    $trophy = "<i class='fa fa-trophy' style='color: {$awards[$stud['rank']]};'></i>";
//                }
//                else $trophy = '&emsp;';
                    echo "<tr><td>" . nameBar($sub['name'], $sub['score']) . "</td>
                              <td>{$sub['score']}</td>
                         </tr>\n";
            }

            echo "</table>\n</div>";
        }
        
    }
    ?>
                
                </div>

</body>
</html>

