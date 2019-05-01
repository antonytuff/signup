<?php
$servername = "localhost";
$username = "root";
$password = "";
$dBName="login";

// Create connection
$conn = new mysqli($servername, $username, $password,$dBName);

//Check connection
//if ($conn->connect_error) {
   // die("Connection failed: " . $conn->connect_error);
//}
//echo "Connected successfully";

//Counts the rows od Records of the database
function row_count($result){
    return mysqli_num_rows($result);
}


//Performs a query against the database 
function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn,$string);
}



//Performs a query against the database  every time we want to make a conncetion
function query($query){
    global $conn;
    return mysqli_query($conn,$query);
}


//Confirm the query for database is good
function confirm($result){
    global $conn;
    if(!$result){
        die("QUERY FAILED" .mysqli_error($conn));
    }
}


//Fetches a result row as an associative, a numeric array, or both
function fetch_array($result){
    global $conn;

    return mysqli_fetch_array($result);
}