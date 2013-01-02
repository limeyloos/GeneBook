<?php
include('check_for_data.php');
include("gb_parameters.php");

$gene_name = $_POST["gene"];
$params = $_POST["widget_params"];





$params_split = explode(" ",$params);
#split the file param
$types = explode(":",$params_split[2]);
$file_param = $params_split[0];
$widget = $params_split[1];
$id = $params_split[2];
?>

  
<form>
 <?php
if ($gene_name)
{
#loop this foreach type
foreach ($types as $type)
{

print "<input id=\"$id\" type=\"radio\" name=\"1$widget\" value=\"$ws_url"."graph.php?gene=$gene_name&type=$type&file=$file_param\">$type";

}

print " </form><div ></div>";





print "<iframe src=\"$ws_url"."graph.php?gene=$gene_name&type=$types[0]&file=$file_param&title=$title\" id=\"$widget\" width=100% height=500 ></iframe>";

print "<script>";


print "$(\"input[name=1$widget]\").click(function() {";
  
 

print "document.getElementById(\"$widget\").src=$(\"input[name=1$widget]:checked\").val();";



print "});";
print "</script>";
}


?>



