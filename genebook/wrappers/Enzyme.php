<?php

// displays the enzyme from the Expasy Enzyme URL results

include('check_for_data.php');
include('database_connection.php');
include ('getrss.php');

$gene_name = $_POST["gene"];
$genome_id = $_POST["genome"];





$enz_query = "select ec_num from ec_number, gene where genome_id = \"".$genome_id."\" and ec_number.locus_tag = gene.locus_tag and (gene = \"".$gene_name."\" or gene.locus_tag = \"".$gene_name."\")";
//echo $enz_query;
$query_results = mysql_query($enz_query);
#echo $gene_name."<br>";
while($row=mysql_fetch_array($query_results))
{
	
	
#$url = "http://enzyme.expasy.org/EC/".$row['ec_num'];
#$url = "http://enzyme.expasy.org/cgi-bin/enzyme/get-enzyme-entry?".$row['ec_num'];
$url = "http://enzyme.expasy.org/EC/".$row['ec_num'].".txt";

//echo $url;
//consider adding .txt on the end then will be in easily parsible text format...

$page = get_web_page( $url );
#print $page;

$keywords = preg_split("/\n/", $page);
#print_r ($keywords);

#some data types have more than one line this checks whether it is the first line
$i = 0;
$j = 0;
$k = 0;

foreach($keywords as $page_line)
{
	#regular expression to split the line into data type and data
	$data_explode = explode("   ", $page_line);
	
	if ($data_explode[0] == "ID")
	{
		print "<b>ENZYME entry:\t</b>".$data_explode[1]."<br/>";
	}
	elseif ($data_explode[0] == "DE")
	{
		print "<b>Accepted name:\t</b>".$data_explode[1]."<br/>";
	}
	elseif ($data_explode[0] == "AN")
	{
		if ($i == 0)
		{
			print "<b>Alternative name(s):\t</b>$data_explode[1]";
		}		
		else		
		{		
			print ",".$data_explode[$i+1];
		}
		$i++;
	}
	elseif ($data_explode[0] == "CA")
	{
		if ($j == 0)
		{
			print "<br/><b>Reaction Catalysed:\t</b>$data_explode[1]";
		}		
		else
		{
			print " ".$data_explode[$j+1];
		}	
		$j++;
	}
	elseif ($data_explode[0] == "CC")
	{
		if ($k == 0)
		{
			print "<br/><b>Comment(s):</b>$data_explode[1]";
		}		
		else
		{
			print "\t".$data_explode[$k+1];
		}	
		$k++;
	}
	
	#
}
print "<br/><br/>";
//finds the appropriate part of the html string and extracts it into an array
#$s_pos = strpos($page, "<PRE>");
#$e_pos = strpos($page, "DR");
#
#$string_diff = $e_pos-$s_pos;
#$enz_info = substr($page, $s_pos, $string_diff);

#echo $enz_info."<br>";;

//echo $s_pos."-".$e_pos."=".$string_diff;

}
//print_r($matches);
//echo "Test".$genome_id;

?>

