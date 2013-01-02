<?php

// Displays the results from the correlation matrix webservice and parses into an html table

include('check_for_data.php');
include("gb_parameters.php");
$gene_name = $_POST['gene'];
$genome = $_POST['genome'];

$params = $_POST["widget_params"];



$params_split = explode(" ",$params);

$file_param = chop($params_split[0]);
#print $file_param;



$results_number = 10;

$correlation_xml = simplexml_load_file("$ws_url"."correlation_matrix.php?gene=$gene_name&file=$file_param");


print "<table border = 1 cellpadding = 4><th>rank</th><th>locus tag</th><th>correlation value</th>";
foreach( $correlation_xml as $rank )
{

	$ranking = $rank;
	$locus_tag = $rank->locus_tag;
	$correlation = $rank->correlation_value;
	

	#print $ranking;

	#stops at user defined number
	if ($ranking > $results_number)
	{
	break;
	}

	print "<tr><td>$ranking</td><td><a href = \"$rel_web_url&gene=$locus_tag&genome=$genome\">$locus_tag</a><td>$correlation</td></tr>";

		

	

	

}
print "</table>";

?>




