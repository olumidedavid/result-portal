<?php 


    $res=$pdo->query("select count(*) from staff"); //staff
    $countAllStaff = $res->fetchColumn();

    $res=$pdo->query("select count(*) from department"); //department
    $countDepartment = $res->fetchColumn();

    $res=$pdo->query("select count(*) from student"); //student
    $countAllStudent = $res->fetchColumn();

    $res=$pdo->query("select count(*) from subject"); //courses
    $countAllCourses = $res->fetchColumn();

    $res=$pdo->query("select count(*) from session"); //courses
    $countAllSession = $res->fetchColumn();

    $res=$pdo->query("select count(*) from finalresult"); //courses
    $countAllComputed = $res->fetchColumn();

    $res=$pdo->query("select count(*) from level"); //courses
    $countAllLevel = $res->fetchColumn();

    $res=$pdo->query("select count(*) from semester"); //courses
    $countAllSemester = $res->fetchColumn();

    $res=$pdo->query("select count(*) from class"); //classes
    $countAllClass = $res->fetchColumn();

     $res=$pdo->query("select count(*) from result"); //results
    $countAllResult = $res->fetchColumn();
    
    $res = $pdo->query("SELECT classofdiploma, count(*) as tot FROM finalresult GROUP BY classofdiploma");
    $results = $res->fetchAll();
    $diplomas = array_column($results, 'tot', 'classofdiploma');


//$query = mysqli_query($con,"select * from student where matricNo='$matricNo'");
//$row = mysqli_fetch_array($query);
//$departmentId = $row['departmentId'];
//$facultyId = $row['facultyId'];
//$levelId = $row['levelId'];
//
//
//$que=mysqli_query($con,"select * from department where Id = '$departmentId'"); //department                     
//$row = mysqli_fetch_array($que);  
//$departmentName = $row['departmentName'];      
//
//
//$que=mysqli_query($con,"select * from faculty where Id = '$facultyId'"); //faculty                      
//$row = mysqli_fetch_array($que);  
//$facultyName = $row['facultyName'];      



//Log on to codeastro.com for more projects!
////////////  ADMINISTRATOR DASHBOARD //////////////

//$queryStudent=mysqli_query($con,"select * from student where facultyId = '$facultyId' and departmentId = '$departmentId'"); //assigned staff
//$adminCountStudent = mysqli_num_rows($queryStudent);
//
//$queryCourses=mysqli_query($con,"select * from course where facultyId = '$facultyId' and departmentId = '$departmentId'"); //today's Attendance
//$adminCountCourses=mysqli_num_rows($queryCourses);
//
//
//
////Log on to codeastro.com for more projects!
////-------------------------SUPER ADMINISTRATOR
//
//
//$admin=mysqli_query($con,"select * from admin where adminTypeId = '2'");
//$countAdmin=mysqli_num_rows($admin);
//
//$todaysAtt=mysqli_query($con,"select * from attendance where date(DateTaken)=CURDATE();"); //today's Attendance
//$countTodaysAttendance=mysqli_num_rows($todaysAtt);
//
//$allAtt=mysqli_query($con,"select * from attendance");
//$countAllAttendance=mysqli_num_rows($allAtt);

// //-------------------------------------------


//$staffQuery=mysqli_query($con,"select * from staff"); //staff
//$countAllStaff = mysqli_num_rows($staffQuery);
//
//$departmentQuery=mysqli_query($con,"select * from department"); //department
//$countDepartment = mysqli_num_rows($departmentQuery);
//
////$facultyQuery=mysqli_query($con,"select * from faculty"); //faculty
////$countFaculty = mysqli_num_rows($facultyQuery);
//
//$studentQuery=mysqli_query($con,"select * from student"); //student
//$countAllStudent = mysqli_num_rows($studentQuery);
//
//$courseQuery=mysqli_query($con,"select * from course"); //courses
//$countAllCourses = mysqli_num_rows($courseQuery);
//
//$courseSession=mysqli_query($con,"select * from session"); //courses
//$countAllSession = mysqli_num_rows($courseSession);
//
//$resultComputed=mysqli_query($con,"select * from finalresult"); //courses
//$countAllComputed = mysqli_num_rows($resultComputed);
//
//$levelQue=mysqli_query($con,"select * from level"); //courses
//$countAllLevel = mysqli_num_rows($levelQue);
//
//$semesterQue=mysqli_query($con,"select * from semester"); //courses
//$countAllSemester = mysqli_num_rows($semesterQue);
//
//$distinctno=mysqli_query($con,"SELECT * from finalresult WHERE classOfDiploma = 'Distinction'"); //dist. no.
//$countAllDist = mysqli_num_rows($distinctno);
//
//$uppercred=mysqli_query($con,"SELECT * from finalresult WHERE classOfDiploma = 'Upper Credit'"); //upper cred
//$countAllUpc = mysqli_num_rows($uppercred);
//
//$lowercred=mysqli_query($con,"SELECT * from finalresult WHERE classOfDiploma = 'Lower Credit'"); //lower cred
//$countAlllc = mysqli_num_rows($lowercred);
//
//$justpass=mysqli_query($con,"SELECT * from finalresult WHERE classOfDiploma = 'Pass'"); //just passed
//$countAlljp = mysqli_num_rows($justpass);
//
//$failed=mysqli_query($con,"SELECT * from finalresult WHERE classOfDiploma = 'Fail'"); //failed numbers
//$countAllf = mysqli_num_rows($failed);


//$res=$pdo->query("select count(*) from faculty"); //faculty
//$countFaculty = $res->fetchColumn();

//Log on to codeastro.com for more projects!
//-----------------------LECTURER----------------------

//$lecCourse=mysqli_query($con,"select * from course where departmentId = '$departmentId'"); //courses
//$countLecCourse = mysqli_num_rows($lecCourse);
//
//$que=mysqli_query($con,"select * from assignedstaff where departmentId = '$departmentId'"); //assigned staff
//$lecCountStaff = mysqli_num_rows($que);

//Log on to codeastro.com for more projects!
//-----------------------STUDENT----------------------

//$studCourse=mysqli_query($con,"select * from course where departmentId = '$departmentId'"); //courses
//$coutAllStudentCourses = mysqli_num_rows($studCourse);
//
//$queResult=mysqli_query($con,"select * from finalresult where matricNo = '$matricNo'"); //assigned staff
//$countAllStudResult = mysqli_num_rows($queResult);
////Log on to codeastro.com for more projects!
