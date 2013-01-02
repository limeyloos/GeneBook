<?php

// Gets the results from BLAST EBI webservice and displays them as an SVG file

include('check_for_data.php');
include('database_connection.php');
include("gb_parameters.php");
$gene_name = $_POST["gene"];
$genome_id = $_POST["genome"];
$padding = $_POST["padding"];
$pid = $_POST["pid"];
print $pid;


$translation = file_get_contents("$cgi_url"."sequence.cgi?gene=$gene_name&padding=$padding&type=aa");



# Service WSDL
#$wsdlUrl = 'http://www.ebi.ac.uk/Tools/services/soap/wublast?wsdl';
$wsdlUrl = 'http://www.ebi.ac.uk/Tools/services/soap/ncbiblast?wsdl'; 
# Get service proxy
$proxy = new SoapClient($wsdlUrl);
/* 
# Input parameters
$params = array();
$params['email'] = 'emily.richardson@roslin.ed.ac.uk';
$params['program'] = 'blastp';
$params['database'] = 'swissprot';
$params['align'] = 8;
$params['outformat'] = 'tabular';
$params['sequence'] = $row['translation'];
*/




$params -> {'parameters'}->{'sequence'} = $translation;
#$params -> {'parameters'}->{'sequence'} = $nuc_seq;
$params -> {'parameters'}->{'program'} = 'blastp';
#$params -> {'parameters'}->{'program'} = 'blastx';

#$params -> {'parameters'}->{'database'}[1] = 'uniref90';
$params -> {'parameters'}->{'database'}[0] = 'uniprotkb_trembl';
$params -> {'parameters'}->{'database'}[1] = 'uniprotkb_swissprot';
$params -> {'parameters'}->{'stype'} = 'protein';
#$params -> {'parameters'}->{'stype'} = 'dna';
#$params -> {'parameters'}->{'sequence'} = $row['translation'];
#$params -> {'parameters'}->{'sequence'} = $row['translation'];

$params -> {'email'} = 'emily.richardson@roslin.ed.ac.uk';




#NEED TO GET SEQUENCE FROM DATABASE QUERY



# Input data


$parameters = $proxy->getParameters();
#print_r ($parameters);

#$test = $parameters->{'parameters'}->{'id'}[0];

$test -> {'parameterId'} = 'database';

#print_r ($test);
$parameters_details = $proxy->getParameterDetails($test);
#print_r ($parameters_details);


# Submit the job
$jobId = $proxy->run($params);
#print_r ($jobId);	



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
 

# Get the result
#$result = $proxy->poll($jobId, 'toolxml');
$result_type = $proxy->getResultTypes($jobId);

#print_r($result_type);

#$type -> {'type'} = 'xml';

$jobId -> {'type'} = 'visual-svg';

#$result = base64_decode($proxy->getResult($jobId));
$result_svg = $proxy->getResult($jobId);

#$jobId2 = $jobId;
#$jobId2 -> {'type'} = 'complete-visual-svg';
#$result_complete_svg = $proxy->getResult($jobId2);

#$jobId3 = $jobId;
#$jobId3 -> {'type'} = 'ffdp-subject-svg';
#$result_ffdps_svg = $proxy->getResult($jobId3);

$jobId4 = $jobId;
#$jobId4 -> {'type'} = 'ffdp-query-svg';
#$result_ffdpq_svg = $proxy->getResult($jobId4);

$jobId4 -> {'type'} = 'xml';

$result_xml = $proxy->getResult($jobId4);



#print gettype($result);

$output_svg = $result_svg->{'output'};
#$output_comp_svg = $result_complete_svg->{'output'};
#$output_ffdps_svg = $result_ffdps_svg->{'output'};
#$output_ffdpq_svg = $result_ffdpq_svg->{'output'};
$output_xml = $result_xml->{'output'};

#print "<img src= $output type=\"image/jpg\"></img>";
#print $output;

$svg_lines = explode("\n", $output_svg);
#print_r ($svg_lines);

#$output_file = "/var/www/tmp/blast.svg";
#$fh = fopen($output_file, 'wb');
#fwrite ($fh,$output_svg);



include ("parsesvg.php");






$fa = fopen("$ws_tmp_dir/$pid"."_$gene_name&$genome_id"."_blast_comp.svg", "w+");
fwrite ($fa,$output_svg);

#$fb = fopen("/var/www/tmp/$pid"."_$gene_name&$genome_id"."_blast_ffdps.svg", "w+");
#fwrite ($fb,$output_ffdps_svg);

#$fc = fopen("/var/www/tmp/$pid"."_$gene_name&$genome_id"."_blast_ffdpq.svg", "w+");
#fwrite ($fc,$output_ffdpq_svg);

$fd = fopen("$ws_tmp_dir/$pid"."_$gene_name&$genome_id"."_blast_xml.xml", "w+");
fwrite ($fd,$output_xml);

#fwrite ($fh,$output_svg);
#print "<h1>complete visual svg</h1>";
#print "<object data= \"/tmp/$pid"."_$gene_name&$genome_id"."_blast_comp.svg\" type=\"svg/xml\"></object>";
print "<h1>Swissprot hits to $gene_name</h1>";
print "<object width= 1000 data= \"$tmp_url"."$pid"."_$gene_name&$genome_id"."_blast.svg\" type=\"svg/xml\"></object>";
#print "<h1>ffdp subject</h1>";
#print "<object data= \"/tmp/$pid"."_$gene_name&$genome_id"."_blast_ffdps.svg\" type=\"svg/xml\"></object>";
#print "<h1>ffdp query</h1>";
#print "<object data= \"/tmp/$pid"."_$gene_name&$genome_id"."_blast_ffdpq.svg\" type=\"svg/xml\"></object>";
//echo $result;

/*

$start_pos = strpos($result, ">");
$end_pos = strpos($result, 'Query:');
$string_diff = $end_pos-$start_pos;
$blast_info = substr($result, $start_pos, $string_diff);
//echo $blast_info;

#echo "@@@@@@@@".$end_pos."!!!!!!!!!!";
#echo "EEEEEEEE".$start_pos."eeeeeeeee";

*/




?>
