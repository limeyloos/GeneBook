<?php
//-------------------Connects to DB_Tree mysql database-----------------//
$dbhost = 'localhost';
$dbuser = 'micropath';
$dbpass = 'micropath';
$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die
('Error connecting to mysql');

$dbname = 'micropath';
mysql_select_db($dbname) or die
('Error cannot connect to database');

//-------------------------End of Connection Code----------------------------//
?>
