<?php

// displays the output from the kegg orthology webservice into a table

include('check_for_data.php');
include("gb_parameters.php");
##this file takes the results from the kegg orthology check and returns them into a table
$gene_name = $_POST["gene"];
$genome_id = $_POST["genome"];
#$pid = $_POST["pid"];
if ($_GET["PID"])
{
	$pid = $_GET["PID"];
}
else
{
	$pid = getmypid();
}




print "$tmp_url"."$pid"."table_$gene_name&$genome_id.txt";
$open_table = file("$tmp_url"."$pid"."table_$gene_name&$genome_id.txt");
foreach ($open_table as $line)
{
print $line;
}
?>
