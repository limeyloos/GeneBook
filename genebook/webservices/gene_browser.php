<?php
/* *******This script makes a genome context diagram*******
It uses graphviz to produce an SVG.

*/

include('database_connection.php');
include("parameters.php");
//Query to get the locus start and end the gene

$gene = $_GET['gene'];
$genome_id = $_GET['genome'];


if ($_GET["PID"])
{
	$pid = $_GET["PID"];
}
else
{
	$pid = getmypid();
}
#print $pid;

//$gene = "thra";
//print $gene;

//$genome_id = "NC_003197";

//print $genome_id;

$locus_query = "select locus_tag, Strand_Dir, Locus_Start, Locus_End, Genome_ID from gene where (gene = '$gene' or locus_tag = '$gene') and genome_id = '$genome_id'";
//print $locus_query;

$query_results = mysql_query($locus_query);

while ($row=mysql_fetch_array($query_results))
{
	$locus_start_orig = $row['Locus_Start'];
	$locus_end_orig = $row['Locus_End'];
	$strand_dir = $row['Strand_Dir'];
	$locus_tag_orig = $row['locus_tag'];
	#print "$locus_start_orig $locus_end_orig $strand_dir $locus_tag_orig";
}

//Query to get genes x distance up and down stream 
$downstream = $locus_end_orig+10000;
$upstream = $locus_start_orig-10000;
$graph_dist = $downstream-$upstream;

#not include pseudos
#$all_genes_query = "select gene, note, locus_tag, Strand_Dir, Locus_Start, Locus_End, Pseudo from gene where Genome_ID = \"$genome_id\" and Locus_Start >= $upstream and Locus_End <= $downstream and Pseudo != 'true'";

#include pseudos
$all_genes_query = "select gene, note, locus_tag, Strand_Dir, Locus_Start, Locus_End, Pseudo from gene where Genome_ID = \"$genome_id\" and Locus_Start >= $upstream and Locus_End <= $downstream";

//make the diagram.to show scale

//print $all_genes_query;
$dist = 0;
$query_results = mysql_query($all_genes_query);
$graph = "graph g { graph[rankdir = \"LR\" ]; edge[dir=none]; node[fontsize = 20 fontname = \"helvetica\" fixedsize = true style = \"bold,filled\"];";
$i =0 ;

while ($row=mysql_fetch_array($query_results))
{
	$locus_tag = $row['locus_tag'];
	$strand_dir = $row['Strand_Dir'];
	$locus_start = $row['Locus_Start'];
		$locus_end = $row['Locus_End'];
		$gene = $row['gene'];
		$note = $row['note'];
		$pseudo = $row['Pseudo'];
	#print $pseudo;
	//print $locus_start."l";
	//print $downstream."d";
	$dist = ((((($locus_end - $locus_start)/2)+($locus_start-$upstream))/700)*72);
	//print $dist."\n";
	$width = ($locus_end-$locus_start)/700;
	//print $width."\n";
	if ($i == 0 )
	{
		
	}
	else
	{
	//print $width;
	$graph .= " \"$locus_tag\" [color=\"#0000ff\" ]";
	}
	$graph .= " \"$locus_tag\" --";
	
	$sub_graph = " \"$locus_tag\" --";
	//print $strand_dir;

	if ($strand_dir == "1")
	{
		if ($locus_tag == $locus_tag_orig)
		{
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene $note\" fillcolor = \"gold1\" pos = \"$dist,20\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$genome_id\" target \"_top\" lp = \"0,0\" label=\"$locus_tag\" ]; ";  
		}
		elseif ($pseudo == "True")
		{
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene $note\" fillcolor = \"hotpink\" pos = \"$dist,20\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$genome_id\"  target \"_top\" ]; ";  
		}
		else
		{
		$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene $note\" fillcolor = \"green\" pos = \"$dist,20\" shape=house height = 1 width = $width orientation = -90 href = \"$rel_web_url&gene=$locus_tag&genome=$genome_id\"  target \"_top\" ]; ";  
		}
	}
	else 
	{
		if ($locus_tag==$locus_tag_orig)
		{
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene $note\"  fillcolor = \"gold1\" pos = \"$dist,20\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$genome_id\"  target \"_top\" lp = \"25\" label=\"$locus_tag\"]; ";  
		}
		elseif ($pseudo == "True")
		{
			$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene $note\" fillcolor = \"hotpink\" pos = \"$dist,20\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$genome_id\"  target \"_top\" ]; ";  
		}
		else
		{
		$nodes .= "\"$locus_tag\" [tooltip = \"$locus_tag $gene $note\" fillcolor = \"red\"  pos = \"$dist,20\" shape=house height = 1 width = $width orientation = 90 href = \"$rel_web_url&gene=$locus_tag&genome=$genome_id\"  target \"_top\"]; ";
		}
	}
	// each gene's location into graph.
	$i++;
}


$pos = strpos($graph, $sub_graph);

$graph_trimmed = substr($graph, 0, $pos);
$graphviz_query = $graph_trimmed." ".$nodes." }";







$esc_g_q = escapeshellarg($graphviz_query); 


passthru("echo $esc_g_q | \"$neato_dir\" -Tsvg -n -o \"$ws_tmp_dir/$locus_tag_orig.svg\"");

echo "<script>		$(document).ready(function() {
			



			var browserwidth = jQuery(document).width();
			var percentage = 90;
			var fbwidth = Math.ceil((browserwidth*percentage)/100);
			var browserheight = jQuery(document).height();
			var fbheight = Math.ceil((browserheight*percentage)/100);


			$(\"a#zoom4\").fancybox({
				'zoomSpeedIn'		:	500,
				'zoomSpeedOut'		:	500,
				'overlayShow'		:	false,
				'frameWidth'            :       fbwidth,
				'frameHeight'		:	200



			});

			


		});

			$(window).resize(function() {


			var browserwidth2 = jQuery(document).width();
			var browserheight2 = jQuery(document).height();
			var percentage2 = 80;
			var fbwidth2 = Math.ceil((browserwidth2*percentage2)/100);
			var fbheight2 = Math.ceil((browserheight2*percentage2)/100);


			  $(\"a#zoom4\").fancybox({
				'zoomSpeedIn'		:	500,
				'zoomSpeedOut'		:	500,
				'overlayShow'		:	false,
				'frameWidth'		:	fbwidth2,
				'frameHeight'		:	200

			});
			});

</script>";
print "<object data = \"$tmp_url"."$locus_tag_orig.svg\" type=\"image/svg+xml\"
        width=\"100%\" height=\"100\">
    <embed src=\"$tmp_url"."$locus_tag_orig.svg\" type=\"image/svg+xml\"
            width=\"100%\" height=\"100\" />
</object>";




?>

