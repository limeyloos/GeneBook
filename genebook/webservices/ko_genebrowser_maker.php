<?php
print session_id();
/* *******This script makes a genome context diagram*******
It uses graphviz to produce an SVG.

*/
include('database_connection.php');
include("parameters.php");
//Query to get the locus start and end the gene


//print $gene;
//$gene = "thra";
//$new_genome_id = "NC_003197";

//print $new_genome_id;
$localtime_assoc = localtime(time(), true);
#print "before q1 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

$locus_query = "select locus_tag, Strand_Dir, Locus_Start, Locus_End, Genome_ID, gene from gene where locus_tag = '".$results_code[1]."'";
#print $results_code[1];

$query_results = mysql_query($locus_query);



while ($row=mysql_fetch_array($query_results))
{
	$locus_start_orig = $row['Locus_Start'];
	$locus_end_orig = $row['Locus_End'];
	$strand_dir_orig = $row['Strand_Dir'];
	$locus_tag_orig = $row['locus_tag'];
	$new_genome_id = $row['Genome_ID'];
	$gene_desc = $row['gene'];
}




//Query to get genes x distance up and down stream 
$downstream = $locus_end_orig+6000;
$upstream = $locus_start_orig-6000;
$graph_dist = (($downstream-$upstream)/700)*72;

$localtime_assoc = localtime(time(), true);
#print "after q1 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";


//print $locus_tag_orig;

//tried this in one query but takes too long...
/*$all_genes_query = "SELECT gene.gene, gene.note, gene.locus_tag, gene.Strand_Dir, gene.Locus_Start, gene.Locus_End, feature.Pseudo AS f_pseudo, gene.pseudo AS g_pseudo, feature.product
FROM gene
LEFT JOIN feature ON feature.locus_tag = gene.locus_tag AND feature.Locus_Start = gene.Locus_Start
WHERE gene.Genome_ID = \"$new_genome_id\"
AND gene.Locus_Start >=$upstream
AND gene.Locus_End <=$downstream
AND (
feature = 'CDS'
OR feature.Pseudo = 'True'
OR gene.Pseudo = 'True'
)
ORDER BY gene.Locus_Start +0";*/

$localtime_assoc = localtime(time(), true);
#print "before q2 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

$all_genes_query = "SELECT gene.gene, gene.Pseudo as gene_pseudo, feature.Pseudo as feature_pseudo, gene.note, gene.locus_tag, gene.Strand_Dir, gene.Locus_Start, gene.Locus_End, product, feature
FROM gene 
LEFT JOIN feature ON feature.locus_tag = gene.locus_tag
WHERE gene.Genome_ID = \"$new_genome_id\"
AND gene.Locus_Start >=$upstream
AND gene.Locus_End <=$downstream
ORDER BY gene.Locus_Start +0";



//make the diagram.to show scale






$graph = "";
$nodes = "";
$graph_beginning = "graph g { graph[rankdir = \"LR\" label = ]; edge[dir=none]; node[fixedsize = true style = \"bold,filled\"];";
#print $all_genes_query;
$dist = 0;
$query_results = mysql_query($all_genes_query);

$localtime_assoc = localtime(time(), true);
#print "after q2 $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

$i =0 ;
$graph_height = $graph_height+1000;

if ($locus_start_orig<15000)
{
	$beg_pos = "";
	$begin = "";
}
else
{
	$begin = " \"beginning\" -- ";
	$beg_pos = "pos = \"1,$graph_height\"";
	$beginning = "\"beginning\" [fillcolor = \"red2\" $beg_pos shape=point]; ";
}


$localtime_assoc = localtime(time(), true);
#print "before diagram $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

while ($row=mysql_fetch_array($query_results))
{
	$locus_tag = $row['locus_tag'];
	//print $locus_tag;
	$strand_dir = $row['Strand_Dir'];
	$locus_start = $row['Locus_Start'];
		$locus_end = $row['Locus_End'];
		$gene = $row['gene'];
		$note = $row['note'];
		$product = $row['product'];
		$gene_pseudo = $row['gene_pseudo'];
		$feature_pseudo = $row['feature_pseudo'];
		#print "gene".$gene_pseudo;
		#print "feature".$feature_pseudo;
		$feature = $row['feature'];
		#print $locus_tag." ".$feature;
	
	if ($feature == "CDS" or $feature_pseudo == "True" or $gene_pseudo == "True")
	{		

if ($locus_tag_orig == $locus_tag && $query_locus_tag == $locus_tag)
{
print "<b>".$locus_tag_orig." $kegg_spec[2] $kegg_spec[3]</b>";

$href = "<a href = \"$rel_web_url&gene=$locus_tag_orig&genome=$new_genome_id\">";

array_push ($ortholog_table, "<tr><td>$href"."$locus_tag_orig</a></td><td>$gene_desc</td><td>$product</td><td>$kegg_spec[2]</td><td>$kegg_spec[3]</td></tr>");
}
elseif ($locus_tag_orig == $locus_tag)
{
print $locus_tag_orig." $kegg_spec[2] $kegg_spec[3]";
$href = "<a href = \"$rel_web_url&gene=$locus_tag_orig&genome=$new_genome_id\">";
array_push ($ortholog_table, "<tr><td>$href"."$locus_tag_orig</a></td><td>$gene_desc</td><td>$product</td><td>$kegg_spec[2]</td><td>$kegg_spec[3]</td></tr>");
	if ($query_strand_dir != $strand_dir_orig)
	{
		print " (flipped)";
	}
}



//determine host specificty and assign a colour
if ($kegg_spec[1] == "HG")
{
	$spec_colour = "gold1";
}
else
{
	$spec_colour = "deepskyblue";

}

		
		//print $feature_pseudo."EMEMEME";

		//print $gene_pseudo."ILILILIL";
		//print "$product\n";
	//print $locus_start."l";
	//print $downstream."d";
	$dist = ((((($locus_end - $locus_start)/2)+($locus_start-$upstream))/700)*72);
	#$dist = ((((($locus_end - $locus_start)/2)+($downstream-$locus_end))/700)*72);
	//print $dist."\n";
	$width = ($locus_end-$locus_start)/700;
	//print $width."\n";
	if ($i == 0)
	{
		if ($begin!="")
		{
			$graph .= $begin."\"$locus_tag\"";
		}
	}
	else
	{
	//print $width;
	$graph .= " \"$locus_tag\" [color=\"#0000ff\" ]";
	}
	$graph .= " \"$locus_tag\" --";
	//print $graph.
	//$sub_graph = " \"$locus_tag\" --";
	
	//print $sub_graph."\n";
	//print $strand_dir;

#	if ($feature != 'CDS' || ($gene_pseudo != "True" xor $feature_pseudo != "True"))
#	{
#		print $feature;
#	}
#	else
#	{


		

	if ($query_strand_dir == $strand_dir_orig)
	{
	#print $feature;

	if ($strand_dir == "1")
	{
		if ($locus_tag == $locus_tag_orig)
		{
	
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\" fillcolor = $spec_colour pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\" target \"_top\" ]; ";  
		}
		elseif ($feature_pseudo == 'True' or $gene_pseudo == 'True')
		{
			//Add in pseudogene colour.
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PSEUDOGENE $product\" fillcolor = \"hotpink\" pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\" target \"_top\" ]; ";  

		}
		else
		{
		$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\" fillcolor = \"green\" pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target \"_top\" ]; ";  
		}
	}
	else 
	{
		if ($locus_tag==$locus_tag_orig)
		{
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\"  fillcolor = $spec_colour pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target \"_top\" ]; ";  
		}
		elseif ($feature_pseudo == 'True' or $gene_pseudo == 'True')
		{
			//Add in pseudogene colour.
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PSEUDOGENE $product\"  fillcolor = \"hotpink\" pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target \"_top\" ]; ";  
		}
		else
		{
		$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\" fillcolor = \"red2\"  pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target \"_top\"]; ";
		}
	}
	// each gene's location into graph.
	$i++;
}
else
	{

	$dist = ((((($locus_end - $locus_start)/2)+($downstream-$locus_end))/700)*72);
	#print "reverse";
		
	
	if ($strand_dir == "1")
	{
		if ($locus_tag == $locus_tag_orig)
		{
			$nodes = "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\" fillcolor = $spec_colour pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\" target = \"_top\" ]; ".$nodes;  
		}
		elseif ($feature_pseudo == 'True' or $gene_pseudo == 'True')
		{
			//Add in pseudogene colour.
			$nodes = "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PSEUDOGENE $product\" fillcolor = \"hotpink\" pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\" target = \"_top\" ]; ".$nodes;  

		}
		else
		{
		$nodes = "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\" fillcolor = \"green\" pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target = \"_top\" ]; ".$nodes;  
		}
	}
	else 
	{
		if ($locus_tag==$locus_tag_orig)
		{
			$nodes = "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\"  fillcolor = $spec_colour pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target = \"_top\" ]; ".$nodes;  
		}
		elseif ($feature_pseudo == 'True' or $gene_pseudo == 'True')
		{
			//Add in pseudogene colour.
			$nodes = "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PSEUDOGENE $product\"  fillcolor = \"hotpink\" pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target = \"_top\" ]; ".$nodes;  
		}
		else
		{
		$nodes = "\"$locus_tag\" [tooltip = \"$locus_tag $gene ORGANISM = ".$kegg_spec[2]." ".$kegg_spec[3]." SPECIFICITY = ".$kegg_spec[1]." PRODUCT = $product\" fillcolor = \"red2\"  pos = \"$dist,$graph_height\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$new_genome_id\"  target = \"_top\"]; ".$nodes;
		}
	}
	// each gene's location into graph.
	$i++;

}
#}
}
}
$graph .= "\"end\" [color=\"#0000ff\" ]";

$localtime_assoc = localtime(time(), true);
#print "after diagram $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";


if ($query_strand_dir == $strand_dir_orig)
	{
	$nodes .= " $beginning \"end\" [fillcolor = \"red2\" pos = \"$graph_dist,$graph_height\" shape=point];";
}
else
{
	$beginning = "\"beginning\" [fillcolor = \"red2\" pos = \"$graph_dist,$graph_height\"  shape=point]; ";
	$nodes .= " $beginning \"end\" [fillcolor = \"red2\" $beg_pos shape=point];";
}

$graphviz_query = $graph_beginning." ".$graph." ".$end_and_start." ".$nodes." }";


$localtime_assoc = localtime(time(), true);
#print "before qraphviz $localtime_assoc[tm_hour]:$localtime_assoc[tm_min]:$localtime_assoc[tm_sec]  \n";

//$graphviz_query = "graph g {emily -- jane }";
$esc_g_q = escapeshellarg($graphviz_query); 

//header("Content-type: image/svg+xml");
passthru("echo $esc_g_q | \"$neato_dir\" -Tsvg -n -o \"$ws_tmp_dir/$pid"."_$locus_tag_orig.svg\"");


$localtime_assoc = localtime(time(), true);
#print $pid;
print "<object data = \"$tmp_url"."$pid"."_$locus_tag_orig.svg\" type=\"image/svg+xml\"
        width=\"100%\" height=\"50\"></object>";


//include the gene_browser stuff use include()? Need to change slightly to get the coordinates to match the
//query gene each genome should be a bit lower down - change the y axis.
#print getmypid();
?>
