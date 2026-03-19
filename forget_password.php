<?php
require_once 'inc/header_b.php';
?>

<div class="container d-flex align-items-center justify-content-center vh-70">
    <div class="col-md-8 col-lg-5 col-sm-12 col-xs-12 border p-4">
        <form id="forgotPasswordEmailForm" autocomplete="off">
            <div class="mb-4 text-center">
                <h3><i class="fa fa-lock fa-4x"></i></h3>
                <h2>Forgot Your Password?</h2>
                <p>Enter the email and we will send you a password reset link on your provided email address if exists in our database</p>
            </div>
            <label for="email" class="form-label">Enter your email</label>
            <div class="input-group mb-3">
                <input type="email" class="form-control" id="email" placeholder="e.g. email@futech.com">
                <button type="submit" id="submitBtn" class="btn btn-primary">Send Email <i class="fa fa-arrow-right"></i></button>
                <div class="invalid-feedback">Please enter your email to proceed.</div>
                <div class="valid-feedback"> Looks Good.</div>
            </div>
        </form>
    </div>
</div>

<?php
require_once 'inc/footer_b.php';
?>