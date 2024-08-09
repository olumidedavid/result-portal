<?php
session_start();
require 'db_inc.php';
require 'functions_ba.php';
$pdo = pdoConnect();
                                                                                      
                                                                                    
################################################################################
#  Check admin is logged in                                                    #
################################################################################
    if (getAdminType($pdo) == 0) {
        header("Location: login.php");
        exit;
    }
                                                                                    
################################################################################
#  Handle AJAX requests                                                        #
################################################################################
    if (isset($_POST['ajax'])) {
        
        if ($_POST['ajax'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO staff_class(staffid, classid) VALUES (?, ?)");
            $stmt->execute([ $_POST['sid'], $_POST['cid'] ]);
            exit("OK");
        }
        
        elseif ($_POST['ajax'] == 'del') {
            $stmt = $pdo->prepare("DELETE FROM staff_class
                     WHERE staffid = ?
                       AND classid = ?
                     ");
            $stmt->execute([ $_POST['sid'], $_POST['cid'] ]);
            exit("OK");
        }
        
        else exit("0");
    }
    
    if (isset($_GET['ajax']))  {
        
        if ($_GET['ajax'] == 'getstaff') {
            exit( getStaff($pdo, $_GET['class']) );
        }
        
        else exit("0");
    }

################################################################################
#  Build list of staff                                                         #
################################################################################
    
    function getStaff($pdo, $class)
    {
        $res = $pdo->prepare("SELECT s.id as sid
                                 , sc.classid as cid
                                 , concat(lastname, ', ', firstname) as name
                            FROM staff s 
                                 LEFT JOIN
                                 staff_class sc ON s.id = sc.staffid
                                                AND classid = ?
                            ORDER BY lastname
                           ");
        $stafflist = '';
        $res->execute([$class]);
        foreach ($res as $r) {
            $colcls = $r['cid'] == $class ? 'w3-pale-green' : 'w3-light-gray' ;
            $chk = $r['cid'] == $class ? 'checked' : '';
            $stafflist .= "<div class='w3-col m3 w3-padding $colcls staff-item w3-border w3-hover-pale-green' >
                               <input type='checkbox' $chk class='staff-chk' data-cid='{$r['cid']}' data-sid='{$r['sid']}' > 
                               {$r['name']}
                           </div>\n";
        }
        return $stafflist;
    }

################################################################################
#  Build list of classes                                                       #
################################################################################
    $classlist = '';
    $stafflist = '';
    
    $res = $pdo->query("SELECT id
                             , classname
                             , coalesce(allocated, 0) as allocated
                        FROM class c 
                             LEFT JOIN (
                                        SELECT classid
                                             , count(*) as allocated
                                        FROM staff_class
                                        GROUP BY classid     
                                       ) alloc ON c.id = alloc.classid
                        ORDER BY SUBSTRING(classname,6)+0, classname
                       ");
    foreach ($res as $r) {
        $clr = $r['allocated'] ? 'w3-blue-gray' : 'w3-sand';
        $checked = $r['allocated'] ? "<span class='w3-right'>&check;</span>" : '';
        $classlist .= "<li class='class-item $clr w3-hover-pale-blue' data-id='{$r['id']}'>
                       {$r['classname']}
                       $checked
                       </li>";
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
<title>Test</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>

$().ready( function() {
    
    $(".class-item").click( function() {
        let id = $(this).data("id")
        $("#classid").val(id)
        $(".class-item").each( function(k,v) {
            if ($(v).hasClass("w3-pale-blue")) {
                $(v).removeClass("w3-pale-blue").addClass("w3-blue-gray");
            }
        })
        $(this).removeClass("w3-blue-gray").addClass("w3-pale-blue");
        $.get(
            "",
            {"ajax":"getstaff", "class":id},
            function(resp) {
                $("#stafflist").html(resp)
                $(".staff-chk").click( function() {
                    let sid = $(this).data("sid") 
                    let cid = $("#classid").val()
                    if ($(this).is(":checked")) {
                        $(this).parent().removeClass("w3-light-gray").addClass("w3-pale-green")
                        $.post(
                            "",
                            {"ajax":"add", "cid":cid, "sid":sid},
                            function(resp) {
                            },
                            "TEXT"
                        )
                    }
                    else {
                        $(this).parent().removeClass("w3-pale-green").addClass("w3-light-gray")
                        $.post(
                            "",
                            {"ajax":"del", "cid":cid, "sid":sid},
                            function(resp) {
                            },
                            "TEXT"
                        )
                    }
                })
            },
            "TEXT"
        )
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
    .class-item {
        cursor: pointer;
    }
    .staff-item {
        margin: 2px;
        max-height: 36px;
        overflow: hidden;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding'>
        <h1>Staff Class Allocation</h1>
    </header>

<div class='w3-row'>
    <div class='w3-col m3'>
        <div class='w3-panel w3-dark-gray'>
            <h3>Class</h3>
        </div>
        <ul class='w3-ul w3-padding'>
                <?= $classlist ?>
            </ul>
    </div>
    <div class='w3-col m9'>
        <input type='hidden' id='classid' value=''>
        <div class='w3-panel w3-dark-gray'>
            <h3>Staff</h3>
        </div>
        <div class='w3-row' id='stafflist'>
             <?=$stafflist?>
        </div>
    </div>
</div>
</body>
</html>
