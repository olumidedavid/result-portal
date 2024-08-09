<?php
session_start();
include 'db_inc.php';
include 'functions_ba.php';
require_once 'TCPDF-main/tcpdf.php';
#require_once WEB_ROOT.'/tcpdf-main/tcpdf.php';
$pdo = pdoConnect();

################################################################################
#  Check suoeradmin is logged in                                               #
################################################################################
    if (getAdminType($pdo) != 1) {
        header("Location: login.php");
        exit;
    }

class RESULTSpdf  extends TCPDF
{
    public $print_list = [];
    protected $report_title = 'Results';
    
    protected $pdo;
    protected $sessionname;
    protected $termno;
    protected $studentclass;
    protected $studentclassid;
    protected $semester;
    protected $sessionid;
    protected $data = [];
    protected $gradelist;
    // student data
    protected $studentlevelid;
    protected $studentname;
    protected $studentimage;
    protected $pupilsinclass;
    protected $scoretotal;
    protected $totalsubjects;
    protected $comments = [ 'teacher'=>'', 'head'=>'' ];
     
    public function __construct($semester, $class, $pdo)
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->setHeaderMargin(PDF_MARGIN_HEADER);
        $this->setFooterMargin(PDF_MARGIN_FOOTER);
        $this->setAutoPageBreak(TRUE, 10);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        $res = $pdo->prepare("SELECT   sm.sessionid AS sessid
                                     , ss.sessionname
                                     , sm.semestername+0 AS termno
                                     , stc.studentid AS student
                                     , l.id as level
                                     , cl.classname as class
                                FROM level l 
                                     JOIN class cl ON l.id = cl.levelid
                                     JOIN student_class stc ON cl.id = stc.classid
                                                           AND stc.semesterid = ?
                                     JOIN semester sm ON stc.semesterid = sm.id
                                     JOIN session ss ON sm.sessionid = ss.id
                                WHERE cl.id = ?
                                ORDER BY l.id, cl.id
                                ");
        
        $res->execute([ $semester, $class ]);
        $this->print_list = $res->fetchAll();
        $this->pdo = $pdo;
        $this->semester = $semester;
        $this->studentclassid = $class;
        $this->studentlevelid = $this->print_list[0]['level'];
        $this->termno = $this->print_list[0]['termno'];
        $this->studentclass = $this->print_list[0]['class'];
        $this->sessionname = $this->print_list[0]['sessionname'];
        $this->sessionid = $this->print_list[0]['sessid'];
        $this->gradelist = getGradeList($this->pdo, $this->studentlevelid);
    }
    
    function header()
    {
        $headtext = "<strong>MABEST ACADEMY</strong><br> 
            <small><i>Mentoring Future Leaders, Transforming The Society...</i></small><br>                
            Omolayo Estate, Oke-Ijebu Road, Akure, Ondo State, Nigeria.              
            <h2>$this->report_title</h2>";
        $this->setFontSize(10);
        $this->image('logo1.png', 15, 10, 25, 0, 'jpg');
//        $this->setXY(40,10);
//        $this->Cell(120, 8, 'MABEST ACADEMY', 0, 2, 'C');
//        $this->Cell(120, 8, 'Omolayo Estate, 340110, Akure, Nigeria', 0, 2, 'C');
//        $this->SetFontSize(16);
//        $this->Cell(120, 8, $this->report_title, 0, 2, 'C');
        $this->WriteHTMLCell(120, 6, 40, 10, $headtext, 0,0,0,0,'C');
        $this->setAlpha(0.1);
        $this->image('logo1.png', 40, 150, 120, 0, 'jpg');
        $this->setAlpha(1);
    }
    
    function footer()
    {
        // suppress footer
    }
    
    protected function printResultsHeader()
    {
        $this->image($this->studentimage, 170, 10, 0, 25);
        $this->setXY(10, 40);
        $widths = [80, 10, 30, 5, 30, 5, 30];
        $this->setFont('Helvetica', 'B', 10);
        $this->Cell($widths[0], 6, "Name: $this->studentname", 0, 2);
        $this->setFont('Helvetica', '', 10);
        $this->Cell($widths[0], 6, "Class: $this->studentclass", 0, 2);
        $this->Cell($widths[0], 6, "Session: $this->sessionname - Term {$this->termno}", 0, 1);
        
        $this->setXY(100, 40);
        $this->setFillColor(0x02, 0x7c, 0xbc);
        $this->setDrawColor(0x02, 0x7c, 0xbc);
        $this->SetTextColor(0xFF, 0xFF, 0xFF);
        $this->setFontSize(9);
        $this->Cell($widths[2], 6, "Students in class", 0, 0, 'C', 1);
        $this->Cell($widths[3]);
        $this->Cell($widths[4], 6, "Percentage", 0, 0, 'C', 1);
        $this->Cell($widths[5]);
        $this->Cell($widths[6], 6, "Score", 0, 0, 'C', 1);
        $this->setXY(100, 50);
        $this->SetTextColor(0);
        $this->setFontSize(14);
        
        $pcent = number_format($this->scoretotal/$this->totalsubjects, 0);
        $scr = "{$this->scoretotal}/" . ($this->totalsubjects*100);
        
        $this->Cell($widths[2], 6, $this->pupilsinclass, 1, 0, 'C');
        $this->Cell($widths[3]);
        $this->Cell($widths[4], 6, "$pcent %", 1, 0, 'C');
        $this->Cell($widths[5]);
        $this->Cell($widths[6], 6, $scr, 1, 0, 'C');
        
        $this->Ln(12);
    }
    
    protected function printComments()
    {
        $this->setFont('helvetica', 'B', 12);
        $this->Cell(0, 6, 'Comments', 0, 1);
        $this->Ln(4);
        $this->setFont('helvetica', 'B', 10);
        $this->Cell(4,5);
        $this->Cell(30, 5, 'Class Teacher', 0, 0);
        $this->setFont('helvetica', '', 9);
        $this->Multicell(0, 5, $this->comments['teacher'], 0, 'L', 1);
        
        $this->Ln(4);
        $this->setFont('helvetica', 'B', 10);
        $this->Cell(4,5);
        $this->Cell(30, 5, 'Head of School', 0, 0);
        $this->setFont('helvetica', '', 9);
        $this->Multicell(0, 5, $this->comments['head'], 0, 'L', 1);
    }
    
} #class RESULTSpdf

##################################################################################################

class MIDPDF extends RESULTSpdf
{   
    protected $report_title = 'Mid-Term Results';
    
    public function getFilename()
    {
        $fn[] = str_replace('/', '-', $this->sessionname);
        $fn[] = "Mid_Term{$this->termno}";
        $fn[] = str_replace(' ', '', $this->studentclass);
        return join('_', $fn) . '.pdf';
    }
    
    private function getData($student)
    {
        $res = $this->pdo->prepare("SELECT st.id as stid
                                 , concat_ws(' ', st.lastname, st.firstname, st.othername) as stname
                                 , st.image
                                 , cl.classname
                                 , sc.classid
                                 , l.id as level
                                 , c.subjectid
                                 , s.subjectname
                                 , score*10 as ca
                            FROM result r
                                 JOIN 
                                 (
                                 student_class sc 
                                 JOIN class cl ON sc.classid = cl.id
                                 JOIN level l ON cl.levelid = l.id
                                 JOIN course c ON c.levelid = l.id
                                 JOIN student st ON sc.studentid = st.id
                                 JOIN semester sm ON sc.semesterid = sm.id
                                 JOIN session sn ON sm.sessionid = sn.id
                                 JOIN subject s ON c.subjectid = s.id
                                 ) ON r.studentclassid = sc.id AND exam = 'CA1' and r.courseid = c.id
                            WHERE sn.id = ?
                              AND studentid = ?
                              AND sm.semestername+0 = ?
                              AND cl.id = ?
                            ORDER BY c.levelid, sc.id, c.subjectid, sc.semesterid, exam
                            ");
        $res->execute( [ $this->sessionid, $student, $this->termno,$this->studentclassid ] );
        $this->data = [];
        $r = $res->fetch();
        if ($r) {
            $this->studentname = $r['stname'];
            #$this->studentclass = $r['classname'];
            #$this->studentclassid = $r['classid'];
            #$this->studentlevelid = $r['level'];
            $this->studentimage = $r['image'] ?  "images/{$r['image']}" : "images/no_photo.jpg" ;
            // then process the rest of the row data in the first and remaining rows
            do {
                if (!isset($this->data[ $r['subjectid'] ])) {
                    $this->data[ $r['subjectid'] ] = [ 'name' => $r['subjectname'],
                                                 'ca' => 0,
                                                 'last'  => 0,
                                                 'avg' => 0, 
                                                 'grade' => '',
                                                 'comment' => '',
                                                 'terms' => 0
                                               ];
                }   
                $this->data[ $r['subjectid'] ]['ca'] = $r['ca'];
            } while ($r = $res->fetch());
            $this->totalsubjects = count($this->data);
            if ($this->totalsubjects == 0) $this->totalsubjects = 1;

            ################################################################################
            #  get prev terms' totals
            ################################################################################ 
            $res = $this->pdo->prepare("SELECT c.subjectid
                                         , round(sum(score) ) as lastterm
                                         , count(distinct sm.id) as terms
                                    FROM result r 
                                         JOIN course c ON r.courseid = c.id
                                         JOIN student_class stc ON r.studentclassid = stc.id
                                         JOIN semester sm ON stc.semesterid = sm.id
                                    WHERE sm.sessionid = ?
                                          AND stc.studentid = ?
                                          AND sm.semestername+0 <= ?
                                    GROUP BY c.subjectid
                                    ");
            $t1 = $this->termno - 1;
            $res->execute([ $this->sessionid, $student, $t1 ]);
            foreach ($res as $r) {
                $this->data[$r['subjectid']]['last'] = $r['lastterm'];
                $this->data[$r['subjectid']]['terms'] = $r['terms'];
            }
            ################################################################################
            #  get the avg scores for the class                                            #
            ################################################################################
            $avgs = classAverageScores($this->pdo, $this->studentclassid, $this->sessionid, $this->termno);
            foreach ($avgs as $s => $av) {
                if (isset($this->data[$s]))
                    $this->data[$s]['avg'] = $av;
            }   
            ################################################################################
            #  Get pupil count                                                             #
            ################################################################################
            $res = $this->pdo->prepare("SELECT COUNT(DISTINCT stc.studentid) AS pupils
                                        FROM student_class stc 
                                             JOIN semester sm ON sm.id = stc.semesterid
                                             JOIN result r ON stc.id = r.studentclassid
                                        WHERE sm.id = ?
                                          AND stc.classid = ? 
                                    ");
            $res->execute([ $this->semester, $this->studentclassid ]);
            $this->pupilsinclass = $res->fetchColumn();    
            ################################################################################
            #  Get totals and grades                                                       #
            ################################################################################
            $grand_total = 0;
            foreach ($this->data as $subid => &$subdata) {
                $total = round(($subdata['last'] + $subdata['ca'])/($subdata['terms']+1));
                $subdata['last'] = $total;
                $grand_total += $total;
                if ($total > 0) {
                    list($grade, $comment) = getGradeComment($this->pdo, $total, $this->studentlevelid );
                }
                else {
                    $grade = '-';
                    $comment = '-';
                }
                $subdata['grade'] = $grade;
                $subdata['comment'] = $comment;
            }
            $this->scoretotal = $grand_total;
            
            $this->comments = getEOTComments($this->pdo, $student, $this->semester);
        }
    }
    
    private function printTableHeader($widths)
    {
        $this->setFontSize(9);
        #$this->setFillColor(0);
        $this->setDrawColor(0xFF);
        $this->SetTextColor(0xFF);
        
        
        $this->Cell($widths[0], 5, '', 'R', 0, 'C', 1);
        $this->Cell($widths[1], 5, ' Subject', 'LR', 0, 'L', 1);
        $this->Cell($widths[2], 5, 'CA', 'LR', 0, 'C', 1);
        $this->Cell($widths[3], 5, 'Total', 'LR', 0, 'C', 1);
        $this->Cell($widths[4], 5, 'Class', 'LR', 0, 'C', 1);
        $this->Cell($widths[5], 5, 'Grade', 'LR', 0, 'C', 1);
        $this->Cell($widths[6], 5, ' Comment', 'L', 0, 0, 1);
        $this->Ln();
        $this->Cell($widths[0], 5, '', 'R', 0, 'C', 1);
        $this->Cell($widths[1], 5, '', 'LR', 0, 'L', 1);
        $this->Cell($widths[2], 5, '', 'LR', 0, 'C', 1);
        $this->Cell($widths[3], 5, '', 'LR', 0, 'C', 1);
        $this->Cell($widths[4], 5, 'Avg', 'LR', 0, 'C', 1);
        $this->Cell($widths[5], 5, '', 'LR', 0, 'C', 1);
        $this->Cell($widths[6], 5, '', 'L', 0, 0, 1);
        
    }
    
    private function printResultsTable($widths)
    {
        $this->setFillColor(240);
        $this->setDrawColor(0);
        $this->SetTextColor(0);
        
        $n = 0;
        foreach ($this->data as $subdata) {
            $this->Ln();
            $this->Cell($widths[0], 6, $n+1, 'R', 0, 'C', $n%2);
            $this->Cell($widths[1], 6, $subdata['name'], 'LR', 0, 'L', $n%2);
            $this->Cell($widths[2], 6, $subdata['ca'], 'L', 0, 'C', $n%2);
            $this->Cell($widths[3], 6, $subdata['last'], '', 0, 'C', $n%2);
            $this->Cell($widths[4], 6, $subdata['avg'], '', 0, 'C', $n%2);
            $this->Cell($widths[5], 6, "      {$subdata['grade']}", 'L', 0, 'L', $n%2);
            $this->Cell($widths[6], 6, $subdata['comment'], '', 0, 0, $n%2);
            ++$n;
        }
        $this->Ln(6);
        $this->setFont('helvetica', 'I', 8);
        $this->setTextColor(100);
        $this->WriteHTMLCell(0, 4, $this->GetX(), $this->GetY()+2, "<b>Grades:</b> $this->gradelist");
        $this->setTextColor(0);
    }
    
    public function printResults($student)
    {
        $this->getData($student);                                     
        
        $this->addPage();
                                                     
        if (!$this->data)  {
            $res = $this->pdo->prepare("SELECT concat(firstname,' ',lastname)
                                        FROM student
                                        WHERE id = ?
                                       ");
            $res->execute([$student]);
            $name = $res->fetchColumn();
            $this->WriteHTMLCell(100, 6, 45, 80, "<b>NO DATA</b><br>$name (ID: $student)");
            return false;
        }
        
        $this->printResultsHeader();
        $widths = [10, 60, 20, 20, 20, 20, 30];
        $this->printTableHeader($widths);       
        $this->printResultsTable($widths);
        
        $this->Ln(12);
        
        $this->printComments();
        
        return true;
        
//        $this->setFont('helvetica', '', 9);
//        
//        $res = $this->pdo->query("SELECT   a.type
//                                         , assessname
//                                         , grade
//                                    FROM assessment a
//                                         join student_class stc ON stc.semesterid = 10
//                                                               AND stc.studentid = 217
//                                         LEFT JOIN eot_assessment e ON a.id = e.assessmentid
//                                         and  e.studentclassid = stc.id
//                                 ");
//        $asses = $res->fetchAll(PDO::FETCH_GROUP);
//        
//        if ($asses) {
//            $this->setFont('helvetica', 'B', 12);
//            $this->Cell(0, 6, 'Assessments', 0, 1);
//            $this->setFontSize(10);
//            $this->Cell(5, 6);
//            $this->Cell(95, 6, 'Affective', 0, 0);
//            $this->Cell(95, 6, 'Psychomotive', 0, 1);
//            $this->Cell(5);
//            $this->Cell(60, 6, 'Domain', 'TB', 0);
//            $this->Cell(15, 6, 'Grade', 'TB', 0);
//            $this->Cell(20);
//            $this->Cell(60, 6, 'Domain', 'TB', 0);
//            $this->Cell(15, 6, 'Grade', 'TB', 1);
//            
//            $saveY = $this->getY();
//        foreach ($asses['Affective'] as $r) {
//            $this->Cell(5);
//            $this->Cell(60, 5.5, $r['assessname'], 0, 0);
//            $this->Cell(15, 5.5, $r['grade'], 0, 1);
//        }
//        $commentY = $this->getY()+6;
//        $this->setY($saveY);
//        foreach ($asses['Psychomotor'] as $r) {
//            $this->setX(115);
//            $this->Cell(60, 5.5, $r['assessname'], 0, 0);
//            $this->Cell(15, 5.5, $r['grade'], 0, 1);
//        }
//        $this->setXY(15, $commentY);
//        }
        
        
    }                        
} # class MIDPDF

##################################################################################################

class ENDPDF extends RESULTSpdf
{
    protected $report_title;

    function header()
    {
        $this->report_title = $this->termno == 3 ? 'End of Year Results' : 'End of Term Results';
        parent::header();
    }
    
    public function getFilename()
    {
        $fn[] = str_replace('/', '-', $this->sessionname);
        $fn[] = "End_Term{$this->termno}";
        $fn[] = str_replace(' ', '', $this->studentclass);
        return join('_', $fn) . '.pdf';
    }
    
    private function getData($student)
    {
        ################################################################################
        #  Get scores and put in array with required output structure                  #
        ################################################################################
        $res = $this->pdo->prepare("SELECT st.id as stid
                                     , concat_ws(' ', st.lastname, st.firstname, st.othername) as stname
                                     , st.image
                                     , cl.classname
                                     , sc.classid
                                     , l.id as level
                                     , sn.sessionname
                                     , sm.semestername
                                     , sm.semestername+0 as term
                                     , c.subjectid
                                     , s.subjectname
                                     , exam
                                     , score
                                FROM result r
                                     JOIN 
                                     (
                                     student_class sc 
                                     JOIN class cl ON sc.classid = cl.id
                                     JOIN level l ON cl.levelid = l.id
                                     JOIN course c ON c.levelid = l.id
                                     JOIN student st ON sc.studentid = st.id
                                     JOIN semester sm ON sc.semesterid = sm.id
                                     JOIN session sn ON sm.sessionid = sn.id
                                     JOIN subject s ON c.subjectid = s.id
                                     ) ON r.studentclassid = sc.id AND r.courseid = c.id
                                     
                                WHERE sn.id = ?
                                  AND studentid = ?
                                  AND sm.semestername+0 <= ?
                                  AND cl.id = ?
                                ORDER BY c.levelid, sc.id, c.subjectid, sc.semesterid, exam
                                ");
        $res->execute( [ $this->sessionid, $student, $this->termno, $this->studentclassid ] );
        $this->data = [];
        // get data common to all rows from first row
        $r = $res->fetch();
        if ($r) {
            $this->studentname = $r['stname'];
            #$this->studentclass = $r['classname'];
            #$studentsession = $r['sessionname'];
            #$studentterm = "- Term $termno";
            $this->studentimage = $r['image']? "images/" . $r['image'] : "images/no_photo.jpg";                                                                      ### provide image path here
            #$level = $r['level'];
            // then process the rest of the row data in the first and remaining rows
            do {
                if (!isset($this->data[ $r['subjectid'] ])) {
                    $this->data[ $r['subjectid'] ] = [ 'name' => $r['subjectname'],
                                                       'exams' => ['CA1'=>'', 'CA2'=>'', 'CA3'=>'', 'Exam'=>''],
                                                       'scores'  => [ 1=>0, 0, 0 ],
                                                       'total' => 0,
                                                       'avg' => 0, 
                                                       'grade' => '',
                                                       'comment' => ''
                                                     ];
                }   
                if ($r['term'] == $this->termno && isset($this->data[$r['subjectid'] ]['exams'][ $r['exam']])) {
                    $this->data[ $r['subjectid'] ]['exams'][ $r['exam'] ] = $r['score'];
                }
                $this->data[ $r['subjectid'] ]['scores'][$r['term']] += $r['score'];
            } while ($r = $res->fetch());
        // get the avg scores for the class
            $avgs = classAverageScores($this->pdo, $this->studentclassid, $this->sessionid, $this->termno);
            foreach ($avgs as $s => $av) {
                
                if (isset($this->data[$s]))
                    $this->data[$s]['avg'] = round($av,0);
            }   
            $this->totalsubjects = count($this->data);
            if ($this->totalsubjects == 0) $this->totalsubjects = 1;
        ################################################################################
        #  Get pupil count                                                             #
        ################################################################################
        $res = $this->pdo->prepare("SELECT COUNT(DISTINCT stc.studentid) AS pupils
                                    FROM student_class stc 
                                         JOIN semester sm ON sm.id = stc.semesterid
                                         JOIN result r ON stc.id = r.studentclassid
                                    WHERE sm.id = ?
                                      AND stc.classid = ? 
                            ");
        $res->execute([ $this->semester, $this->studentclassid ]);
        $this->pupilsinclass = $res->fetchColumn();    
                
        ################################################################################
        #  Loop through the data array to construct the output table rows              #
        ################################################################################
            $grand_total = 0;
            #$subject_count = count($this->data);
            foreach ($this->data as $subid => &$subdata) {
                $temp = array_filter($subdata['scores']);
                $total = $temp ? round(array_sum($temp)/count($temp)) : 0;
                $grand_total += $total;
                if ($total) {
                    list($grade, $comment) = getGradeComment($this->pdo, $total, $this->studentlevelid);
                }
                else {
                    $grade = '-';
                    $comment = '-';
                }
                $subdata['total'] = $total;
                $subdata['grade'] = $grade;
                $subdata['comment'] = $comment;
                #$clr = GRADE_COLOUR[$grade];
            }
            $this->scoretotal = $grand_total;
            $this->comments = getEOTComments($this->pdo, $student, $this->semester);
        }
    }
    
    private function printTableHeader($widths)
    {
        $this->setFontSize(9);
        #$this->setFillColor(0);
        $this->setDrawColor(0xFF);
        $this->SetTextColor(0xFF);
        
        $th = [
                1 => [ ['1st', 'Term', '100'], ['','',''], ['','',''] ],
                2 => [ ['1st', 'Term', ''], ['2nd','Term','100'], ['','',''] ],
                3 => [ ['1st', 'Term', ''], ['2nd','Term',''], ['3rd','Term','100'] ]
              ];
         $t = $this->termno;
       
        $this->Cell($widths[0], 5, '', 'R', 0, 'C', 1);
        $this->Cell($widths[1], 5, '', 'L', 0, 'L', 1);
        $this->Cell($widths[2], 5, 'CA1', 0, 0, 'C', 1);
        $this->Cell($widths[3], 5, 'CA2', 0, 0, 'C', 1);
        $this->Cell($widths[4], 5, 'CA3', 0, 0, 'C', 1);
        $this->Cell($widths[5], 5, 'Exam', 'R', 0, 'C', 1);
        $this->Cell($widths[6], 5, $th[$t][0][0], 'L', 0, 'C', 1);
        $this->Cell($widths[7], 5, $th[$t][1][0], 0, 0, 'C', 1);
        $this->Cell($widths[8], 5, $th[$t][2][0], 'R', 0, 'C', 1);
        $this->Cell($widths[9], 5, '', 0, 0, 'C', 1);
        $this->Cell($widths[10], 5, 'Class', 0, 0, 'C', 1);
        $this->Cell($widths[11], 5, '', 0, 0, 'L', 1);
        $this->Cell($widths[12], 5, ' ', 0, 0, 'L', 1);
        $this->Ln();
        $this->Cell($widths[0], 5, '', 'R', 0, 'C', 1);
        $this->Cell($widths[1], 5, 'Subject', 'L', 0, 'L', 1);
        $this->Cell($widths[2], 5, '', 0, 0, 'C', 1);
        $this->Cell($widths[3], 5, '', 0, 0, 'C', 1);
        $this->Cell($widths[4], 5, '', 0, 0, 'C', 1);
        $this->Cell($widths[5], 5, '', 'R', 0, 'C', 1);
        $this->Cell($widths[6], 5, $th[$t][0][1], 'L', 0, 'C', 1);
        $this->Cell($widths[7], 5, $th[$t][1][1], 0, 0, 'C', 1);
        $this->Cell($widths[8], 5, $th[$t][2][1], 'R', 0, 'C', 1);
        $this->Cell($widths[9], 5, 'Total', 0, 0, 'C', 1);
        $this->Cell($widths[10], 5, 'Avg', 0, 0, 'C', 1);
        $this->Cell($widths[11], 5, 'Grade', 0, 0, 'L', 1);
        $this->Cell($widths[12], 5, ' Comment', 0, 0, 'L', 1);
        $this->Ln();
        $this->Cell($widths[0], 5, '', 'R', 0, 'C', 1);
        $this->Cell($widths[1], 5, '', 'L', 0, 'L', 1);
        $this->Cell($widths[2], 5, '10', 0, 0, 'C', 1);
        $this->Cell($widths[3], 5, '10', 0, 0, 'C', 1);
        $this->Cell($widths[4], 5, '10', 0, 0, 'C', 1);
        $this->Cell($widths[5], 5, '70', 'R', 0, 'C', 1);
        $this->Cell($widths[6], 5, $th[$t][0][2], 'L', 0, 'C', 1);
        $this->Cell($widths[7], 5, $th[$t][1][2], 0, 0, 'C', 1);
        $this->Cell($widths[8], 5, $th[$t][2][2], 'R', 0, 'C', 1);
        $this->Cell($widths[9], 5, '', 0, 0, 'C', 1);
        $this->Cell($widths[10], 5, '', 0, 0, 'C', 1);
        $this->Cell($widths[11], 5, '', 0, 0, 'L', 1);
        $this->Cell($widths[12], 5, ' ', 0, 0, 'L', 1);
        
    }
    
    private function printResultsTable($widths)
    {
        $this->setFillColor(240);
        $this->setDrawColor(0);
        $this->SetTextColor(0);
        
        $n = 0;
        foreach ($this->data as $subdata) {
            $this->Ln();
            $this->Cell($widths[0], 6, $n+1, 'R', 0, 'C', $n%2);
            $this->Cell($widths[1], 6, $subdata['name'], 'L', 0, 'L', $n%2);
            $this->Cell($widths[2], 6, $subdata['exams']['CA1'], 0, 0, 'C', $n%2);
            $this->Cell($widths[3], 6, $subdata['exams']['CA2'], 0, 0, 'C', $n%2);
            $this->Cell($widths[4], 6, $subdata['exams']['CA3'], 0, 0, 'C', $n%2);
            $this->Cell($widths[5], 6, $subdata['exams']['Exam'], 'R', 0, 'C', $n%2);
            
            foreach ($subdata['scores'] as &$s) {
                if ($s==0) $s = '';
            }
            
            $this->Cell($widths[6], 6, $subdata['scores'][1], 'L', 0, 'C', $n%2);
            
            $s2 = $this->termno > 1 ? $subdata['scores'][2] : '';
            $this->Cell($widths[7], 6, $s2, 0, 0, 'C', $n%2);
            
            $s2 = $this->termno > 2 ? $subdata['scores'][3] : '';
            $this->Cell($widths[8], 6, $s2, 'R', 0, 'C', $n%2);
            
            $this->Cell($widths[9], 6, $subdata['total'], 'L', 0, 'C', $n%2);
            $this->Cell($widths[10], 6, $subdata['avg'], 0, 0, 'C', $n%2);
            $this->Cell($widths[11], 6, "  {$subdata['grade']}", 0, 0, 'L', $n%2);
            $this->Cell($widths[12], 6, $subdata['comment'], 0, 0, 0, $n%2);
            ++$n;
        }
        $this->Ln(6);
        $this->setFont('helvetica', 'I', 8);
        $this->setTextColor(100);
        $this->WriteHTMLCell(0, 4, $this->GetX(), $this->GetY()+2, "<b>Grades:</b> $this->gradelist");
        $this->setTextColor(0);
    }
    
    private function printAssessments($student)
    {
        $this->setFont('helvetica', '', 9);
        
        $res = $this->pdo->prepare("SELECT   a.type
                                         , assessname
                                         , grade
                                    FROM assessment a
                                         join student_class stc ON stc.semesterid = ?
                                                               AND stc.studentid = ?
                                         LEFT JOIN eot_assessment e ON a.id = e.assessmentid
                                         and  e.studentclassid = stc.id
                                 ");
        $res->execute([ $this->semester, $student ]);
        $asses = $res->fetchAll(PDO::FETCH_GROUP);
        
        if ($asses) {
            $this->Ln(2);
            $this->setFont('helvetica', 'B', 12);
            $this->Cell(0, 6, 'Assessments', 0, 1);
            $this->setFontSize(10);
            $this->Cell(5, 6);
            $this->Cell(95, 6, 'Affective', 0, 0);
            $this->Cell(95, 6, 'Psychomotive', 0, 1);
            $this->Cell(5);
            $this->Cell(60, 6, 'Domain', 'TB', 0);
            $this->Cell(15, 6, 'Grade', 'TB', 0);
            $this->Cell(20);
            $this->Cell(60, 6, 'Domain', 'TB', 0);
            $this->Cell(15, 6, 'Grade', 'TB', 1);
            
            $this->setFont('helvetica', '', 9);
            $saveY = $this->getY();
            foreach ($asses['Affective'] as $r) {
                $this->Cell(5);
                $this->Cell(60, 4.5, $r['assessname'], 0, 0);
                $this->Cell(15, 4.5, $r['grade'], 0, 1);
            }
            $commentY = $this->getY();
            $this->setY($saveY);
            foreach ($asses['Psychomotor'] as $r) {
                $this->setX(115);
                $this->Cell(60, 4.5, $r['assessname'], 0, 0);
                $this->Cell(15, 4.5, $r['grade'], 0, 1);
            }
            $this->setXY(15, $commentY);
        }
        
    }
    
    public function printResults($student)
    {
        $this->getData($student);                                     
        
        $this->addPage();
                                                     
        if (!$this->data)  {
            $res = $this->pdo->prepare("SELECT concat(firstname,' ',lastname)
                                        FROM student
                                        WHERE id = ?
                                       ");
            $res->execute([$student]);
            $name = $res->fetchColumn();
            $this->WriteHTMLCell(100, 6, 45, 80, "<b>NO DATA</b><br>$name (ID: $student)");
            return false;
        }
        
        $this->printResultsHeader();
        $widths = [5,45,10,10,10,10,10,10,10,10,10,12,28];
        $this->printTableHeader($widths);       
        $this->printResultsTable($widths);
        
        $this->Ln(8);
        $this->printAssessments($student);
        $this->Ln(2);
        $this->printComments();
        
        return true;
    }                        
    
} # class ENDPDF

##################################################################################################



$semester = $_GET['semester'] ?? 0;
$class = $_GET['class'] ?? 0;

// create new PDF document
switch ($_GET['result']) {
    case 'mid': $pdf = new MIDPDF($semester, $class, $pdo);
                break;
    case 'end': $pdf = new ENDPDF($semester, $class, $pdo);
                break;
}

//$pdf->setFont('helvetica', '', 10, '', true);

$filename = $pdf->getFilename();
foreach ($pdf->print_list as $s) {
    $pdf->printResults($s['student']);
}
$pdf->output($filename, 'I');
?>

