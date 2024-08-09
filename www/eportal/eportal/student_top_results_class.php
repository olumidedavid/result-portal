<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
$pdo = pdoConnect();

$awards = [  '#fac746', '#c0c0c0', '#cd7f32' ];

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

$semester = $_GET['semester'] ?? '';
$class =    $_GET['class'] ?? '';
$midend = $_GET['midend'] ?? 'M';
switch ($midend) {
    case 'M': $res = $pdo->prepare("SELECT cl.classname
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
                                    WHERE stc.semesterid =?
                                            AND exam = 'CA1'
                                            AND cl.id = ?
                                    GROUP BY c.id, sb.id, st.id
                                    ORDER BY substring(classname,6,2)+0 , subjectname, score DESC
                                    ");
              $chkm = 'checked';
              $chke = '';
              break;
    default:  $res = $pdo->prepare("SELECT   sb.subjectname
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
                                    GROUP BY c.id, sb.id, st.id
                                    ORDER BY substring(classname,6,2)+0 , subjectname, score DESC
                                    ");
              $chke = 'checked';
              $chkm = '';
              break;       
}

$res->execute([ $semester, $class ]);

$data = [];
foreach ($res as $r) {
    if (!isset($data[$r['subjectname']])) {
        $data[$r['subjectname']]['students'] = [];
    }
    
    $data[$r['subjectname']]['students'][] = [ 'name' => $r['stname'], 'score' => $r['score']];
}
#echo '<pre>' . print_r($data, 1) . '</pre>'; 

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Student Top Results</title>
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
        <h1><img src='logo1.png' height='65' alt='logo'>Student Top Results</h1>
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
    <div class='w3-container w3-margin-top'>
    <?php
    if ($data) {
        echo "<div class='w3-row'>";
        foreach ($data as $subname => $subdata) {
            
            $studs = array_pad($subdata['students'], 3, ['name'=>'&nbsp;', 'score'=>'&nbsp;']);
                     
            echo "<div class='w3-col w3-card s12 m4 w3-margin-top'>
                 <div class='w3-panel w3-margin w3-padding w3-large w3-center w3-blue-gray'>$subname</div>
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

            foreach ($subdata['students'] as $k => $stud) {
                if ($k < 3) {
                    $trophy = "<i class='fa fa-trophy' style='color: {$awards[$k]};'></i>";
                }
                else $trophy = '&emsp;';
                    echo "
                         <tr><td>$trophy&ensp;
                              {$stud['name']}</td>
                              <td>{$stud['score']}</td>
                         </tr>\n
                    ";
            }

            echo "</table>\n</div>";
        }
        
    }
    ?>
                
                </div>

</body>
</html>
