<?php
session_start();

/* ---------------------------
   CLEAR ALL SESSION VARIABLES
---------------------------- */
$_SESSION = [];

/* ---------------------------
   DESTROY THE SESSION
---------------------------- */
session_destroy();

/* ---------------------------
   CLEAR REMEMBER ME COOKIES
---------------------------- */
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}
if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}

/* ---------------------------
   REDIRECT TO LOGIN WITH MESSAGE
---------------------------- */
session_start();
$_SESSION['logout_message'] = "You have successfully logged out. See you soon!";
header("Location: login.php");
exit();
?>