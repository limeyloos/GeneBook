<?php

//This wrapper displays the NGS pile up webservice into an iframe

include("gb_parameters.php");

$gene = $_POST["gene"];
$genome = $_POST["genome"];
$params = $_POST["widget_params"];
$params_split = explode(" ",$params);
$file_bam = $params_split[0];
$file_fasta = $params_split[1];
$buffer = $params_split[2];
$id = $params_split[3];


print "<iframe onLoad=\"calcHeight('$id');\" id=\"$id\" src=\"$cgi_url"."test_library_install.cgi?seq_id=CP002487.1&bam=$file_bam&fasta=$file_fasta&genome=$genome&gene=$gene&buffer=$buffer\" width = 0 height = 0  ></iframe>";


?>
