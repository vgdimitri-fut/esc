<?php
require_once 'inc/header_b.php';
require_once 'forgot_password_logic.php';
?>
<div class="container d-flex align-items-center justify-content-center vh-70">
    <div class="col-md-8 col-lg-5 col-sm-12 col-xs-12 border p-4">
        <form id="changePasswordForm">
            <div class="mb-4 text-center">
                <h3><i class="fa fa-lock fa-4x"></i></h3>
                <h2>Change Your Password</h2>
                <p>You can reset your password here. after that you will be redirected to login with your new password.</p>
            </div>
            <label for="password" class="form-label">Enter your password</label>
            <div class="input-group mb-3">
                <input type="password" class="form-control" id="password" placeholder="Password...">
                <button class="btn btn-outline-primary" type="button" id="passwordToggle">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <div class="invalid-feedback">Please enter a password.</div>
                <div class="valid-feedback"> Looks Good.</div>
            </div>
            <label for="retypePassword" class="form-label">Re-Enter your password</label>
            <div class="input-group mb-3">
                <input type="password" class="form-control" id="retypePassword" placeholder="Re-Type Password...">
                <button class="btn btn-outline-primary" type="button" id="retypePasswordToggle">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <div class="invalid-feedback"> Please retype the password. </div>
                <div class="valid-feedback"> Looks Good. </div>
            </div>
            <div class="mb-3 text-center">
                <button type="submit" class="btn btn-primary">Change password</button>
            </div>
            <div class="alert alert-success" role="alert">
            </div>
        </form>
    </div>
</div>
<?php
require_once 'inc/footer_b.php';
?>