<?php
    session_start();
    include 'db_inc.php';
    include 'functions_ba.php';
    $pdo = pdoConnect();

    if (!isset($_SESSION['staff_id'])) {
        header("Location: login.php");
        exit;
    }

    $semester = $_GET['semester'] ?? 0;
    $subject = $_GET['subject'] ?? 0;

################################################################################
#  Get lists of grades                                                         #
################################################################################
    $res = $pdo->query("select distinct level_group
                             , case comments 
                                    when 'Ungraded' then 'UN'
                                    else grade
                                    end as grade
                        FROM examgrade
                        ORDER BY level_group, lomark
                        ");
    $results = $res->fetchAll(PDO::FETCH_GROUP);
    $levelgroups = ['Lower School', 'Upper School'];
    foreach ($results as $g => $gdata) {
        $grades[$g] = array_column($gdata, 'grade');
        $gradeheads[$g] = "<th class='la'>{$levelgroups[$g]}</th><th>" . join('</th><th>', $grades[$g]) . "</th></tr>\n";
        $templates[$g] = array_fill_keys($grades[$g], 0);
    }

################################################################################
#  Get grade distribution data                                                 #
################################################################################
    $midend = $_GET['midend'] ?? 'M';
    switch ($midend) {
        case 'M': $res = $pdo->prepare("SELECT levelid > 5 as levelgroup
                                             , classname
                                             , subjectname
                                             , case when score < 40 AND levelid > 5 
                                                    then 'UN'
                                                    else eg.grade
                                                    end as grade
                                             , count(studentclassid) as cnt
                                        FROM (
                                                SELECT subjectname
                                                     , studentclassid
                                                     , cl.classname
                                                     , cl.levelid
                                                     , sum(score*10) as score
                                                FROM class cl 
                                                     JOIN student_class stc ON stc.classid = cl.id
                                                     JOIN result r ON stc.id = r.studentclassid
                                                     JOIN course c ON r.courseid = c.id
                                                     JOIN subject sb ON c.subjectid = sb.id
                                                WHERE  stc.semesterid = ?
                                                       AND exam = 'CA1'
                                                GROUP BY subjectname, studentclassid
                                            ) scores
                                            JOIN examgrade eg ON score BETWEEN lomark AND himark
                                                              AND level_group = (levelid > 5)
                                        GROUP BY levelgroup, classname, subjectname, grade
                                        ");
                  $chkm = 'checked';
                  $chke = '';
                  break;
        default:  $res = $pdo->prepare("SELECT levelid > 5 as levelgroup
                                             , classname
                                             , subjectname
                                             , case when score < 40 AND levelid > 5 
                                                    then 'UN'
                                                    else eg.grade
                                                    end as grade
                                             , count(studentclassid) as cnt
                                        FROM (
                                                SELECT subjectname
                                                     , studentclassid
                                                     , cl.classname
                                                     , cl.levelid
                                                     , sum(score) as score
                                                FROM class cl 
                                                     JOIN student_class stc ON stc.classid = cl.id
                                                     JOIN result r ON stc.id = r.studentclassid
                                                     JOIN course c ON r.courseid = c.id
                                                     JOIN subject sb ON c.subjectid = sb.id
                                                WHERE  stc.semesterid = ?
                                                GROUP BY subjectname, studentclassid
                                            ) scores
                                            JOIN examgrade eg ON score BETWEEN lomark AND himark
                                                              AND level_group = (levelid > 5)
                                        GROUP BY levelgroup, classname, subjectname, grade
                                        ");
                  $chke = 'checked';
                  $chkm = '';
                  break;       
    }

    $res->execute([ $semester ]);
    $data = [];
    foreach ($res as $r) {
        if (!isset($data[$r['levelgroup']][$r['classname']][$r['subjectname']])) {
            $data[$r['levelgroup']][$r['classname']][$r['subjectname']]['grades'] = $templates[$r['levelgroup']];
        }
        $data[$r['levelgroup']][$r['classname']][$r['subjectname']]['grades'][$r['grade']] = $r['cnt'];
    }
//    echo '<pre>' . print_r($data, 1) . '</pre>';
################################################################################
#  Convert data array to table rows for output                                 #
################################################################################
    $tdata = '';
    foreach ($data as $gp => $gdata) {
        $k = $gp==0 ? 8 : 11;
        $tdata .= " <div class='w3-content w3-responsive w3-margin-top'>
                    <table class='w3-margin-top' border='1'>\n" . $gradeheads[$gp];
        foreach ($gdata as $clname => $cdata) {
            $chart = chart($cdata, $templates[$gp]);
            $tdata .= "<tr>
                         <td class='w3-indigo la'>$clname</td>
                         <td colspan='$k' class='w3-black la' style='padding:0'>$chart</td>
                      <tr>\n";
            foreach ($cdata as $subj => $sdata) {
                $tdata .= "<tr><td class='subj'>$subj</td>";
                foreach ($sdata['grades'] as $g => $n) {
                    $tdata .= styledCell($n);
                }
                $tdata .= "</tr>\n";
            }
        }
        $tdata .= "</table></div>\n";
    }

    
################################################################################
#  FUNCTIONS                                                                   #
################################################################################
    function subjectOptions($pdo, $current='')
    {
        $opts = "<option value=''>- select subject -</option>\n";
        $res = $pdo->query("SELECT id
                                 , subjectname
                            FROM subject
                            ORDER BY subjectname     
                           ");
        foreach ($res as $r) {
            $sel = $r['id']==$current ? 'selected' : '';
            $opts .= "<option $sel value='{$r['id']}'>{$r['subjectname']}</option>\n";
        }
        return $opts;
    }
    
    function styledCell($n)
    {
        $alpha = $n > 10 ? 1 : $n/10;
        $fg = $n > 5 ? '#fff' : '#000';
        $bg = "rgba(0, 115, 230, $alpha)";
        if ($n==0) $n = '';
        return "<td class='cell' style='background-color: $bg; color: $fg'>$n</td>";    
    }
    
    function chart($data, $tots)
    {
        $k = count($tots);
        $width = 40*$k;
        $height = 100;
        foreach ($data as $sdata) {
            foreach ($sdata['grades'] as $g => $n) {
                $tots[$g] += $n;
            }
        }
        $grand = array_sum($tots);
        #foreach ($tots as &$tot) $tot = round($tot / $grand * 100);
        $svg = "<svg width='$width' height='$height' viewBox='0 0 $width $height'>\n";
        $n = 0;
        foreach ($tots as $g => $t) {
            $v = round($t / $grand * 100);
            $tx = $n * 40 + 20;
            $x = $n++ * 40 + 5;
            $y = $height - $v;
            $svg .= "<rect x='$x' y='$y' width='30' height='$v' fill='#EC9807'/>\n";
            if ($t > 0) $svg .= "<text x='$tx' y='94' text-anchor='middle' fill='#fff' font-size='11'>$t</text>\n"; 
        }
        $tx = $width/2;
        $svg .= "<text x='$tx' y='12' text-anchor='middle' fill='#fff' font-size='10'>Totals</text>
                 </svg>";
        return $svg;
    }    
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Student Grade Distribution</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
    $(function() {
        
    })
</script>
<style type='text/css'>
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    background-color: #666;
    color: white;
    padding: 8px;
}
td {
    padding: 4px 8px;
    text-align: center;
}
td.cell {
    width: 40px;
}
td.subj {
    text-align: left;
    padding-left: 40px;
}
.la {
    text-align: left;
}
</style>
</head>
<body>
    <header class='w3-container w3-padding w3-margin'>
        <h1><img src='logo1.png' border='0' width='69' height='65' alt='logo'>Student Grade Distribution</h1>
    </header>
    <div class='w3-bar w3-light-gray'>
    <form id='form1'>
        <label class='w3-bar-item' for='semester'>Term</label>
        <select class='w3-input w3-bar-item w3-border ' name='semester' id='semester'>
            <?= semesterOptions($pdo, 1, $semester)?>
        </select>
        
        <span class='w3-bar-item'>
            &emsp;<label><input type='radio' name='midend' value = 'M' <?=$chkm?>> Mid-term</label>
            &emsp;<label><input type='radio' name='midend' value = 'E' <?=$chke?>> End-term</label>
        </span>
        <button class='w3-button w3-bar-item w3-blue-gray w3-margin-left'>Show results</button>
    </form>
    </div>
    
    <?= $tdata ?>
    
</body>
</html>

