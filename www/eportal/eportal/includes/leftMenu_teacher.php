
<?php
#include 'db_inc.php';
$con = myConnect();
   # $pdo = pdoConnect();
$staff_id = $_SESSION['staff_id'];
# $_SESSION['staff_id'] = 16; 
 $query = mysqli_query($con,"select firstname, lastname from staff where id='$staff_id'");
#$query = mysqli_query($con,"select * from staff where staff_id='$staff_id'");
$row = mysqli_fetch_array($query);
$staffName = $row['firstname'].' '.$row['lastname'];
?>
<aside id="left-panel" class="left-panel">
        <nav class="navbar navbar-expand-sm navbar-default">
            <div id="main-menu" class="main-menu collapse navbar-collapse">
                <ul class="nav navbar-nav">
                <li class="menu-title">Hi,&nbsp;<?php echo $staffName;?></li>
                    <li class="<?php if($page=='dashboard'){ echo 'active'; }?>">
                        <a href="#"><i class="menu-icon fa fa-dashboard"></i>Dashboard </a>
                    </li>
                  
                         <li class="menu-item-has-children dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false"> <i class="menu-icon fa fa-user"></i>Result Ranking</a>
                            <ul class="sub-menu children dropdown-menu">
<li><i class="fa fa-plus"></i> <a href="student_grade_distrib.php" target="_blank">Results Grades</a></li>
                                <li><i class="fa fa-plus"></i> <a href="student_top_subjects.php" target="_blank">Subject Ranking</a></li>
                                <li><i class="fa fa-plus"></i> <a href="student_top_results_bars.php" target="_blank">Top Result</a></li>
                            </ul>
                        </li> 
                 
                 <li class="menu-item-has-children dropdown <?php if($page=='session'){ echo 'active'; }?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false"> <i class="menu-icon fa fa-cogs"></i>Compute Result</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="fa fa-plus"></i> <a href="enter_results.php" target="_blank">Input Student Result</a></li>
                    </ul>
                </li>
                   
                   <li class="menu-title">Class Teacher Assessment</li>
                    <li class="menu-item-has-children dropdown <?php if($page=='faculty'){ echo 'active'; }?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false"> <i class="menu-icon fa fa-th"></i>Psychomotor</a>
                        <ul class="sub-menu children dropdown-menu">
                            <li><i class="fa fa-plus"></i> <a href="enter_assessments.php" target="_blank">Comments</a></li>
                           <!-- <li><i class="fa fa-eye"></i> <a href="viewclass.php">View Class</a></li>-->
                        </ul>
                    </li>
                    
                    <li class="menu-item-has-children dropdown <?php if($page=='department'){ echo 'active'; }?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false"> <i class="menu-icon fa fa-bars"></i>View Result</a>
                        <ul class="sub-menu children dropdown-menu">
<li><i class="fa fa-plus"></i> <a href="create_pdfs.php" target="_blank">Download Result by Class</a></li>                            
<li><i class="fa fa-plus"></i> <a href="midterm_results.php" target="_blank">Mid-Term Result</a></li>
                            <li><i class="fa fa-eye"></i> <a href="endterm_results.php" target="_blank">EndTerm Result</a></li>
                        </ul>
                    </li>
<!--
                    <li class="menu-title">Student Section</li>
                    <li class="menu-item-has-children dropdown <?php if($page=='student'){ echo 'active'; }?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false"> <i class="menu-icon fa fa-users"></i>Student</a>
                        <ul class="sub-menu children dropdown-menu">
                            <li><i class="fa fa-plus"></i> <a href="student_data.php">Add New Student</a></li>
                            <li><i class="fa fa-eye"></i> <a href="viewStudent.php">View Student</a></li>
                             <li><i class="fa fa-eye"></i> <a href="student_class_alloc.php">Assign Student Class</a></li>
                        </ul>
                    </li>

                    

                     <li class="menu-item-has-children dropdown <?php if($page=='courses'){ echo 'active'; }?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false"> <i class="menu-icon fa fa-book"></i>Courses</a>
                        <ul class="sub-menu children dropdown-menu">
                            <li><i class="fa fa-plus"></i> <a href="createCourses.php">Add New Course</a></li>
                            <li><i class="fa fa-eye"></i> <a href="viewCourses.php">View Courses</a></li>
                        </ul>
                    </li>
                    <li class="menu-title">Grading System</li>
                      <li class="menu-item-has-children dropdown <?php if($page=='result'){ echo 'active'; }?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false"> <i class="menu-icon fa fa-file"></i>Assign Class Grading</a>
                        <ul class="sub-menu children dropdown-menu">
                            <li><i class="fa fa-plus"></i> <a href="grading.php">Assign Grade</a></li>
                            <li><i class="fa fa-plus"></i> <a href="midterm_results.php">Mid-Term Result</a></li>
                            <li><i class="fa fa-plus"></i> <a href="endterm_results.php">End of Term Results</a></li>
                            <li><i class="fa fa-plus"></i> <a href="results_status.php">Result Status</a></li>
                            <li><i class="fa fa-plus"></i> <a href="results_print.php">Download All Result by Class</a></li>                     
                            <li><i class="fa fa-plus"></i> <a href="gradingCriteria.php">View Grading Criteria</a></li>

                        </ul>
                    </li>-->

                    <li class="menu-title">Account</li>
                    <li class="menu-item-has-children dropdown <?php if($page=='profile'){ echo 'active'; }?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false"> <i class="menu-icon fa fa-user-circle"></i>Profile</a>
                        <ul class="sub-menu children dropdown-menu">
                           <li><i class="menu-icon fa fa-key"></i> <a href="#">Change Password</a></li> 
                            <li><i class="menu-icon fa fa-user"></i> <a href="#">Update Profile</a></li>
                            </li>
                        </ul>
                      <!--  <li class="menu-title">Staff Section</li>
                    <li class="menu-item-has-children dropdown <?php if($page=='profile'){ echo 'active'; }?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false"> <i class="menu-icon fa fa-user-circle"></i>Staff</a>
                        <ul class="sub-menu children dropdown-menu">
                           <li><i class="menu-icon fa fa-key"></i> <a href="staff_data.php">Add New Staff</a></li> 
                            <li><i class="menu-icon fa fa-user"></i> <a href="staff_course.php">Assign Teacher to Class</a></li>
                            </li>
                        </ul>-->
                         <li>
                        <a href="logout.php"> <i class="menu-icon fa fa-power-off"></i>Logout </a>
                    </li>
                    </li>
                </ul>
            </div><!-- /.navbar-collapse -->
        </nav>
    </aside>