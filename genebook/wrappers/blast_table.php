<?php

// Parses the results from EBI BLAST webservice into an html table

include('check_for_data.php');
include("gb_parameters.php");
##this file takes the results from the blast queryk and returns them into a table
$gene_name = $_POST["gene"];
$genome_id = $_POST["genome"];
$pid = $_POST["pid"];

sleep(50);
while (file_exists("$ws_tmp_dir/$pid"."_$gene_name&$genome_id"."_blast_xml.xml") == false)
{}


$xml_output = simplexml_load_file("$ws_tmp_dir/$pid"."_$gene_name&$genome_id"."_blast_xml.xml");

print "<table><tr><th>Hit id</th><th>Hit accession</th><th>Description</th><th>E-value</th><th>Length</th></tr>";

foreach ($xml_output->{'SequenceSimilaritySearchResult'}->{'hits'}->{'hit'} as $hit)
{

$hit_id = $hit['id'];

#print $hit['number'];

$hit_acc =  $hit['ac'];
$hit_length = $hit['length'];
$hit_desc = $hit['description'];


#print $hit[0]->{'alignments'}->{'alignment'}->{'score'};
#print $hit[0]->{'alignments'}->{'alignment'}->{'bits'};
$e_value = $hit->{'alignments'}->{'alignment'}->{'expectation'};
#print $hit[0]->{'alignments'}->{'alignment'}->{'identity'};
#print $hit[0]->{'alignments'}->{'alignment'}->{'positives'};

print "<tr><td><a href=\"http://www.uniprot.org/uniprot/$hit_id\"</a>$hit_id</td><td>$hit_acc</td><td>$hit_desc</td><td>$e_value</td><td>$hit_length</td></tr>";

#print_r ($xml_output);
}
print "</table>";
#$count_cdd = count($xml_domain_info->DocSum);


?>
