<?php

const GRADE_COLOUR = [  'A*' => '#54BC54',
                        'A'  => '#54BC54',
                        'A1'  => '#54BC54',
                        'B+' => '#006EFC',
                        'B'  => '#006EFC',
                        'B2'  => '#006EFC',
                        'B3'  => '#006EFC',
                        'C'  => '#006EFC',
                        'C4'  => '#006EFC',
                        'C5'  => '#006EFC',
                        'C6'  => '#006EFC',
                        'D'  => '#88DDFF',
                        'D7'  => '#88DDFF',
                        'E'  => '#88DDFF',
                        'E8'  => '#88DDFF',
                        'F'  => '#E02222', 
                        'F9'  => '#E02222' 
                     ];

const SUBJ_CODES = [    '1'  => 'ENG',
                        '2'  => 'MAT',
                        '3'  => 'BIO',
                        '4'  => 'PHY',
                        '5'  => 'CHE',
                        '6'  => 'GEO',
                        '7'  => 'FMA',
                        '8'  => 'AGR',
                        '9'  => 'ECO',
                        '10' => 'HOM',
                        '11' => 'TEC',
                        '12' => 'YOR',
                        '13' => 'FRC',
                        '14' => 'CRS',
                        '15' => 'BST',
                        '16' => 'HIS',
                        '17' => 'GOV',
                        '18' => 'CIV',
                        '19' => 'BUS',
                        '20' => 'CPS',
                        '21' => 'CRK',
                        '22' => 'CCA',
                        '23' => 'NVE',
                        '24' => 'PRE',
                        '25' => 'DAT',
                        '26' => 'LIT', 
                        '27' => 'CCP', 
                        '28' => 'HUS',
                        '29' => 'FIN', 
                        '30' => 'COM',
                        '31' => 'DYE', 
                        '32' => 'ICG' 
                   ];
                   
function getAdminType($pdo)
{
    if (!isset($_SESSION['staff_id'])) {
        return 0;
    }
    $res = $pdo->prepare("SELECT admintypeid
                          FROM admin
                          WHERE staffid = ?
                         ");
    $res->execute([ $_SESSION['staff_id']]);
    return $res->fetchColumn();
}
                     
function semesterOptions($db, $currsess=0, $current='')
{
    $opts = "<option value=''>- select term -</option>\n";
    $where = $currsess ? "WHERE curdate() BETWEEN s.date_from AND s.date_until" : '';
    $res = $db->query("SELECT s.sessionName
                             , t.id
                             , t.semesterName
                     --        , curdate() BETWEEN t.date_from AND t.date_until as curr
                        FROM semester t
                             JOIN
                             session s ON t.sessionid = s.id
                     --   $where
                        ORDER BY s.date_from DESC, t.date_from     
                       ");
    $prevsess = '';
    foreach ($res as $r) {
        if ($current) {
            $sel = $r['id']==$current ? 'selected' : '';
        }
        else {
            $sel = '';
        }
        if ($r['sessionName'] != $prevsess) {
            if ($prevsess) {
                $opts .= "</optgroup>\n";
            }
            $opts .= "<optgroup label='{$r['sessionName']}'>\n";
            $prevsess = $r['sessionName'];
        }
        $ss = substr($r['sessionName'], 0, 5);
        $ss .= substr($r['sessionName'], -2);
        $opts .= "<option $sel value='{$r['id']}'>{$r['semesterName']}&emsp;$ss</option>\n";
    }
    return $opts;
}

function sessionOptions($pdo, $current=0)
{
    $res = $pdo->query("SELECT ss.sessionname
                         , ss.id as sessionid
                         , curdate() BETWEEN date_from AND date_until as curr
                    FROM session ss
                    ");
    $opts = "<option value=''>- select session -</option>\n";
    foreach ($res as $r) {
        if ($current) {
            $sel = $r['sessionid'] == $current ? 'selected' : '';
        }
        else {
            $sel = $r['curr'] ? 'selected' : '';
        }
        $opts .= "<option $sel value='{$r['sessionid']}'>{$r['sessionname']}</option>\n";
    }
    return $opts;
}

function classOptions($pdo, $session, $staff, $current='')
{
    $params = [ $session ];
    $extra = '';
    if (!getAdminType($pdo)) {
        $extra = 'AND s.id = ?';
        $params[] = $staff;
    }
    
    $res = $pdo->prepare("SELECT DISTINCT
                                   cl.id
                                 , cl.classname
                            FROM staff s 
                                 JOIN staff_course sc on sc.staffid = s.id
                                 JOIN course c ON sc.courseid = c.id
                                 JOIN level l ON c.levelid = l.id
                                 JOIN class cl ON cl.levelid = l.id
                                 JOIN student_class stc ON stc.classid = cl.id
                                 JOIN semester sm ON stc.semesterid = sm.id
                            WHERE sm.sessionid = ?
                                  $extra
                            ORDER BY c.levelid, cl.classname");
    $res->execute($params);
    $opts = "<option value=''>- select class -</option>\n";
    foreach ($res as $r) {
        $sel = $r['id']==$current ? 'selected' : '';
        $opts .= "<option $sel value='{$r['id']}'>{$r['classname']}</option>\n";
    }
    return $opts;
}

function studentOptions($pdo, $semester, $class, $staff, $current='')
{
    $params = [ $semester, $class ];
    $extra = '';
    if (!getAdminType($pdo)) {
        $extra = 'AND sc.staffid = ?';
        $params[] = $staff;
    }
    
    $res = $pdo->prepare("SELECT DISTINCT
                                           s.id
                                         , concat(s.firstname, ' ', s.lastname) as sname
                                    FROM class cl
                                         JOIN level l ON cl.levelid = l.id
                                         JOIN course c ON l.id = c.levelid
                                         JOIN staff_course sc ON sc.courseid = c.id
                                         JOIN student_class stc ON stc.classid = cl.id
                                         JOIN  student s ON stc.studentid = s.id
                                         JOIN semester sm ON stc.semesterid = sm.id
                                    WHERE sm.id = ?
                                          AND cl.id = ?
                                          $extra  
                                    ORDER BY s.id"
                                    );
    $res->execute($params);
    $opts = "<option value=''>-select student -</option>\n";
    foreach ($res as $r) {
         $sel = $r['id']==$current ? 'selected' : '';
        $opts .= "<option $sel value='{$r['id']}'>{$r['sname']}</option>\n";
    }
    return $opts;
}

function levelOptions($pdo, $current='')
{
    $opts = "<option value=''>- select level -</option>\n";
    $res = $pdo->query("SELECT id
                               , levelname
                          FROM level l
                          where id > 2
                          ORDER BY substring(levelname,5,3)+0 , id    
                         ");
    
    foreach ($res as $r) {
        $sel = $r['id'] == $current ? 'selected' : '';
        $opts .= "<option $sel value='{$r['id']}'>{$r['levelname']}</option>\n";
    }
    return $opts ;
}

function getGradeComment($pdo, $score, $level)
{
    $res = $pdo->prepare("SELECT grade, comments
                          FROM examgrade
                          WHERE ? BETWEEN lomark AND himark
                            AND level_group = (? > 5)               -- Level 9 is #5
                         ");
    $res->execute([$score, $level]);
    return $res->fetch(PDO::FETCH_NUM);
}

function getEOYComments($pdo, $stud, $sess)
{
    $res = $pdo->prepare("SELECT type
                               , comments
                               , stc.id as stcid
                          FROM student_class stc
                               JOIN eot_comment c ON c.studentclassid = stc.id
                               JOIN semester sm ON stc.semesterid = sm.id
                          WHERE stc.studentid = ? 
                                AND sm.sessionid = ? 
                                AND sm.semestername LIKE '3%'     
                         ");
    $res->execute( [ $stud, $sess ] );
    $comments = ['teacher' => '', 'head' => ''];
    $comdata = $res->fetchAll();
    if ($comdata) {
        $comments = array_column($comdata, 'comments', 'type');                     
    }
    return $comments;
}

function getEOTComments($pdo, $stud, $semester)
{
    $res = $pdo->prepare("SELECT type
                               , comments
                               , stc.id as stcid
                          FROM student_class stc
                               JOIN eot_comment c ON c.studentclassid = stc.id
                          WHERE stc.studentid = ? 
                                AND stc.semesterid = ?     
                         ");
    $res->execute( [ $stud, $semester ] );
    $comments = ['teacher' => '', 'head' => ''];
    $comdata = $res->fetchAll();
    if ($comdata) {
        $comments = array_column($comdata, 'comments', 'type');                     
    }
    return $comments;
}

function classAverageScores($pdo, $class, $session, $term)
{
    $res = $pdo->prepare("SELECT c.subjectid
                                 , round(avg( CASE exam
                                                  WHEN 'Exam' THEN score*100/70
                                                  ELSE score * 10
                                                  END
                                             )) as ave
                            FROM result r
                                 JOIN course c ON r.courseid = c.id 
                                 JOIN student_class stc ON r.studentclassid = stc.id
                                 JOIN semester sm ON stc.semesterid = sm.id
                                 JOIN session ss ON sm.sessionid = ss.id
                            WHERE 
                                  stc.classid = ?
                                  AND sessionid = ? 
                                  AND semestername+0 <= ? 
                            GROUP BY c.subjectid
                            ");
    $res->execute([$class, $session, $term]);
    $result = $res->fetchAll();
    return array_column($result, 'ave', 'subjectid');
}

function getGradeList($pdo, $level)
{
    $res = $pdo->query("SELECT GROUP_CONCAT( grade, concat('&nbsp;(',comments,')'), '&nbsp;', concat(lomark,'&nbsp;-&nbsp;',himark)
                               ORDER BY id SEPARATOR ', ')
                        FROM examgrade
                        WHERE level_group = ($level > 5)
                        ");
    return  $res->fetchColumn();
}
