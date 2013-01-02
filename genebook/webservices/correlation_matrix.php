<?php
// calculates spearmans correlation coeffiecient and outputs the features ordered by rank into xml


include("parameters.php");
$gene_name = $_GET["gene"];

$file = $_GET["file"];

escapeshellcmd($gene_name);	


$header_first_line = shell_exec("head -1 $ws_tmp_dir/$file.txt");

$header_line = rtrim($header_first_line);
#print "<p>$header</p>";

#split headers 
$headers = explode("\t", $header_line);



#define the data headings array
$data_headings = array();



#counter to record the data based headings
$data_counter = 0;
$data_columns = array();



#find data_ headers 	
foreach ($headers as $header)
{

	#print $header;	
	$heading = explode("_",$header);

	if ($heading[0] == "data")
	{

		array_push($data_columns, $data_counter);

	}
	if ($header == "locus_tag")
	{
		$locus_tag_col = $data_counter;
	}
	$data_counter++;
}


$matching_lines = shell_exec("grep $gene_name $ws_tmp_dir/$file.txt");

#print $matching_lines;


if ($matching_lines == "")

{
	print "no data for this locus_tag";

}
else
{
	$hits = "some experiment";

}

#this allows for multiple lines for the same locus tag.	
$lines = explode("\n", $matching_lines);


$data_lines = array();
$data_points = array();
$k = 0;
foreach ($lines as $line)
{
	if ($line=="")
	{		

	}

	else
	{

		#split the string by tabs to get the data	
		$cells = explode ("\t", $line);

		$j = 0;					
		
		#ensure that the locus_tag we grepped is actually in the locus_tag column and not elsewhere
		if ($cells[$locus_tag_col] == $gene_name)
		{
		
		
			foreach($data_columns as $col_number)
			{

				#check if it is the first instance
				if (isset ($data_points[$gene_name][$j]))
				{
					$data_points[$gene_name][$j] = ($data_points[$gene_name][$j]+$cells[$col_number]);
				}
				else
				{
					$data_points[$gene_name][$j] = ($cells[$col_number]);
				}
				
	
				#print "!!data $j $gene_name!!";
				#print "@@cell $cells[$col_number]@@";

				$j++;
			}
		$k++;
		}

	
	}
}





#size of x  
$n = count($data_points[$gene_name]); 
#print $n;
#print_r ($data_points);
#print "k = $k";


#take an average if there are multiple values for the same locus_tag

#query mean
for( $i = 0; $i < $n; $i++ )
{
	
	$mean_data_points[$gene_name][$i] = $data_points[$gene_name][$i]/$k;
	
	$square_data_points[$gene_name][$i] = pow($mean_data_points[$gene_name][$i],2);
	

}

#print_r ($mean_data_points);
#print_r ($square_data_points);

#when looping through each line ignore if $locus_tag = our gene.
$wc_file = shell_exec("wc -l $ws_tmp_dir/$file.txt");
$number_lines = explode(" ",$wc_file);
#print $number_lines[0];


$correlation_array = array();

$handle = @fopen("$ws_tmp_dir/$file.txt", "r");
if ($handle) 
{
	while (($buffer = fgets($handle)) !== false) 
	{


	if ($buffer=="\n")
	{		

	}
	else
	{

		#split the string by tabs to get the data	
		$cells = explode ("\t", $buffer);
	

		$j = 0;					
		
		#ensure that the locus_tag we grepped is actually in the locus_tag column and not elsewhere
		if (($cells[$locus_tag_col] == $gene_name) or ($cells[$locus_tag_col] == "locus_tag"))
		{
			
		}
		#we don't want our subject to be the same as the query locus_tag

		else
		{

			foreach($data_columns as $col_number)
			{



				$data_points[$cells[$locus_tag_col]][$j] = $cells[$col_number];
				$square_data_points[$cells[$locus_tag_col]][$j] = pow($data_points[$cells[$locus_tag_col]][$j],2);
				$qs_data_points[$j] = ($cells[$col_number])*($mean_data_points[$gene_name][$j]);

				#print "cells col number ".$cells[$col_number]."<br>";
				#print "mean data ".$mean_data_points[$gene_name][$j]."<br>";


				$j++;
			}
		
	#print_r($data_points[$cells[$locus_tag_col]]);


	#dividend
	$n_sigma_xy = ($n*array_sum($qs_data_points));
	#print "!!!n $n !!!!";
	$total_x = array_sum($mean_data_points[$gene_name]);
	#print "total of x".$total_x;
	
	$total_y = array_sum($data_points[$cells[$locus_tag_col]]);
	#print "total of y".$total_y;

	$dividend = ($n_sigma_xy - ($total_x*$total_y));





	#dividor
	$n_xsquared_total = ($n*array_sum($square_data_points[$gene_name]));
	$totalx_squared = pow(array_sum($mean_data_points[$gene_name]),2);
	$xdividor = $n_xsquared_total - $totalx_squared;

	#print "total xsquared times n".$totalx_squared;

	$n_ysquared_total = ($n*array_sum($square_data_points[$cells[$locus_tag_col]]));
	$totaly_squared = pow(array_sum($data_points[$cells[$locus_tag_col]]),2);
	$ydividor = $n_ysquared_total - $totaly_squared;

	#print "total ysquared times n".$totaly_squared;


	$dividor = $xdividor*$ydividor;
	$dividor = sqrt($dividor);

	#print "dividor".$dividor;
	#print "dividend".$dividend;

	#ensure that dividor variable is defined.
	if ($dividor)
	{
	#print "CORRELATION $cells[$locus_tag_col]";
	$correlation  = $dividend/$dividor;
	#print $correlation;
	}
	#print_r ($mean_data_points[$gene_name]);

	#print_r($qs_data_points);

#check that it is a real locus tag with real data rather than a headers column

	#print "<br>n sigma xy array<br>";
	#print_r ($n_sigma_xy);
#print "<br>x s toty<br>";
	#print_r ($n_xsquared_total);
#print "<br>y sq tot<br>";
	#print_r ($n_ysquared_total);
	if ((isset ($correlation_array[$cells[$locus_tag_col]])) and ($correlation_array[$cells[$locus_tag_col]] < $correlation))
	{
	$correlation_array[$cells[$locus_tag_col]] = $correlation;
	}
	elseif (((isset ($correlation_array[$cells[$locus_tag_col]])) and ($correlation_array[$cells[$locus_tag_col]] > $correlation)) or (!isset($correlation)))
	{}
	else
	{
	$correlation_array[$cells[$locus_tag_col]] = $correlation;
	}	

		}




	
	}











	}
	if (!feof($handle)) 
	{
		echo "Error: unexpected fgets() fail\n";
	}
	fclose($handle);

}



arsort($correlation_array);
#print_r($correlation_array);


#print_r ($correlation_array);


$test_array = array (
  'bla' => 'blub',
  'foo' => 'bar',
  'another_array' => array (
    'stack' => 'overflow',
  ),
);

#print_r($test_array);

$xml = new SimpleXMLElement('<correlation/>');
$rank = 1;
foreach ($correlation_array as $locus => $correlation_value)
{
	$locus_tag = $xml->addChild('rank', $rank);	
	$locus_tag->addChild('correlation_value', $correlation_value);
	$locus_tag->addChild('locus_tag', $locus);
	
	$rank++;

}

print $xml->asXML();

?> 
