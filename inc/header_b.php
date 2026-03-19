<?php
$path = '/var/www/html';
if (gethostname() == 'zeropoint') {
    $path = '/var/www/html/solarlogs';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="SHORTCUT ICON" href="favicon.ico" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ESC - Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>

        .vh-70 {
            height: 70vh !important;
        }
    </style>
</head>

<body>
    <!-- Centered logo container -->
    <div>
        <a href="./"><img src="<?php echo url() ?>/esc/images/ESC_logo.png"  class="latest-logo mx-auto d-block" alt="Futech logo"></a>
    </div>