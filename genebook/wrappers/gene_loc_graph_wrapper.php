<?php

// displays the gene location graph from the in house webservice

include('check_for_data.php');
include("gb_parameters.php");


$gene_name = $_POST["gene"];
$params = $_POST["widget_params"];



$params_split = explode(" ",$params);

$file_param = chop($params_split[0]);
#print $file_param;
$id = $params_split[1];

print "<iframe id=\"$id\" onLoad=\"calcHeight('$id');\" src=\"$ws_url"."/gene_loc_graph.php?gene=$gene_name&file=$file_param\" width=100% height = 500></iframe>";
#print "$ws_url"."/gene_loc_graph.php?gene=$gene_name&file=$file_param";
?>
