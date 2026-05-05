<?php

    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "crowfunding";
    $conn = mysqli_connect($host, $username, $password, $database);

    if(!$conn){
        die("Koneksi gagal");
    }

?>