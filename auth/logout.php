<?php
session_start();
$_SESSION = array();
session_destroy();

// Home page එකට redirect කිරීම
header("Location: ../index.php");
exit();
?>