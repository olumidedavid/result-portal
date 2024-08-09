
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* student-update.css */

.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
    padding-top: 60px;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 90%;
    max-width: 500px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

    </style>


<div class="w3-container">
    <!--<h2>Student Dashboard</h2>-->
    <button id="updateDetailsBtn" class="w3-button w3-blue">PLEASE CLICK IF YOU HAVEN'T UPDATED YOUR EMAIL, PHONE NUMBER, OR DATE OF BIRTH YET</button>
</div>

<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Update Details</h2>
        <form id="updateForm" class="w3-container w3-card-4 w3-light-grey w3-padding">
            <p>
                <label for="dob">Date of Birth</label>
                <input class="w3-input w3-border" type="date" name="dob" id="dob">
            </p>
            <p>
                <label for="phone">Phone</label>
                <input class="w3-input w3-border" type="text" name="phone" id="phone">
            </p>
            <p>
                <label for="email">Email</label>
                <input class="w3-input w3-border" type="email" name="email" id="email">
            </p>
            <button type="submit" class="w3-button w3-blue">Update</button>
        </form>
        <div id="response" class="w3-panel w3-margin-top"></div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var modal = $('#updateModal');

        $('#updateDetailsBtn').on('click', function() {
            modal.show();
        });

        $('.close').on('click', function() {
            modal.hide();
        });

        $(window).on('click', function(event) {
            if ($(event.target).is(modal)) {
                modal.hide();
            }
        });

        $('#updateForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: 'update_student.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#response').html(response);
                    setTimeout(function() {
                        modal.hide();
                    }, 2000);
                },
                error: function() {
                    $('#response').html('<div class="w3-panel w3-red">An error occurred. Please try again.</div>');
                }
            });
        });
    });
</script>

