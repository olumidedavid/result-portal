<?php
session_start();
require 'db_inc.php';
require 'functions_ba.php';
$pdo = pdoConnect('mabest_biotime');

                                                                                    
################################################################################
#  Check suoeradmin is logged in                                               #
################################################################################
    if (getAdminType($pdo) != 1) {
        header("Location: login.php");
        exit;
    }
                                                                                    
################################################################################
#  Handle AJAX requests                                                        #
################################################################################
    if (isset($_POST['ajax'])) {
        
        if ($_POST['ajax'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO staff_course(staffid, courseid) VALUES (?, ?)");
            $stmt->execute([ $_POST['sid'], $_POST['cid'] ]);
            exit("OK");
        }
        
        elseif ($_POST['ajax'] == 'del') {
            $stmt = $pdo->prepare("DELETE FROM staff_course
                     WHERE staffid = ?
                       AND courseid = ?
                     ");
            $stmt->execute([ $_POST['sid'], $_POST['cid'] ]);
            exit("OK");
        }
        
        else exit("");
    }

################################################################################
#  Build list of staff                                                         #
################################################################################

    $staff = $_GET['staff'] ?? 0;


    $res = $pdo->query("SELECT id
                             , concat(firstname, ' ', lastname) as name
                             , staffnumber
                        FROM staff
                        ORDER BY name
                       ");
    $stafflist = '';
    foreach ($res as $r) {
        $colcls = $r['id'] == $staff ? 'w3-pale-green' : 'w3-light-gray' ;
        $stafflist .= "<li class='$colcls staff-item w3-hover-light-green' data-id='{$r['id']}'><b>{$r['name']}</b><br>{$r['staffnumber']}</li>\n";
    }
################################################################################
#  Build level headings for table                                              #
################################################################################
    $heads = '';
    $res = $pdo->query("SELECT levelname
                        FROM level
                        WHERE id > 2
                       ");
    foreach ($res as $r) {
        $lh = vertical($r['levelname']);
        $heads .= "<th>$lh</th>\n";
    }

################################################################################
#  Build table body                                                            #
################################################################################


    $res = $pdo->prepare("SELECT   sb.id as subject
                                 , sb.subjectname
                                 , l.id as level
                                 , c.id as crsid
                                 , sfc.id as sfcid
                            FROM subject sb
                                 CROSS JOIN level l ON l.id > 2 
                                 JOIN (
                                        SELECT subjectid, count(*) as tot
                                        FROM course
                                        GROUP BY subjectid
                                      ) seq ON sb.id = seq.subjectid
                                 LEFT JOIN course c ON c.levelid = l.id
                                                    AND c.subjectid = sb.id
                                 LEFT JOIN staff_course sfc ON c.id = sfc.courseid
                                                            AND sfc.staffid = ?
                            ORDER BY sb.subjectname, l.id
                            ");
    $res->execute( [$staff] );
    $data = [];
    foreach ($res as $r) {
        if (!isset($data[$r['subject']])) {
            $data[$r['subject']] = [ 'subj'  => $r['subjectname'],
                                     'crses' => []
                                   ];
        }
        $data[$r['subject']]['crses'][$r['level']] = [ 'cid' => $r['crsid'], 'scid' => $r['sfcid'] ];
    }
    $tdata = '';                                                                             #echo '<pre>' . print_r($data, 1) . '</pre>';  exit;
    foreach ($data as $subdata) {
        $tdata .= "<tr><td class='w3-blue-gray' style='text-align: left;'>{$subdata['subj']}</td>\n";
        foreach ($subdata['crses'] as $cdata) {
            $bg = $cdata['cid'] ? '' : 'w3-gray';
            $chk = '';
            if ($cdata['scid']) {
                $bg = 'w3-amber';
                $chk = 'checked';
            }
            if ($cdata['cid']) {
                $tdata .= "<td class='$bg'><input type='checkbox' class='course-cb' data-crs='{$cdata['cid']}' $chk></td>\n";
            }
            else {
                $tdata .= "<td class='$bg'>&nbsp;</td>\n";
            }
        }
        $tdata .= "</tr>\n";
    }
    
    
    function vertical($str) 
    {
        $out = "<svg width='30' height='120' viewBox='0 0 30 120'>
                <text x='21' y='116'  fill='#000' transform='rotate(-90, 21, 116)'>$str</text>
                </svg>
               ";
               return $out;
    }
?>
<!DOCTYPE html>
<html>
<head>
<title>Mabest Academy</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>

$().ready( function() {
    
    $(".staff-item").click( function() {
        let id = $(this).data("id")
        location.href = "?staff="+id
    })
    
    $(".course-cb").click( function() {
        let td = $(this).parent()
        let sid = $("#staff-id").val()
        let cid = $(this).data("crs")
        if ( $(this).is(":checked")) {
            updateStaffCourse('add', sid, cid, td)
            $(td).addClass("w3-amber")
        }
        else {
            updateStaffCourse('del', sid, cid, td)
            $(td).removeClass("w3-amber")
        }
    })
})

function updateStaffCourse(action, sid, cid, td) 
{
    $.post (
        "",
        {"ajax":action, "sid":sid, "cid":cid},
        function(resp) {
            if (resp != 'OK') {
                $(td).removeClass("w3-amber").addClass("w3-red")
            }
        },
        "TEXT"
    )
}
</script>
<style type='text/css'>
    table {
        border-collapse: collapse;
    }
    td {
        text-align: center;
        padding: 4px;
    }

    th:nth-child(5),
    th:nth-child(6),
    th:nth-child(7),
    th:nth-child(11),
    th:nth-child(12),
    th:nth-child(13) {
        background-color: #eee;
    }
    
    td:nth-child(4),
    td:nth-child(7),
    td:nth-child(10) {
        border-right: 3px solid gray;
    }
    
    .staff-item {
        cursor: pointer;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding'>
        <h1>Staff Course Allocation</h1>
    </header>

<div class='w3-row'>
    <div class='w3-col m3'>
        <div class='w3-panel w3-dark-gray'>
            <h3>Staff</h3>
        </div>
        <ul class='w3-ul w3-padding'>
                <?= $stafflist ?>
            </ul>
    </div>
    <div class='w3-col m9'>
        <div class='w3-panel w3-dark-gray'>
            <h3>Subjects &amp; Levels</h3>
        </div>
        <div class='w3-container w3-responsive'>
            <table border='1' >
                <tr>
                    <td>
                        <div class='w3-panel'>
                             Select subjects and levels taught<br>by the selected staff member
                             <input type='hidden' id='staff-id' value='<?=$staff?>'>
                        </div>
                    </td>
                    <?=$heads?>
                </tr>
                
                <?= $tdata ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
