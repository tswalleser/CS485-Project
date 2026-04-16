<?php
$servername = "localhost";
$username = "root";  //user name
$password = "";  //password used to login MySQL server
$dbname = "CS485_Project"; //name of the database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
