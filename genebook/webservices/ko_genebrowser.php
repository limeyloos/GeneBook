<?php

// gets the KEGG orthologs and displays as an SVG

#print session_id();
$localtime_assoc = localtime(time(), true);
#print "start $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";


$wsdlUrl = 'http://soap.genome.jp/KEGG.wsdl';
$serv = new SoapClient($wsdlUrl);
$offset = 1;
$limit = 5;

include('database_connection.php');
include("parameters.php");

$gene_name = $_GET["gene"];
$genome_id = $_GET["genome"];

if ($_GET["PID"])
{
	$pid = $_GET["PID"];
}
else
{
	$pid = getmypid();
}
#print $pid;
//get the kegg genome code for for the gene
$kegg_3_letters_gene = "select kegg_code, locus_tag, Strand_Dir from gene, genome where genome.genome_id =\"".$genome_id."\" and   genome.genome_id = gene.Genome_ID and gene =\"".$gene_name."\" ";
$kegg_3_letters_locus_tag = "select kegg_code, locus_tag, Strand_Dir from gene, genome where genome.genome_id =\"".$genome_id."\" and   genome.genome_id = gene.Genome_ID and locus_tag =\"".$gene_name."\" ";

//get the genome codes for all genomes in the database;
$all_kegg_codes = "select kegg_code, locus_tag from gene, genome";


#print $kegg_3_letters;
$gene_array = array();

$localtime_assoc = localtime(time(), true);
#print "bef outside query 1 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

$query_results = mysql_query($kegg_3_letters_locus_tag);

if (mysql_num_rows($query_results) == 0)
{
	print "no results";
	$query_results = mysql_query($kegg_3_letters_gene);
}

$localtime_assoc = localtime(time(), true);
#print "aft outside query 1 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

while ($row=mysql_fetch_array($query_results))
{
	$kegg_code = $row['kegg_code'];
	$gene = $row['kegg_code'].":".$row['locus_tag'];
	$query_locus_tag = $row['locus_tag'];
	$query_strand_dir = $row['Strand_Dir'];
	array_push($gene_array, $gene);
	$ko_results = $serv->get_ko_by_gene($gene);
	
}

$localtime_assoc = localtime(time(), true);
#print "bef outside query 2 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";


//get the genome codes for all genomes in the database;
$all_kegg_codes = "select specificity, serovar, strain, kegg_code from genome";
$kegg_query_results = mysql_query($all_kegg_codes);

$localtime_assoc = localtime(time(), true);
#print "aft outside query 2 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

$localtime_assoc = localtime(time(), true);
#print "bef results query 2 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

$kegg_codes_array = array();
$specificity_array = array();
while ($row=mysql_fetch_array($kegg_query_results))
{
	//print $row['kegg_code'];
	array_push($kegg_codes_array, $row['kegg_code'].":".$row['specificity'].":".$row['serovar'].":".$row['strain']);
	//$specificity = $row['specificity'];
	#print $row['kegg_code'].":".$row['specificity'].":".$row['serovar'].":".$row['strain']."\n";
}

$localtime_assoc = localtime(time(), true);
#print "aft results query 2 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";


$unique_codes = array_unique($kegg_codes_array);

#print_r ($unique_codes);

$gene_matches = array();

$localtime_assoc = localtime(time(), true);
#print "kegg bef $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";


//just incase there are multiple KOs for the query gene
foreach ($ko_results as $ko_res)
{
	#print $ko_res;	
	$ko_genes = $serv->get_genes_by_ko($ko_res, 'all');
}

$localtime_assoc = localtime(time(), true);
#print "kegg aft $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

#print_r ($ko_genes);

//$j is the nth gene
$j = 1;
//Goes through each genome code from our database and looks for hits against the ko response


//$graph_beginning = "graph g { graph[rankdir = \"LR\" ]; edge[dir=none]; node[fontsize = 20 fixedsize = true style = \"bold,filled\"];";
$graph_trimmed_all = "";
$graph_height = 0;


$localtime_assoc = localtime(time(), true);
#print "before inc $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";


#array for ortholog details table.
$ortholog_table = array("<table><tr><th>Locus tag</th><th>Gene name</th><th>Product</th><th>Serovar</th><th>Strain</th></tr>");

foreach ($unique_codes as $u_c)
{
#print_r ($unique_codes);
//separate kegg code form specificity
$kegg_spec = explode(":", $u_c);



	foreach ($ko_genes as $ko_gene)
	{
		$results_code = explode(":", $ko_gene->{'entry_id'});
		
		#print_r ($ko_gene);
		//print $ko_gene->{'entry_id'};
		//print $u_c;
		//print $results_code[0];
		//print "\n";
		##print $ko_def;
		
		if ($kegg_spec[0] == $results_code[0])
		{
			//print $kegg_spec[0].$results_code[0];
			//print $results_code[0];
			//print $results_code[1]."\n";
			include ('ko_genebrowser_maker.php');
			
			//this is the genes that are added onto the URL		
			$gene_get .= "&gene$j=$locus_tag_orig";
			$j++;
			#break;
			
		}
		
		
	}
	
	

}

$fp = fopen("$ws_tmp_dir/$pid"."_$gene_name&$genome_id.txt", "w+");
fwrite($fp,$gene_get);

$localtime_assoc = localtime(time(), true);
#print "after inc $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";
#print_r ($ortholog_table);

$fe = fopen("$ws_tmp_dir/$pid"."table_$gene_name&$genome_id.txt", "w+");
foreach($ortholog_table as $ortholog_row)
{
#save the data for the table.
#print $ortholog_row;
fwrite($fe,$ortholog_row."\n");
}
fwrite($fe,"</table>");
#print $pid;
//print $graphviz_query;
//print_r ($ko_genes);
//print $gene_get;
#print "ko_genebrowser".getmypid();
?>
