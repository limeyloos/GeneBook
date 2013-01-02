<?php

// displays a dynamic list of domains from the CDD using the eutilities webservice

include('check_for_data.php');
include("gb_parameters.php");
/*This script is designed to access and retrieve data from Entrez using eutils. 
Currently it;
	Submits a gene id (gi)
	Gets any corresponding domains from Entrez's CDD
	Finds any gene accessions associated with that CDD accession
	
The output is;
	Results for a particular gene
	Gene list of genes with similar domains.
*/
include('database_connection.php');

$gene_name = $_POST['gene'];
$genome_id = $_POST['genome'];



$locus_tag_query = "select locus_tag from gene where Genome_ID =\"".$genome_id."\" and (gene =\"".$gene_name."\" or locus_tag =\"".$gene_name."\")";
$query_results = mysql_query($locus_tag_query);
while ($row=mysql_fetch_array($query_results))
{
	$locus_tag = $row['locus_tag'];
}


//Gets gene id
$xml_gene_id = simplexml_load_file("http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=gene&term=".$gene_name."[gene name]");

$gene_id = $xml_gene_id->IdList->Id;
echo $gene_id;

//Gets CDD domain information
$xml_domain_match = simplexml_load_file('http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?dbfrom=gene&db=cdd&id='.$gene_id);
#print $xml_domain_match."E";
$count = count($xml_domain_match->LinkSet->LinkSetDb->Link);
print $count;

$cdd_array = array();
for ($i = 0; $i < $count;)
//array to hold the domain IDs

{
	//put into array then make unique
	array_push($cdd_array, $xml_domain_match->LinkSet->LinkSetDb->Link[$i]->Id);
	$i++;
}
$unique_cdds = array_unique($cdd_array);
//print_r ($cdd_array);


//turn array into comma delimited string for request
$cdd_id_string = array_reduce($unique_cdds,"delimiter");
//print $cdd_id_string;

//foreach ($unique_cdds as $cdd_id) 
//{
//print $cdd_id;
$xml_domain_info = simplexml_load_file("http://www.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=cdd&id=$cdd_id_string");
//print_r ($xml_domain_info);
$count_cdd = count($xml_domain_info->DocSum);




//echo "<script> $('.accordion').('disable');</script>";
//echo "<script> $('.accordion').('enable');</script>";

//echo "<div class=\"accordion\">";

echo "<ul>";
for ( $j = 0; $j <$count;)
{
$domain_accession = $xml_domain_info->DocSum[$j]->Item[0];
$domain_title = $xml_domain_info->DocSum[$j]->Item[1];
$domain_abstract = $xml_domain_info->DocSum[$j]->Item[2];

//print $domain_abstract;

echo "<li>";
echo "<a class=\"accordion-header\" href =\"#\">$domain_accession $domain_title</a>";
echo "<div>$domain_abstract</div>";
echo "</li>";


$j++;
}
echo "</ul>";
echo "<script> 
$('.accordion').accordion('destroy');
$('.accordion').accordion({active:false, collapsible:true, autoHeight:false});</script>";
//echo "</div>";
//Possibly sort by group eg COGs etc and then make into a tree directory. COG matches, pfam matches etc. Could it be possible to click on one and then it shows the corresponding gene list? 'Onclick' of certain part of description

//}
/*
//Gets gene list
$xml_list = simplexml_load_file('http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?dbfrom=cdd&db=protein&id=117998');

//print_r($xml_output);
$count = count($xml_list->LinkSet->LinkSetDb[1]->Link);
//print $count;
for ($i = 0; $i < $count;)
{
	//print $xml_list->LinkSet->LinkSetDb[1]->Link[$i]->Id;
	//print "\n";
	$i++;
}
*/

function delimiter($v1,$v2)
{
return $v1 . "," . $v2;
}


?>
