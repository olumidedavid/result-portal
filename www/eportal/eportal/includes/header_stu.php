 <?php
#include 'db_inc.php';
$con = myConnect();
   # $pdo = pdoConnect();
$student_id = $_SESSION['student_id'];
# $_SESSION['staff_id'] = 16; 
 $query = mysqli_query($con,"select image from student where id='$student_id'");
#$query = mysqli_query($con,"select * from staff where staff_id='$staff_id'");
$row = mysqli_fetch_array($query);
$passport = "images/" . $row['image'];
?>

 <header id="header" class="header">
            <div class="top-left">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#">Mabest Academy eResult</a>
                     <!--<a class="navbar-brand" href="./"><img src="assets/img/designed2.png" alt="Logo"></a> -->
                    <a id="menuToggle" class="menutoggle"><i class="fa fa-bars"></i></a>
                </div>
            </div>
            <div class="top-right">
                <div class="header-menu">
                    <div class="header-left">
                        <!-- <button class="search-trigger"><i class="fa fa-search"></i></button> -->
                        <div class="form-inline">
                            <form class="search-form">
                                <input class="form-control mr-sm-2" type="text" placeholder="Search ..."
                                    aria-label="Search">
                                <button class="search-close" type="submit"><i class="fa fa-close"></i></button>
                            </form>
                        </div>

                    <div class="user-area dropdown float-right">
                        <a href="#" class="dropdown-toggle active" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <img class="user-avatar rounded-circle" src='<?php echo $passport;?>' alt="User Avatar">
                            <!-- <img class="user-avatar rounded-circle" src="../images/logo.jpeg" alt="User Avatar"> -->
                        </a>

                        <div class="user-menu dropdown-menu">
                            <!--<a class="nav-link" href="updateProfile.php"><i class="fa fa-user"></i>My Profile</a>-->
                            <!-- <a class="nav-link" href="changePassword.php"><i class="fa fa-cog"></i>Change Password</a> -->

                            <!-- <a class="nav-link" href="#"><i class="fa fa- user"></i>Notifications <span
                                    class="count">13</span></a> -->

                            <!-- <a class="nav-link" href="#"><i class="fa fa -cog"></i>Settings</a> -->

                            <a class="nav-link" href="logout.php"><i class="fa fa-power-off"></i>Logout</a>
                        </div>
                    </div>

                </div>
            </div>
            
        </header>

        <script src="../assets/js/main.js"></script>
