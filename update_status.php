<?php

if (isset($_REQUEST['status'])) {
    session_start();
    echo $_SESSION['status'];
} else {
    session_start();
    echo $_SESSION['progress'];
}

?>
