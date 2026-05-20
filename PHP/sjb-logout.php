<?php
session_start();
session_destroy();
header("Location: sjb-login-form.php");
exit();
?>
