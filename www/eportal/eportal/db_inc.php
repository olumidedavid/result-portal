<?php
define("HOST",'localhost');
define("USERNAME",'root');
define("PASSWORD",'');
define("DATABASE", 'self');                         // default database name


function pdoConnect($dbname=DATABASE) 
{
    $db = new PDO("mysql:host=".HOST.";dbname=$dbname;charset=utf8",USERNAME,PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::MYSQL_ATTR_LOCAL_INFILE, true);
    return $db;
}

function myConnect($database=DATABASE)
{
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT); 
    $db = mysqli_connect(HOST,USERNAME,PASSWORD,$database);
    $db->set_charset('utf8');
    return $db;
}

/*

                                              #---------------------------------------------------------------------------------------------+
                                              #                                    db_inc.php                                               |
                                              #---------------------------------------------------------------------------------------------+
                                              #  const HOST     = 'localhost';                                                              |
                                              #  const USERNAME = '????';                                                                   |
                                              #  const PASSWORD = '????';                                                                   |
                                              #  const DATABASE = 'test');               // default db                                      |
                                              #                                                                                             |
require 'db_inc.php';      <----------------- #  function pdoConnect($dbname=DATABASE)                                                      |
                                              #  {                                                                                          |
$db = pdoConnect();                           #      $db = new PDO("mysql:host=".HOST.";dbname=$dbname;charset=utf8",USERNAME,PASSWORD);    |
                                              #      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);                          |
                                              #      $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);                     |
                                              #      $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);                                  |
                                              #      return $db;                                                                            |
                                              #  }                                                                                          |
                                              #                                                                                             |
                                              #  function myConnect($database=DATABASE)                                                     |
                                              #  {                                                                                          |
                                              #      mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);                               |
                                              #      $db = mysqli_connect(HOST,USERNAME,PASSWORD,$database);                                |
                                              #      $db->set_charset('utf8');                                                              |
                                              #      return $db;                                                                            |
                                              #  }                                                                                          |
                                              #                                                                                             |
                                              #                                                                                             |
                                              #---------------------------------------------------------------------------------------------+
*/

function daterScaper($arr)
{
    return array_map( function($v) {
                        return array_map('_he', $v);
                        }, 
                        $arr);
}


function _he($raw_input, $encoding='utf-8')
{
    return htmlspecialchars($raw_input, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $encoding);
}

function echho ($output)
{
    echo _he($output);
}

function pr($arr)
{
    echo '<pre>', print_r($arr,1), '</pre>';
}

function objectToArray( $object )
{
    return json_decode(json_encode($object), 1);
}

function query2HTMLtable($db, $sql)
{
    $output = "<table border='1' cellpadding='2' style='border-collapse:collapse'>\n";
    // Query the database
    $result = $db->query($sql);
    // check for errors
    if (!$result) return ("$db->error <pre>$sql</pre>");
    if ($result->num_rows == 0) return "No matching records";
    // get the first row and display headings
    $row = $result->fetch_assoc();
    $output .= "<tr><th>" . join('</th><th>', array_keys($row)) . "</th></tr>\n";
    
    // display the data
    do {
       $output .= "<tr><td>" . join('</td><td>', $row) . "</td></tr>\n"; 
    } while ($row = $result->fetch_assoc());
    $output .= "</table>\n";
    return $output;
}

function pdo2html(PDO $pdo, $sql, $params=[])
{
    $res = $pdo->prepare($sql);
    $res->execute($params);
    $data = $res->fetch();
    if ($data) {
        $out = "<table border='1'>\n";
        $heads = array_keys($data);
        $out .= "<tr><th>".join('</th><th>', $heads)."</th></tr>\n";
        do {
            $out .= "<tr><td>".join('</td><td>', $data)."</td></tr>\n";
        } while ($data = $res->fetch());
        $out .= "</table>\n";
    } else {
        $out = "NO RECORDS FOUND";
    }
    return $out;
}

/**
* write query results to plain text table
* 
* @param PDO $pdo
* @param string $sql
* @param array $params
* @return string query results
*/
function pdo2text(PDO $pdo, $sql, $params=[])
{
    $res = $pdo->prepare($sql);
    $res->execute($params);
    $data = $res->fetchAll(PDO::FETCH_ASSOC);
    if (count($data) == 0) {
        return "NO RECORDS FOUND";
    }
    $out = "<pre>\n";
    $heads = array_keys($data[0]);
    $widths = [];
    foreach ($heads as $h) {
        $widths[] = strlen($h);
    }
    foreach ($data as $row) {
        foreach (array_values($row) as $c => $v) {
            $widths[$c] = min(max($widths[$c], strlen($v)), 50);
        }
    }
    $horiz = '+';
    $format = '|';
    foreach ($widths as $w) {
        $horiz .= str_repeat('-', $w+2) . '+';
        $format .= " %-{$w}s |";
    }
    $format .= "\n";
    $out .= "$horiz\n";
    $out .= vsprintf($format, $heads);
    $out .= "$horiz\n";
    $repl = ["\n", "\t", "    ", "   ", "  "];
    foreach ($data as $row) {
        $row = array_map(function($v) use ($repl) {
                            return substr(str_replace($repl, " ", $v), 0, 50);
                        }, 
                        $row);
        $out .= vsprintf($format, $row);
    }
    $out .= $horiz . '</pre>';
    
    return $out;
}

function distance($lat, $lng, $lat0, $lng0)
{
    $deglen = 110.25;
    $x = $lat - $lat0;
    $y = ($lng - $lng0)*cos($lat0);
    return $deglen*sqrt($x*$x + $y*$y);
}
/* 8hBH9P5h47GKib4 */