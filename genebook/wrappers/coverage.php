<?php

// displays the NGS coverage webservice output

include("gb_parameters.php");

$gene = $_POST["gene"];
$genome = $_POST["genome"];
$params = $_POST["widget_params"];
$params_split = explode(" ",$params);
$file_bam = $params_split[0];
$file_fasta = $params_split[1];
$buffer = $params_split[2];
$id = $params_split[3];


$test = file_get_contents("$cgi_url"."coverage_plot.cgi?seq_id=CP002487.1&bam=$file_bam&fasta=$file_fasta&genome=$genome&gene=$gene&buffer=5000");
print $test;

?>




