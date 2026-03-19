<?php
$path = '/var/www/html';
if (gethostname() == 'zeropoint') {
    $path = '/var/www/html/solarlogs';
}

$db = new Database('esc_db');

if (isset($_POST['email']) && $_POST['verify_email'] == true) {
    $where = "email='" . $_POST['email'] . "' AND active='1'";
    $row = $db->selectSingle('kal_users', 'user_id,naam,email,active', $where);
    if (!count($row)) {
        echo json_encode(false);
        die();
    }

    if (isset($row) &&  isset($row['user_id'])) {
        $currentDateTime = date("Y-m-d H:i:s");
        $params =  array(
            'expiry' => encrypt_decrypt($currentDateTime),
            'user' => encrypt_decrypt($row['user_id']),
        );
        $emailSender = new EmailSender();
        $to = $row['email'];
        $subject = 'Password Reset Request FROM ESC';

        $templateData = [
            'name' => $row['naam'],
            'link' => url() . '/esc/change_password.php?' . http_build_query($params),
            'logo' => "https://www.solarlogs.be/esc/images/mail_logo.png",
            'site' => 'https://www.solarlogs.be/esc'
        ];
        $result = $emailSender->sendForgetPasswordEmail($to, $subject, $templateData);

        if ($result) {
            echo "Email sent successfully.";
        } else {
            echo "Email could not be sent.";
        }
    }
}

if (isset($_GET['expiry']) && isset($_GET['user'])) {
    $expiry = encrypt_decrypt($_GET['expiry'], false);
    $userId = encrypt_decrypt($_GET['user'], false);
    if (!$expiry || !$userId) {
        header("Location:" . url());
        exit();
    }

    // calculate time difference
    $expiryDateTime = new DateTime($expiry);
    $currentDateTime = new DateTime();
    $interval = $expiryDateTime->diff($currentDateTime);
    if ($interval->h >= 2) { // 2 hours
        header("Location:" . url());
        exit();
    }
}

if (
    basename($_SERVER['PHP_SELF']) == 'change_password.php'
    && !isset($_GET['expiry']) && !isset($_GET['user'])
) {
    header("Location:" . url());
    exit();
}

if (isset($_POST['retype_password']) && isset($_POST['password']) && isset($_POST['query'])) {
    $queryStrings = getQueryString();
    if (!isset($queryStrings['user'])) {
        header("Location:" . url());
        exit();
    }
    $userId = encrypt_decrypt($queryStrings['user'], false);
    if (!$userId) {
        header("Location:" . url());
        exit();
    }
    $password = md5($_POST['password']);

    $currentDateTime = date("Y-m-d H:i:s");
    $data = array('pwd' => $password, 'password_reset_at' => $currentDateTime);
    $row = $db->update('kal_users', $data, "user_id='{$userId}'");

    if (!$row) {
        echo json_encode(false);
        exit;
    }
    echo json_encode($row);
    exit;
}

function encrypt_decrypt($currentDateTime, $flag = true)
{
    $key = "ASMNtvopXZL12&&S"; // precisely 16 bytes
    $cipher = "AES-256-CBC";

    $returnable = null;
    if ($flag == true) {
        $returnable = openssl_encrypt($currentDateTime, $cipher, $key, 0, $key);
    } else {
        $returnable = openssl_decrypt($currentDateTime, $cipher, $key, 0, $key);
    }

    return $returnable;
}

function getQueryString()
{
    $queryString = $_POST['query'];

    // Explode query string into pairs
    $paramPairs = explode('&', $queryString);

    $params = array();
    foreach ($paramPairs as $pair) {
        list($param, $value) = explode('=', $pair);
        $params[$param] = urldecode($value);
    }

    return $params;
}
