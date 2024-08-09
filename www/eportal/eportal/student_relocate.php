<?php
session_start();
require 'db_inc.php';
require 'functions_ba.php';
$pdo = pdoConnect();
                                                                                
                                                                                    
################################################################################
#  Check suoeradmin is logged in                                               #
################################################################################
    if (getAdminType($pdo) != 1) {
        header("Location: login.php");
        exit;
    }

                                                                                    
################################################################################
#  Process posted form data                                                    #
################################################################################

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
             try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE student_class stc
                                              JOIN semester sm ON stc.semesterid = sm.id
                                                               AND sm.sessionid = ?
                                           SET stc.classid = ?
                                           WHERE stc.classid = ?  AND stc.studentid = ?   
                                      ");
                foreach ($_POST['student'] as $sid) {
                    $stmt->execute([ $_POST['newsession'], $_POST['newclass'], $_POST['oldclass'], $sid  ]);
                }
                $pdo->commit();
            }
            catch (PDOException $e) {
                $pdo->rollBack();
                throw $e;
            }
            header("Location: ?");
            exit;
    }
                                                                                    
################################################################################
#  Get new session                                                             #
################################################################################

//    $res = $pdo->query("SELECT sm.id, sessionname, sm.date_until
//                        FROM semester sm 
//                             JOIN session ss ON sm.sessionid = ss.id
//                        WHERE sm.date_until < curdate()
//                        ORDER BY sm.date_from DESC
//                        LIMIT 1
//                        ");
//    list($lastsemester, $lastsessname, $lastsemesterend) = $res->fetch(PDO::FETCH_NUM);

    $res = $pdo->query("SELECT sessionid, sessionname, ss.date_from
                        FROM semester sm 
                             JOIN session ss ON sm.sessionid = ss.id
                        WHERE sm.date_until > curdate()
                        ORDER BY sm.date_until
                        LIMIT 1
                        ");
    list($newsession, $newsessname, $newsessstart) = $res->fetch(PDO::FETCH_NUM);

################################################################################
#  Get classes                                                                 #
################################################################################

    $res = $pdo->prepare("SELECT substring(classname, 5, 3) + 0  as yr
                             , cl.id as clid
                             , classname
                             , count(distinct stc.studentid) as studs
                             , sm.id
                        FROM class cl
                             LEFT JOIN (
                                        student_class stc
                                        JOIN semester sm ON stc.semesterid = sm.id
                                                        AND sm.sessionid = ?
                                        ) ON cl.id = stc.classid 
                        GROUP BY yr, cl.id  
                       ");
    $res->execute([$newsession]);
    $classdata = $res->fetchAll(PDO::FETCH_GROUP);                                      #echo '<pre>' . print_r($classdata, 1) . '</pre>';  exit;

################################################################################
#  Build student list                                                          #
################################################################################

    $class = $_GET['class'] ?? -1;
                                                                                
//    if ($class == 0) {
//        $res = $pdo->prepare("SELECT   st.id
//                                     , concat(st.firstname, ' ', st.lastname) as name
//                                     , timestampdiff(YEAR, dob, ?) as age
//                                FROM student st
//                                     LEFT JOIN student_class stc ON st.id = stc.studentid
//                                                                AND stc.semesterid = ?
//                                     LEFT JOIN (
//                                                SELECT studentid
//                                                FROM student_class
//                                                WHERE semesterid = ?+3
//                                                ) done ON done.studentid = st.id
//                                WHERE stc.id IS NULL 
//                                      AND done.studentid IS NULL
//                                      AND st.leavingdate IS NULL
//                                ");
//        $res->execute([ $newsessstart, $lastsemester, $lastsemester ]);
//    }
//    elseif ($class > 0) {
        $res = $pdo->prepare("SELECT DISTINCT
                                       st.id
                                     , concat(st.firstname, ' ', st.lastname) as name
                                FROM student st
                                     JOIN student_class stc ON st.id = stc.studentid
                                                           AND stc.classid = ?
                                     JOIN semester sm ON stc.semesterid = sm.id
                                                           AND sm.sessionid = ?
                              --       LEFT JOIN (
                              --                  SELECT studentid
                              --                  FROM student_class
                              --                  WHERE semesterid = ?+3
                              --                  ) done ON done.studentid = st.id
                                WHERE -- done.studentid IS NULL AND 
                                      st.leavingdate IS NULL
                                ");
        $res->execute([ $class, $newsession ]);
//    }
    $studlist = "<table class='w3-table-all'>
                   <tr class='w3-blue-gray'><th>Name</th><th>Select</th></tr>
                   ";
    foreach ($res as $r) {
        $studlist .= "<tr><td>{$r['name']}</td>
                          <td class='w3-center'>
                              <input type='checkbox' class='stud-cb' name='student[]' value='{$r['id']}' >
                          </td>
                      </tr>\n";
    }
    $studlist .= "</table>\n";

################################################################################
#  Function to build class lists                                               #
################################################################################
function buildClassList(&$classes, $list, $class)
{
    switch ($list) {
        case 1: $cls = 'w3-pale-green w3-hover-light-green class1-item';
                break;    
        case 2: $cls = 'w3-pale-blue w3-hover-light-blue class2-item';
                break;    
    }
    $out = '';
    $flag = '';
    foreach ($classes as $year)  {
        $out .= "<div class='w3-row w3-margin-bottom'>\n";
        foreach ($year as $c) {
            if ($list == 1) $flag = $c['clid'] == $class  ? 'w3-bottombar' : '';
            
            
            $out .= "<div class='w3-col w3-third w3-border w3-padding $cls $flag' data-id='{$c['clid']}'>
                         {$c['classname']}
                     </div>";
        }
        
        $out .= "</div>\n";
    }
    return $out;
}


?>
<!DOCTYPE html>
<html>
<head>
<title>Student Relocation</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>

$().ready( function() {
    
    $(".class1-item").click( function() {
        let clid = $(this).data("id")
        //$("#oldclass").val(clid)
        location.href = "?class=" + clid
    })
    
    $(".class2-item").click( function() {
        let clid = $(this).data("id")
        $("#newclass").val(clid)
        $("#form1").submit()
    })
    
    $(".stud-cb").click( function() {
        if ($(this).is(":checked")) {
            $(this).parent().addClass("w3-amber")
        }
        else {
            $(this).parent().removeClass("w3-amber")
        }
    })
})

</script>
<style type='text/css'>
    
    td:nth-child(4),
    td:nth-child(7),
    td:nth-child(10) {
        border-right: 3px solid gray;
    }
    
    .class1-item,
    .class2-item {
        cursor: pointer;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding'>
        <h1>Student Relocation</h1>
    </header>


<div class='w3-row'>
    <div class='w3-col m4'>
        <div class='w3-panel w3-dark-gray'>
            <h3>Current Class<br>(<?=$newsessname?>)</h3>
        </div>
        <div class='w3-container w3-padding'>
        <!--    <div class='w3-row w3-margin-bottom'>
                <div class='w3-col w3-border w3-light-gray w3-hover-light-green class1-item w3-padding' data-id='0'>
                     Not yet assigned
                </div>
            </div>         -->
            <?= buildClassList($classdata, 1, $class) ?>
         </div>
    </div>
    
    <div class='w3-col m4'>
        <div class='w3-panel w3-dark-gray'>
            <h3>Students<br>&nbsp;</h3>
        </div>
        <div class='w3-container w3-responsive'>
            <form method='POST' id='form1'>
                <input type='hidden' name='newsession' value='<?=$newsession?>'>
                <input type='hidden' id='newclass' name='newclass' value='0'>
                <input type='hidden' id='oldclass' name='oldclass' value='<?=$class?>'>
                <?=$studlist?>
            </form>
        </div>
    </div>
    
    <div class='w3-col m4'>
        <div class='w3-panel w3-dark-gray'>
            <h3>New Class<br>(<?=$newsessname?>)</h3>
        </div>
        
        <div class='w3-container w3-padding'>
            <?= buildClassList($classdata, 2, $class) ?>
        <!--    <div class='w3-row w3-margin-bottom'>
                <div class='w3-col w3-border w3-padding w3-pale-blue w3-hover-light-blue class2-item' data-id='0'>
                     No longer assigned
                </div>
            </div>       -->
         </div>
    </div>
</div>
</body>
</html>
