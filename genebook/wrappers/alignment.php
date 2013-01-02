<?php

//this script gets orthologs from kegg and submits these to the clustal webservice for alignment, this is then displayed via jalview applet

include('check_for_data.php');
include("gb_parameters.php");
$wsdlUrl = 'http://soap.genome.jp/KEGG.wsdl';
$serv = new SoapClient($wsdlUrl);
$offset = 1;
$limit = 5;

include('database_connection.php');

$gene_name = $_POST["gene"];
$genome_id = $_POST["genome"];
$pid = $_POST["pid"];

print $pid;

//get the kegg for for the gene
$kegg_3_letters_gene = "select kegg_code, locus_tag from gene, genome where genome.genome_id =\"".$genome_id."\" and   genome.genome_id = gene.Genome_ID and gene =\"".$gene_name."\" ";
$kegg_3_letters_locus_tag = "select kegg_code, locus_tag from gene, genome where genome.genome_id =\"".$genome_id."\" and   genome.genome_id = gene.Genome_ID and locus_tag =\"".$gene_name."\" ";

//get the genome codes for all genomes in the database;
$all_kegg_codes = "select kegg_code, locus_tag from gene, genome";


#print $kegg_3_letters_locus_tag;
$gene_array = array();

$query_results = mysql_query($kegg_3_letters_locus_tag);

if (mysql_num_rows($query_results) == 0)
{
	print "no results";
	$query_results = mysql_query($kegg_3_letters_gene);
}


while ($row=mysql_fetch_array($query_results))
{
	$kegg_code = $row['kegg_code'];
	$gene = $row['kegg_code'].":".$row['locus_tag'];
	
	array_push($gene_array, $gene);
	#print $gene;
	$ko_results = $serv->get_ko_by_gene($gene);
	
}




//get the genome codes for all genomes in the database;
$all_kegg_codes = "select specificity, serovar, strain, kegg_code from genome";
$kegg_query_results = mysql_query($all_kegg_codes);

$kegg_codes_array = array();
$specificity_array = array();
while ($row=mysql_fetch_array($kegg_query_results))
{
	//print $row['kegg_code'];
	array_push($kegg_codes_array, $row['kegg_code'].":".$row['specificity'].":".$row['serovar'].":".$row['strain']);
	#$kegg_codes_array[$row['kegg_code']] = "";
	//$specificity = $row['specificity'];
}

#print_r ($kegg_codes_array);

$unique_codes = array_unique($kegg_codes_array);
#print_r ($unique_codes);
$gene_matches = array();

//just incase there are multiple KOs for the query gene
foreach ($ko_results as $ko_res)
{
	$ko_genes = $serv->get_genes_by_ko($ko_res, 'all');
}

#goes through the whole array and removes the entry_id and the locus tag off the end.
#print_r ($ko_genes);
$kegg_results_array = array();

$query_sequences = "";

foreach ($unique_codes as $u_c)
{

	$kegg_spec = explode(":", $u_c);
	#print_r ($kegg_spec);
	foreach ($ko_genes as $ko_gene)
	{
	$entry_id = explode(":", $ko_gene->{'entry_id'});
	#print_r ($entry_id);
	#print $entry_id[0];
	#print $kegg_spec[0]."\n";	
	if ($kegg_spec[0] == $entry_id[0])
		{
			#array_push($kegg_results_array, $entry_id[1]);
			#print $entry_id[1];
			$seq_query = "SELECT translation FROM `feature` WHERE feature = \"CDS\" and locus_tag = \"$entry_id[1]\"";
		$seq_query_results = mysql_query($seq_query);
		while ($row=mysql_fetch_array($seq_query_results))
		{
			$translation = $row['translation'];
			$query_sequences = "$query_sequences>$entry_id[1]\n$translation\n";
		}
		}	
	
	}
	
}

#print $query_sequences;

#print_r ($kegg_results_array);

#$ortholog_names = array_intersect_key($kegg_results_array, $kegg_codes_array);
#print_r($ortholog_names);


#print $query_sequences;



# Service WSDL
#$wsdlUrl = 'http://www.ebi.ac.uk/Tools/webservices/wsdl/WSClustalW2.wsdl';
#$wsdlUrl = 'http://www.ebi.ac.uk/Tools/webservices/wsdl/WSKalign.wsdl';
#$wsdlUrl = 'http://www.ebi.ac.uk/Tools/webservices/wsdl/WSTCoffee.wsdl';
#$wsdlUrl = 'http://www.ebi.ac.uk/Tools/services/soap/mafft?wsdl';
$wsdlUrl = "http://www.ebi.ac.uk/Tools/services/soap/clustalw2?wsdl";
# Get service proxy
$proxy = new SoapClient($wsdlUrl);






# Input parameters kalign

$params -> {'email'} = 'emily.richardson@roslin.ed.ac.uk';
$params -> {'parameters'}->{'sequence'} = $query_sequences;
$params -> {'parameters'}->{'alignment'} = "fast";
#$params -> {'parameters'}->{'clustering'} = "UPGMA";

#$params['async'] = TRUE;
#$params['moltype'] = 'P';
#$params['gpo'] = '';
#$params['gpe'] = '';
#$params['tgpe'] = '';
#$params['stype'] = 'protein';
#$params['sequence'] = '>Test1\nITTSQDLQWLVQPTLISSMAQSQGQPLASQPPAVDPYDMPGTSYSTPGLSAYSTGGASGSITTSQDLQWLVQPTLISSMAQSQGQPLASQPPVVDPYDMPGTSYSTPGMSGYSSGGASGSITTSQDLQWLVQPTLISSMAQSQGQPLASQPPVVDPYDMPGTSYSTPGMSGYSSGGASGSITTSQDLQWLVQPTLISSMAQSQGQPLASQPPVVDPYDMPGTSYSTPGMSGYSSGGASGS\n>Test2\nITTSQDLQWLSSMAQSQGQPLASDPYDMPGTSYSTGMSGYSSGGASGSITLISSMAQSQGQPLASQPPVVDPYDPG';

//print $row['translation'];
#$seq1 = ">Test1\nISQDLQWLVQPTLISSMAQSQGQPLASQPPAVDPYDMPGTSYSTPGLSAYSTGGASGSITTSQDLQWLVQPTLISSMAQSQGQPLASQPPVVDPYDMPGTSYSTPGMSGYSSGGASGSITTSQDLQWLVQPTLISSMAQSQGQPLASQPPVVDPYDMPGTSYSTPGMSGYSSGGASGSITTSQDLQWLVQPTLISSMAQSQGQPLASQPPVVDPYDMPGTSYSTPGMSGYSSGGASGS\n>Test2\nITTSQDLQWLSSMAQSQGQPLASDPYDMPGTSYSTGMSGYSSGGASGSITLISSMAQSQGQPLASQPPVVDPYDPG";
#print $seq1;
# Input data
#$data = array();
#$data[0]['type'] = 'sequence';
#$data[0]['content'] = $query_sequences;
 
$test -> {'parameterId'} = 'clustering';
$parameters_details = $proxy->getParameterDetails($test);
#print_r ($parameters_details);





# Submit the job
#$jobId = $proxy->runClustalW($params, $data);
$jobId = $proxy->run($params);
#echo "$jobId\n";



# Poll till job finishes
$status -> {'status'} = 'PENDING';
while(strcmp($status->{'status'}, 'RUNNING') == 0 || strcmp($status->{'status'}, 'PENDING') == 0) {
  $status = $proxy->getStatus($jobId);
  #print_r ($status);
  #echo "$status\n";
  if(strcmp($status->{'status'}, 'RUNNING') == 0 || strcmp($status->{'status'}, 'PENDING') == 0) {
    sleep(10);
  }
}



$resultTypes = $proxy->getResultTypes($jobId);
#print_r ($resultTypes);

#print_r($resultTypes);
# Get the result
#$result = $proxy->poll($jobId, $resultTypes[1]->{type});




$jobId -> {'type'} = 'tree';
$result = $proxy->getResult($jobId);
$result_tree = $result->{'output'};
#print $result;
#$myfile_dir = "../files/";
#$myFile = "output_aln.txt";
$fh2 = fopen("$ws_tmp_dir/$gene_name"."output_tree.dnd", 'w') or die("can't open file");
fwrite($fh2, $result_tree);
fclose($fh2);

$jobId2 = $jobId;
$jobId2 -> {'type'} = 'aln-clustalw';
$result = $proxy->getResult($jobId2);
$result_out = $result->{'output'};
#print $result;
#$myfile_dir = "../files/";
#$myFile = "output_aln.txt";
$fh = fopen("$ws_tmp_dir/$gene_name"."output_aln.clustalw", 'w') or die("can't open file");
fwrite($fh, $result_out);
fclose($fh);

/*

echo "<object >";
#echo "width=\"min\" height=\"min\"";
#echo "archive=\"Jalview/jalviewApplet.jar\" >";
echo "<param name=\"code\" \"jalview.bin.JalviewLite\">";
echo "<param name=\"archive\" value=\"Jalview/jalviewApplet.jar\">";
echo "<param name=\"file\" value=\"$myFile\">";
echo "<param name=\"APPLICATION_URL\" value=\"http://www.jalview.org/services/launchApp\">";
echo "<param name=\"showbutton\" value=\"false\">"; 

echo "</object>";
*/
#print "$tmp_url"."$gene_name"."output_aln.clustalw";
echo "<table><td><applet codebase = \"/gb/sites/all/js\" code=\"jalview.bin.JalviewLite\"";
echo "width=\"100\" height=\"50\"";
echo "archive=\"Jalview/jalviewApplet.jar\">";
echo "<param name=\"file\" value=\"$tmp_url"."$gene_name"."output_aln.clustalw\">";
echo "<param name=\"tree\" value=\"$tmp_url"."$gene_name"."output_tree.dnd\">";
echo "<param name=\"APPLICATION_URL\" value=\"http://www.jalview.org/services/launchApp\">";
#echo "<param name=\"embedded\" value=\"true\">"; 
#echo "<param name=\"showbutton\" value=\"false\">"; 
echo "</applet></td>";






