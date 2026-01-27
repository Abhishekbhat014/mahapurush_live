<?php
/* 
 |-----------------------------------------
 | Database Connection File
 | Project: Mahapurush Live
 |-----------------------------------------
 */

$host = "localhost";
$user = "root";
$password = "";
$database = "mahapurush_live";

/* Create connection */
$conn = mysqli_connect($host, $user, $password, $database);

/* Check connection */
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* Set charset */
mysqli_set_charset($conn, "utf8mb4");
?>