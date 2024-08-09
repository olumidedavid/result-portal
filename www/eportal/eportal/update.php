<!DOCTYPE html>
<html>
<head>
    <title>Update Student Information</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body>
    <div class="w3-container">
        <h2>Update Student Information</h2>
        <form method="post" action="update_student.php" class="w3-container w3-card-4 w3-light-grey w3-text-blue w3-margin">
            <h2 class="w3-center">Update Details</h2>
            
            <div class="w3-row w3-section">
                <div class="w3-col" style="width:50px"><i class="w3-xxlarge fa fa-id-badge"></i></div>
                <div class="w3-rest">
                    <input class="w3-input w3-border" name="matricNo" type="text" placeholder="Matric Number" required>
                </div>
            </div>

            <div class="w3-row w3-section">
                <div class="w3-col" style="width:50px"><i class="w3-xxlarge fa fa-calendar"></i></div>
                <div class="w3-rest">
                    <input class="w3-input w3-border" name="dob" type="date" placeholder="Date of Birth" required>
                </div>
            </div>

            <div class="w3-row w3-section">
                <div class="w3-col" style="width:50px"><i class="w3-xxlarge fa fa-phone"></i></div>
                <div class="w3-rest">
                    <input class="w3-input w3-border" name="phone" type="text" placeholder="Phone" required>
                </div>
            </div>

            <div class="w3-row w3-section">
                <div class="w3-col" style="width:50px"><i class="w3-xxlarge fa fa-envelope"></i></div>
                <div class="w3-rest">
                    <input class="w3-input w3-border" name="email" type="email" placeholder="Email" required>
                </div>
            </div>

            <button class="w3-button w3-block w3-section w3-blue w3-ripple w3-padding">Update</button>
        </form>
    </div>
</body>
</html>
