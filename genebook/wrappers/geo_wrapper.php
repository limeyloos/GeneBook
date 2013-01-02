<?php
include('check_for_data.php');



$gene = $_POST["gene"];
$params = $_POST["widget_params"];


#$source = "http://ris-vbiolx01.roslin.ed.ac.uk/webservices/graph.php?";


$params_split = explode(" ",$params);

$acc = chop($params_split[0]);
#print $acc;




#this gets the platform the id for each exp associated with a particular locus_tag
$geo_xml = simplexml_load_file("http://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=$acc&targ=gpl&form=xml&view=full");
#print "http://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc=$acc&targ=gpl&form=xml&view=full";
#print $geo_xml->asXML();

#these xml tags can't be entered directly because of the '-' so making them into a variable first makes them xml friendly.
$data_table = "Data-Table";
$internal_data = "Internal-Data";

#Get the data for the experiment from the xml file.
$geo_data = $geo_xml->Platform->$data_table->$internal_data;


#turn the data into an array split by newline
$geo_data_lines = explode("\n", $geo_data);
#print_r ($geo_data_lines);

#get all elements in the array which contain the locus_tag
#there may be more than one result for a particular gene, so show a graph for each one.
$key = preg_grep("/^.*\t$gene\t.*/", $geo_data_lines);

#split the array element by \t and use the first part of the new array as the id;
foreach($key as $matches)
{

$columns = split("\t",$matches);
#print $matches;
$ID = $columns[0];

print $ID."\t";

#print "<span><img width=\"375\" height=\"316\" border=\"0\" usemap=\"#geomap\" src=\"backend/profileGraph.cgi?ID=$acc:$ID\" alt=\"Gene Expression Profile\"></span>";
#$image = file_get_contents("http://www.ncbi.nlm.nih.gov/geo/geo2r/backend/profileGraph.cgi?ID=$acc:$ID");
#print "<div>$image</div></br>";

#try as an iframe, should be faster
print "<iframe height = \"400\" width = \"100%\" src=\"http://www.ncbi.nlm.nih.gov/geo/geo2r/backend/profileGraph.cgi?ID=$acc:$ID\"></iframe>";



}




#http://www.ncbi.nlm.nih.gov/geo/geo2r/backend/profileGraph.cgi?ID=GSE10337:26.8.11
#http://www.ncbi.nlm.nih.gov/geo/geo2r/backend/profileGraph.cgi?ID=GSE10337:26.8.11

?>



