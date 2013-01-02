<?php
include('check_for_data.php');

// gets the pathways assocaited with the feature using KEGG webservice, displays the pathways dynamically

$wsdlUrl = 'http://soap.genome.jp/KEGG.wsdl';
$serv = new SoapClient($wsdlUrl);
$offset = 1;
$limit = 5;

include('database_connection.php');
include("gb_parameters.php");

$gene_name = $_POST["gene"];
$genome_id = $_POST["genome"];

$kegg_3_letters = "select kegg_code, locus_tag from gene, genome where genome.genome_id =\"".$genome_id."\" and   genome.genome_id = gene.Genome_ID and (gene =\"".$gene_name."\" or locus_tag = \"".$gene_name."\")";
//print $kegg_3_letters;
$gene_array = array();

$query_results = mysql_query($kegg_3_letters);


while ($row=mysql_fetch_array($query_results))
{
	$kegg_code = $row['kegg_code'];
	$gene = $row['kegg_code'].":".$row['locus_tag'];
	array_push($gene_array, $gene);
	//print $gene;
	//$ko_results = $serv->get_ko_by_gene($gene);
	
}
//list of all pathways and descriptions for the genome
$path_definition = $serv->list_pathways($kegg_code);

#<script type="text/javascript" src="/gb/sites/all/js/jquery.fancybox-1.3.4/jquery-1.4.3.min.js"></script>
#<script>$(document).ready(function() { $("a.zoom3").fancybox({});})</script>
#
#
#
#<script type="text/javascript" src="/gb/sites/all/js/jquery.fancybox-1.3.4/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
#<script type="text/javascript" src="/gb/sites/all/js/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
#<link type="text/css" rel="stylesheet" media="all" href="/gb/sites/all/js/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.css" />
?>
<script type="text/javascript" src="/gb/sites/all/js/jquery.fancybox-1.3.4/jquery-1.4.3.min.js"></script>
<script type="text/javascript" src="/gb/sites/all/js/jquery.fancybox-1.3.4/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
<script type="text/javascript" src="/gb/sites/all/js/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link type="text/css" rel="stylesheet" media="all" href="/gb/sites/all/js/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.css" />

<script>$(document).ready(function() { $("a.zoom3").fancybox({});})</script>
<?php






	// Get the pathways specific to the gene in question
	$path_results = $serv->get_pathways_by_genes($gene_array);
	//print "path results";
	#print_r ($path_results);
	print "<h2>".$gene_name."</h2>";
	
	
	
	//make the accordion unordered list for images
	#print "<ul>";
	foreach ($path_definition as $path_def)
	{ 
		
		//$key = array_search($pathway, $path_def); 
		//print_r ($path_def);
		
		//print $pathway;
		//print "key".$key;
		//print_r ($path_definition);
		foreach ($path_results as $path_res)
		{
			$test =$path_def->{'entry_id'};
			//print $test;
			//print "<-test path->".$path_res;
			
			if ($test == $path_res)
			{
				$definition = $path_def->{'definition'};
				
				//print "MATCH!!!";
				
				//make the list entry for accordion

				

				//echo "<a class=\"accordion-header\" href =\"#\">$test $definition</a>";

				

		
					echo "<li>";	
				
				
				//get pathway image and show
				$obj_list = array($gene_name);
				$fg_list  = array('#ff0000', '#00ff00');
				$diag_url = $serv->color_pathway_by_objects($test, $obj_list, $fg_list);
				
				//add fancybox functionality
				print "<a class=\"zoom3\" title=\"Kegg Pathways\" href=\"$diag_url\">$test $definition</a><p>";
				
			
				#list the other genes in this pathway
				$pathway_genes = $serv->get_genes_by_pathway("$kegg_code:$test");
				foreach ($pathway_genes as $pathway_gene)
				{
					
$split_pathgenes = explode(":",$pathway_gene);

print "<a href=\"$rel_web_url&gene=".$split_pathgenes[1]."&genome=$genome_id\"><font size = 1>$pathway_gene</font></a> ";

				}
				#print $diag_url;
				//print "<img src=\"$diag_url\" alt=\"Image not available\" />";
				
			
				
				
				#print "</li>";
				
				break;
			}

		}
	}
	#print "</ul>";
	/*echo "<script> 
$('.accordion').accordion('destroy');
$('.accordion').accordion({active:false, collapsible:true, autoHeight:false});</script>";
	*/
	//print_r ($path_results);
	
	//print $ko_results->{'entry_id'};
/*

foreach ($path_results as $pathway)
{
	//matching genes to pathway
	$pathway_group = $serv->get_genes_by_pathway($pathway);
	//print_r ($pathway_group);
}	
*/	
//foreach ($ko_results as $ko)
//{
	//matching genes to pathway
//	$ko_group = $serv->get_genes_by_ko($ko);
	//print "ko group";
	//print $ko_group[0];
//}	

//echo "EMILY";
//print_r ($ko_group);
//echo $ko_group['Array'][0];



//shows picture
// http://www.genome.jp/dbget-bin/show_pathway?eco00290

//orthologs from other organisms



//get_genes_by_ko('ko:K00010', 'all')



?>
