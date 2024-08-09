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
#   Process AJAX requests                                                      #
################################################################################
    if (isset ($_GET['ajax'])) {
        if ($_GET['ajax']=='activeclasses') {
            $res = $pdo->prepare("SELECT DISTINCT
                                       cl.id
                                     , cl.classname
                                FROM class cl
                                     JOIN student_class stc ON cl.id = stc.classid
                                     JOIN result r ON stc.id = r.studentclassid
                                WHERE stc.semesterid = ?
                                ORDER BY substring(classname, 6, 2)+0
                               ");
            $res->execute([ $_GET['term'] ]);
            $rows = $res->fetchAll();
            exit (json_encode(array_column($rows, 'classname', 'id')));
        }
        
        else exit("Error");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Mabest Academy</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type='text/javascript'>
    
    $().ready( function() {
        $("#semester").change( function() {
            let term = $(this).val()
            $.get(
                "",
                {"ajax":"activeclasses", "term":term},
                function(resp) {
                    $("#class").html("")
                    $.each(resp, function(k,v) {
                        let opt = $("<option>", {"value":k, "text":v})
                        $("#class").append(opt)
                    })
                },
                "JSON"
            )
        }) 
    })
</script>
</head>
<body>
<div class='w3-content'>
<div class='w3-panel w3-blue-gray'>
    <h1>Create PDF Files</h1>
</div>

<div class='w3-container w3-center'>
    <div class='w3-content w3-light-gray' style='max-width:500px; margin: 50px auto;'>
        <div class='w3-panel w3-padding w3-left-align'>
        <form  method='GET' action='batch_print_results.php' target='_blank'>
            <label for='semester'><b>Term</b></label> 
            <select name='semester' id='semester' class='w3-input '>
                <?= semesterOptions($pdo)?>
            </select>
            <br>
            <label for='class'><b>Class</b></label>
            <select name='class' id='class' class='w3-input '>
                
            </select>
            <br>
            <div class='w3-panel w3-padding w3-center'>
            <button class='w3-button w3-blue' name='result' value='mid'>Mid-Term Results</button>
            
            <button class='w3-button w3-green' name='result' value='end' >End of Term Results</button>

        <!-- alternative to disable endterm reports...
            
            <button class='w3-button w3-green w3-disabled' name='result' value='end' disabled>End of Term Results</button>
        -->


            </div>
        </form>
        </div>
    </div>
</div>
</div>
</body>
</html>