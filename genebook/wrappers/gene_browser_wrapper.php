<?php

//displays the genebrowser webservice

include("gb_parameters.php");
$gene_name = $_POST["gene"];
$genome = $_POST["genome"];
$test = $_POST['test'];
print $test;


$homepage = file_get_contents("$ws_url/gene_browser.php?gene=$gene_name&genome=$genome");
echo $homepage;

?>
