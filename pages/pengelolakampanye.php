<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();}
$id_pengelola = $SESSION["id_user"]
$page = isset($_GET["page"]) ?
$_GET["page"] : "list";
?>
<!DOCTYPE html>
<html>
    <head><title>Pengelola Kampanye<title></head>
    <body>
        <header>
            <nav>
                <a href=></a>
                <a href=></a>
                <a href=></a>
            <nav>
        </header>
       <main>
           <?php
                if (!isset($_SESSION['pesan'])) {
                  echo "<p>" $_SESSION['pesan'] "<p>"; 
                  unset($_SESSION['pesan'])
                }
                if ($page=='form'){include "view/form_kampanye.php;"}elseif($page=='donatur'){include "view/form_kampanye.php;"}else{include "view/form_kampanye.php;"}
            ?>
       </main>
            
    </body>
</html>
