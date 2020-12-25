<?php
	$server_name = "localhost";
    $server_user = "root";
    $server_password = "";
	$server_database = "iseps";
	
    $conn = new mysqli($server_name,$server_user,$server_password,$server_database);
    if($conn->connect_error){
        die("Не можемо да се повежемо са базом!" . $conn->connect_error);
    }
?>