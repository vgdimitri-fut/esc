<link rel="stylesheet" href="./css/style.css" type="text/css"></link>
<!-- Include Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<!-- Script to toggle password visibility -->
<script>
    let alertSuccess = $('.alert-success');
    alertSuccess.hide();
    $(document).ready(function() {
        $("#forgotPasswordEmailForm").submit(function(e) {
            e.preventDefault();
            let invalidFeedback = $(".invalid-feedback");
            let validFeedback = $(".valid-feedback");
            let emailField = $("#email");
            let submitBtn = $("#submitBtn");
            let emailValue = emailField.val();
            let allGood = true;


            // Reset previous validation states
            emailField.removeClass("is-invalid").removeClass('is-valid');

            // Check for correct email
            // Check for empty email
            if (emailValue === "") {
                emailField.addClass("is-invalid");
                allGood = false;
            } else if (isEmail(emailValue) == false) {
                emailField.addClass("is-invalid");
                invalidFeedback.text("The email format is not correct");
                allGood = false;
            } else {
                emailField.addClass("is-valid");
                allGood = true;
            }
            console.log("All Good "+ allGood);

            if(allGood === true) {
                submitBtn.text("Sending...").prop('disabled', true);
                emailField.removeClass("is-valid").removeClass('.is-invalid');
                $.ajax({
                    type: "POST",
                    url: 'forgot_password_logic.php',
                    data: { 'email' : emailValue, 'verify_email': true },
                    success: function(response){
                        submitBtn.text("Send Email").prop('disabled', false);
                        console.log(response);
                        if(response == 'false') {
                            emailField.addClass("is-invalid").removeClass('is-valid');
                            invalidFeedback.text("The email does not exist. Maybe the account is inactive or deleted. Check with your admin");
                            return;
                        }
                        emailField.addClass("is-valid").removeClass('is-invalid').val('');
                        validFeedback.text("Email has been sent successfully at " + emailValue + " Please check your inbox or spam/junk");
                        if (response.length > 0) {
                            // let data = $.parseJSON(response);
                        } else {
                            // login for - no record found
                        }
                    }
                });
            }
        });

        function isEmail(email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }

        $("#passwordToggle").click(function() {
            var passwordField = $("#password");
            var fieldType = passwordField.attr("type");
            var icon = $(this).find("i");

            if (fieldType === "password") {
                passwordField.attr("type", "text");
                icon.removeClass("fa-eye").addClass("fa-eye-slash");
            } else {
                passwordField.attr("type", "password");
                icon.removeClass("fa-eye-slash").addClass("fa-eye");
            }
        });

        $("#retypePasswordToggle").click(function() {
            var retypePasswordField = $("#retypePassword");
            var fieldType = retypePasswordField.attr("type");
            var icon = $(this).find("i");

            if (fieldType === "password") {
                retypePasswordField.attr("type", "text");
                icon.removeClass("fa-eye").addClass("fa-eye-slash");
            } else {
                retypePasswordField.attr("type", "password");
                icon.removeClass("fa-eye-slash").addClass("fa-eye");
            }
        });

        $("#changePasswordForm").submit(function(e) {
            e.preventDefault();
            var validFeedback = $(".valid-feedback");
            var passwordField = $("#password");
            var retypePasswordField = $("#retypePassword");
            var passwordValue = passwordField.val();
            var retypePasswordValue = retypePasswordField.val();
            let allGood = true;

            // Reset previous validation states
            passwordField.removeClass("is-invalid").removeClass('is-valid');
            retypePasswordField.removeClass("is-invalid");

            // Check for empty passwords
            if (passwordValue === "") {
                passwordField.addClass("is-invalid");
                allGood = false;
            } else {
                passwordField.addClass("is-valid");
            }

            if (retypePasswordValue === "") {
                retypePasswordField.addClass("is-invalid");
            }

            if (passwordValue !== retypePasswordValue) {
                retypePasswordField.removeClass("is-valid").addClass("is-invalid");
                allGood = false;
            } else {
                if (retypePasswordValue) retypePasswordField.addClass("is-valid");
            }

            if(allGood == true) {
                $.ajax({
                    type: "POST",
                    url: 'forgot_password_logic.php',
                    data: { 'password' : passwordValue, 'retype_password': retypePasswordValue, 'query': '<?php echo $_SERVER['QUERY_STRING'];?>' },
                    success: function(response){
                        console.log(response);
                        if(response == 'false') {
                            passwordField.addClass("is-invalid").removeClass('is-valid');
                            invalidFeedback.text("Something went wrong, please contact admin");
                            return;
                        }
                        passwordField.addClass("is-valid").removeClass('is-invalid');
                        alertSuccess.show();
                        alertSuccess.text("You password changed successfully, please login");
                        setTimeout(function(){
                            window.location.href = "./";
                        }, 3000);
                        if (response.length > 0) {
                            let data = $.parseJSON(response);
                        } else {
                            // login for - no record found
                        }
                    }
                });
            }
        });
    });
</script>
</body>

</html>