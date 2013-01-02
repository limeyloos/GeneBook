<?php

//displays the results from the kegg ortholog genebrowser

include("gb_parameters.php");
$gene_name = $_POST["gene"];
$genome = $_POST["genome"];


$homepage = file_get_contents("$ws_url"."ko_genebrowser.php?gene=$gene_name&genome=$genome");
echo $homepage;

?>
