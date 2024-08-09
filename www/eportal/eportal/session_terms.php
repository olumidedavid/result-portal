<?php
session_start();
include 'db_inc.php';
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
# PROCESS AJAX REQUESTS                                                        #
################################################################################
    if (isset($_GET['ajax'])) {
        if ($_GET['ajax'] == 'getsess') {
            $res = $pdo->prepare("SELECT sessionname as sess_name
                                       , date_from as sess_from
                                       , date_until as sess_until
                                       , id as ssid
                                  FROM session
                                  WHERE id = ?     
                                 ");
            $res->execute([ $_GET['ssid'] ]);
            exit(json_encode($res->fetch()));
        }
        
        if ($_GET['ajax'] == 'getterm') {
            $res = $pdo->prepare("SELECT semestername as term_name
                                       , date_from as term_from
                                       , date_until as term_until
                                       , id as smid
                                  FROM semester
                                  WHERE id = ?     
                                 ");
            $res->execute([ $_GET['smid'] ]);
            exit(json_encode($res->fetch()));
        }
        
        elseif ($_GET['ajax'] == 'newsess') {
            $res = $pdo->query("SELECT MAX(sessionname) 
                                FROM session
                               ");
            list($y1, $y2) = explode('/', $res->fetchColumn());
            $y3 = $y2 + 1;
            $resp = [ 'nssid'      => 0,
                      'nsess_from' => "{$y2}-08-01",
                      'nsess_until' => "{$y3}-07-31",
                      'nsess_name' => "{$y2}/{$y3}"
                    ];
            exit(json_encode($resp));
        }
        
        elseif ($_GET['ajax'] == 'delsess') {
            $stmt = $pdo->prepare("DELETE sm, ss
                                    FROM session ss
                                         LEFT JOIN semester sm ON ss.id = sm.sessionid
                                    WHERE ss.id = ?
                                   ");
            $stmt->execute([ $_GET['ssid'] ]);
            exit('1');
        }
        
        else exit('0');
    }


################################################################################
# PROCESS POST DATA                                                            #
################################################################################
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        echo '<pre>' . print_r($_POST, 1) . '</pre>';
        if ($_POST['formtype'] == 'newsession')  {
            $stmt1 = $pdo->prepare("INSERT INTO session (sessionName, date_from, date_until)
                                    VALUES (?, ?, ?)
                                   ");
            $stmt2 = $pdo->prepare("INSERT INTO semester (semesterName, sessionid, date_from, date_until)
                                    VALUES (?, ?, ?, ?)
                                   ");
            try {
                $pdo->beginTransaction();
                $stmt1->execute([ $_POST['sess_name'], $_POST['sess_from'], $_POST['sess_until'] ]);
                $sid = $pdo->lastInsertId();
                foreach ($_POST['terms'] as $tname => $dates) {
                    $stmt2->execute([ $tname, $sid, $dates['from'], $dates['until'] ]);
                }
                $pdo->commit();                  
            }
            catch (PDOException $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        
        elseif ($_POST['formtype'] == 'editsession') {
            $stmt = $pdo->prepare("UPDATE session
                                     SET sessionname = ?
                                       , date_from = ?
                                       , date_until = ?
                                   WHERE id = ?    
                                  ");
            $stmt->execute([ $_POST['sess_name'], $_POST['sess_from'], $_POST['sess_until'], $_POST['ssid'] ]);
        }
        
        elseif ($_POST['formtype'] == 'editterm') {
            $stmt = $pdo->prepare("UPDATE semester
                                     SET date_from = ?
                                       , date_until = ?
                                   WHERE id = ?    
                                  ");
            $stmt->execute([ $_POST['term_from'], $_POST['term_until'], $_POST['smid'] ]);
        }
        header("Refresh: 0");
        exit;
    }


################################################################################
# MAIN PROCESSING                                                              #
################################################################################

    $res = $pdo->query("SELECT 
                               ss.id as ssid
                             , ss.sessionName
                             , ss.date_from as ssfrom
                             , ss.date_until as ssuntil
                             , date_format(ss.date_from, '%a/%m')
                             , sm.id as smid
                             , sm.semesterName
                             , sm.date_from as smfrom
                             , sm.date_until as smuntil
                             , date_format(ss.date_from, '%e/%c/%y') as ssf
                             , date_format(ss.date_until, '%e/%c/%y') as ssu
                             , date_format(sm.date_from, '%e/%c') as smf
                             , date_format(sm.date_until, '%e/%c') as smu
                             , datediff(sm.date_from, ss.date_from) as df
                             , datediff(sm.date_until, ss.date_from) as du
                             , coalesce(students, 0) as students
                        FROM session ss 
                             LEFT JOIN (
                                    SELECT ss.id as ssid
                                         , count(sm.id) as students
                                    FROM session ss 
                                         JOIN semester sm ON ss.id = sm.sessionid
                                         JOIN student_class stc ON stc.semesterid = sm.id
                                  ) used ON ss.id = used.ssid
                             LEFT JOIN
                             semester sm ON sm.sessionid = ss.id
                        ORDER BY ssfrom DESC, smfrom
                        ");
    $data = [];
    foreach ($res as $r) {
        if (!isset($data[ $r['ssid'] ])) {
            $data[ $r['ssid'] ] = [ 'sessname' => $r['sessionName'],
                                    'ssfrom'   => $r['ssfrom'],
                                    'ssuntil'  => $r['ssuntil'],
                                    'ssf'      => $r['ssf'],
                                    'ssu'      => $r['ssu'],
                                    'students' => $r['students'],
                                    'terms'    => [  '1st Term' => [ 'smid'    => 0,
                                                                     'smfrom'  => '',
                                                                     'smuntil' => '',
                                                                     'smf'     => '?',
                                                                     'smu'     => '?',
                                                                     'df'      => 60,
                                                                     'du'      => 180
                                                                   ],
                                                     '2nd Term' => [ 'smid'    => 0,
                                                                     'smfrom'  => '',
                                                                     'smuntil' => '',
                                                                     'smf'     => '?',
                                                                     'smu'     => '?',
                                                                     'df'      => 300,
                                                                     'du'      => 400
                                                                   ],
                                                     '3rd Term' => [ 'smid'    => 0,
                                                                     'smfrom'  => '',
                                                                     'smuntil' => '',
                                                                     'smf'     => '?',
                                                                     'smu'     => '?',
                                                                     'df'      => 540,
                                                                     'du'      => 660
                                                                   ]
                                                  ]
                                  ];
        }
        if ($r['smid'])
            $data[ $r['ssid'] ]['terms'][$r['semesterName']] =  [   'smid'  => $r['smid'],
                                                                    'smfrom'  => $r['smfrom'],
                                                                    'smuntil' => $r['smuntil'],
                                                                    'smf'     => $r['smf'],
                                                                    'smu'     => $r['smu'],
                                                                    'df'      => $r['df']*2,
                                                                    'du'      => $r['du']*2
                                                                ];
    }

    #echo '<pre>' . print_r($data, 1) . '</pre>';



################################################################################
# FUNCTIONS                                                                    #
################################################################################

    function sessChart($ssid, $sd)
    {
        $sessDel = $sd['students'] == 0 ?
                        "<span class='w3-button w3-text-red sess-del' 
                            data-sess='$ssid' title='Delete session (only when no registered students)'>
                             &times; </span>"
                        : "";
        $out = "<div class='w3-content w3-margin-top w3-responsive w3-border-top' style='width: 735px'>\n
                <h2>{$sd['sessname']} $sessDel</h2>
                <div>
                    <span class='w3-left'>{$sd['ssf']}</span>
                    <span class='w3-right'>{$sd['ssu']}</span>
                </div><br>
                <svg width='735' height='80' viewBox='0 0 735 80'>
                <rect x='0' y='20' width='732' height='60' fill='#F0F0F0' stroke='#666'/>
                ";
        #
        #  months
        #
        list($yr, $y2) = explode('/', $sd['sessname']);
        $dt1 = new DateTime($sd['ssfrom']);
        $dt2 = new DateTime($sd['ssuntil']);
        $dp = new DatePeriod($dt1, new DateInterval('P1M'), $dt2);
        $x = 0;
        $n = 0;
        foreach ($dp as $d) {
            $label = $d->format('M');
            if ( in_array($d->format('n'), [1,7,8]) ) $label .= ' ' . $d->format('Y');
            $days = $d->format('t')*2;
            $tx = $x+2;
            $ty = 15;
            $fl = $n%2 ? '#999' : '#07ABF6';
            $out .= "<rect x='$x' y='0' width='$days' height='20' fill='$fl' stroke='#fff' />\n";
            $out .= "<text x='$tx' y='$ty' fill='#fff' style='font-size:9pt'>$label</text>\n";
            ++$n;
            $x += $days;
        }
            $out .= "<rect x='0' y='0' width='735' height='20' fill='#fff' fill-opacity='0' class='sess-item' data-sess='$ssid'>\n
                         <title>Edit session {$sd['sessname']}</title>\n
                     </rect>\n";
        #
        #  terms
        #
        foreach ($sd['terms'] as $tname => $td) {
            $twid = $td['du'] - $td['df'];
            if ($td['smf'] == '?') {
                $out .= "<rect x='{$td['df']}' y='30' width='$twid' height='25' fill='#fff' />" ;
            }
            else {
                $out .= "<rect x='{$td['df']}' y='30' width='$twid' height='25' fill='#585858' stroke='#fff' class='term-item' 
                         data-term='{$td['smid']}' data-sess='$ssid' data-name='$tname'>
                             <title>Edit $tname</title>
                         </rect>
                        " ;
            }
            $ty = 50;
            $tx = $td['df'] + 2;
            $out .= "<text x='$tx' y='$ty' fill='#fff'>{$td['smf']}</text>\n";
            $ty = 50;
            $tx = $td['du'] - 2;
            $out .= "<text x='$tx' y='$ty' text-anchor='end' fill='#fff'>{$td['smu']}</text>\n";
            $ty = 74;
            $tx = ($td['df']+ $td['du'])/2;
            $out .= "<text x='$tx' y='$ty' text-anchor='middle' fill='#000'>{$tname}</text>\n";
        }
        
        
        $out .= "</svg></div>\n";
        return $out;
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Sessions &amp; Terms</title>
<meta charset="utf-8">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<script type='text/javascript'>
$(function() {
    $(".term-item").click( function() {
        let smid = $(this).data("term")
        $.get(
            "",
            {"ajax":"getterm", "smid":smid},
            function(resp) {
                $.each(resp, function(k,v) {
                    $("#"+k).val(v)
                }),
                $("#term-modal").show()
            },
            "JSON"
        )
    })
    
    $(".sess-item").click( function() {
        let ssid = $(this).data("sess")
        $.get(
            "",
            {"ajax":"getsess", "ssid":ssid},
            function(resp) {
                $.each(resp, function(k,v) {
                    $("#"+k).val(v)
                }),
                $("#session-modal").show()
            },
            "JSON"
        )
    })
    
    $("#addSession").click( function() {
        $.get(
            "",
            {"ajax":"newsess"},
            function(resp) {
                $.each(resp, function(k,v) {
                    $("#"+k).val(v)
                }),
                $("#new-session-modal").show()
            },
            "JSON"
        )
    })
    
    $("#term-close").click( function() {
        $("#term-modal").hide();
    })
    
    $("#sess-close").click( function() {
        $("#session-modal").hide();
    })
    
    $("#nsess-close").click( function() {
        $("#new-session-modal").hide();
    })
    
    $(".sess-del").click( function() {
        let ssid = $(this).data("sess")
        $.get(
            "",
            {"ajax":"delsess", "ssid":ssid},
            function(resp) {
                location.href = "";
            },
            "TEXT"
        )
    })
    
    $("#helpme").click(function() {
        $("#help-modal").show()
    })
})
</script>
<style type='text/css'>
    p {
        margin-left: 48px;
    }
    .sess-item {
        cursor: pointer;
    }
    .term-item {
        cursor: pointer;
    }
    #helpme {
        cursor: pointer;
    }
</style>
</head>
<body>
    <header class='w3-container w3-padding w3-bottombar'>
        <h1>Sessions &amp; Terms
            <div class='w3-badge w3-brown w3-right w3-medium' title='Help' id='helpme'>?</div>
        </h1>
        
    </header>
   
    <div class='w3-content w3-margin-top w3-responsive'>
        <span class='w3-button w3-indigo w3-margin' id='addSession' >&plus;</span>
        Add new session
    
    <?php
        foreach ($data as $ssid => $ssdata) {
            echo sessChart($ssid, $ssdata);
        }
    ?>
    </div>
<!-- -------------------------------------------------------------------------------------------------------
#
#  MODAL WINDOWS
#
#---------------------------------------------------------------------------------------------------------->

<!-- EDIT TERM -->    
    <div class='w3-modal' id='term-modal'>
        <div class='w3-modal-content w3-padding w3-black'>
            <h2>Term Data
            <span class='w3-button w3-right' id="term-close" title='Close'>&times;</span></h2>
            <form method='post'>
            <label>Term name</label>
            <input type='text' class='w3-input w3-border' name='term_name' id='term_name' style='background-color:black; color:white' readonly>
            <label>Term start date</label>
            <input type='date' class='w3-input' name='term_from' id='term_from' >
            <label>Term end date</label>
            <input type='date' class='w3-input' name='term_until' id='term_until' >
            <br>
            <input type='hidden' name='smid' id='smid' value='' >
            <input type='hidden' name='formtype' value='editterm'>
            <button class='w3-button w3-blue'>Save</button>
            </form>
        </div>
    </div>
    
<!-- EDIT SESSION -->    
    <div class='w3-modal' id='session-modal'>
        <div class='w3-modal-content w3-padding w3-black'>
            <h2>Session Data
            <span class='w3-button w3-right' id="sess-close" title='Close'>&times;</span></h2>
            <form method='post'>
            <label>Session name</label>
            <input type='text' class='w3-input' name='sess_name' id='sess_name' placeholder='eg 2001/2002' >
            <label>Session start date</label>
            <input type='date' class='w3-input' name='sess_from' id='sess_from' >
            <label>Session end date</label>
            <input type='date' class='w3-input' name='sess_until' id='sess_until' >
            <br>
            <input type='hidden' name='ssid' id='ssid' value='' >
            <input type='hidden' name='formtype' value='editsession'>
            <button class='w3-button w3-blue'>Save</button>
            </form>
        </div>
    </div>
    
<!-- ADD NEW SESSION -->    
    <div class='w3-modal' id='new-session-modal'>
        <div class='w3-modal-content w3-padding w3-black'>
            <h2>New Session
            <span class='w3-button w3-right' id="nsess-close" title='Close'>&times;</span></h2>
            <form method='post'>
            <div class='w3-row w3-blue-gray w3-center w3-padding'>
                <div class='w3-col m4'>
                    Session name<br>
                    <input type='text' name='sess_name' id='nsess_name' >
                </div>
                <div class='w3-col m4'>
                    Start date<br>
                    <input type='date' name='sess_from' id='nsess_from' >
                </div>
                <div class='w3-col m4'>
                    End date<br>
                    <input type='date' name='sess_until' id='nsess_until' >
                </div>
            </div>
            
            <h3>Terms</h3>
            
            <div class='w3-row  w3-padding'>
                <div class='w3-col m4'>
                    1st Term
                </div>
                <div class='w3-col m4 w3-center'>
                    <input type='date' name='terms[1st Term][from]' >
                </div>
                <div class='w3-col m4 w3-center'>
                    <input type='date' name='terms[1st Term][until]' >
                </div>
            </div>
            
            <div class='w3-row  w3-padding'>
                <div class='w3-col m4'>
                    2nd Term
                </div>
                <div class='w3-col m4 w3-center'>
                    <input type='date' name='terms[2nd Term][from]' >
                </div>
                <div class='w3-col m4 w3-center'>
                    <input type='date' name='terms[2nd Term][until]' >
                </div>
            </div>
            
            <div class='w3-row  w3-padding'>
                <div class='w3-col m4'>
                    3rd Term
                </div>
                <div class='w3-col m4 w3-center'>
                    <input type='date' name='terms[3rd Term][from]' >
                </div>
                <div class='w3-col m4 w3-center'>
                    <input type='date' name='terms[3rd Term][until]' >
                </div>
            </div>
            
            <input type='hidden' name='ssid' id='nssid' value='' >
            <input type='hidden' name='formtype' value='newsession'>
            <button class='w3-button w3-blue'>Save</button>
            </form>
        </div>
    </div>
    
<!-- HELP -->
    <div class='w3-modal' id='help-modal'>
        <div class='w3-modal-content w3-padding w3-brown w3-text-yellow'>
            <h3>Notes on Use
            <span class='w3-button w3-right' id="sess-close" title='Close' onclick='$("#help-modal").hide()'>&times;</span></h3>
            <hr>
            
            <h4>New Sessions</h4>
            <p>Click the + button to create a new session. By default, sessions start on 1st August and finish on 31st July. This maximises the time that you have
            prior to the start of each new school year to register all the students in their new classes</p>
            <p>Enter the actual start and end dates for each of the three terms</p>
            
            <h4>Edit Session</h4>
            <p>If you need to edit a session's name or dates, click on the header bar showing the months of the school year,</p>
            
            <h4>Edit Term</h4>
            <p>Click on the term to edit its dates.</p>
            
            <h4>Delete session</h4>
            <p>To remove a session and its terms, click the X button next to the session name</p>
            <p>This is only permitted for those sessions where no students are registered. (i.e. there are no student_class records 
            for any of the semesters within the session).</p>
        </div>
    </div>

</body>
</html>
