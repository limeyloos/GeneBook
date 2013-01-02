<?php

// display the sequence from the sequence webservice

include('check_for_data.php');
include("gb_parameters.php");
$gene_name = $_POST['gene'];
$genome = $_POST['genome'];

$params = $_POST["widget_params"];

$homepage = file_get_contents("$cgi_url"."sequence.cgi?gene=$gene_name&genome=$genome");
echo "<div style='word-wrap:break-word'>$homepage</div>";

?>