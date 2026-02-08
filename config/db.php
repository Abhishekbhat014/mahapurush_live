<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "mahapurush_mandir";

$con = mysqli_connect($host, $user, $password, $database);


if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}


mysqli_set_charset($con, "utf8mb4");
