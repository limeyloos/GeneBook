<?php

// this webservice dynamically makes graphs based on sequence location

	$gene_name = $_GET["gene"];
	$file = $_GET["file"];
	include("parameters.php");
	#$graph_title = $_GET["title"];


include('database_connection.php');

$gene_info_query = "select * from gene where gene =\"".$gene_name."\" or locus_tag = \"".$gene_name."\"";

#print $gene_info_query;

$query = mysql_query($gene_info_query);

while($row= mysql_fetch_array($query))
{
	$locus_start = $row['Locus_Start'];
	$locus_end = $row['Locus_End'];
	$strand_dir = $row['Strand_Dir'];
	
}



	escapeshellcmd($gene_name);	
	
	$header_first_line = shell_exec("head -1 $ws_tmp_dir/$file.txt");
	
	$header_line = rtrim($header_first_line);
	#print "<p>$header</p>";

	#split headers 
	$headers = explode("\t", $header_line);



	#define the data headings array
	$data_headings = array();
	$pval_headings = array();
	$pos_headings = array();
	$ori_headings = array();

	#counter to record the data based headings
	$data_counter = 0;
	$data_columns = array();
	$pval_columns = array();
	$pos_columns = array();
	$ori_columns = array();

	#find data_ headers 	
	foreach ($headers as $header)
	{

		$heading = explode("_",$header);
		
		if ($heading[0] == data)
		{
		
		
		array_push($data_headings, "'$heading[1]'");

		array_push($data_columns, $data_counter);
		$data_heading = $heading[2];
			
		}
/*
		if ($header == "name")
		{
			$desc_column = $data_counter;
			
		}		
	*/
		if ($heading[0] == pval)
		{
		
		array_push($pval_headings, "'$heading[1]'");

		array_push($pval_columns, $data_counter);

		$pval_heading = "p-value";
		}

		if ($heading[0] == position)
		{
		
		array_push($pos_headings, "'$heading[1]'");

		array_push($pos_columns, $data_counter);

		$pos_heading = "position";
		}


		if ($heading[0] == orientation)
		{
		
		array_push($ori_headings, "'$heading[1]'");

		array_push($ori_columns, $data_counter);

		$ori_heading = "orientation";
		}	
	
	$data_counter++;
	}


	#print_r ($data_headings);
	
	#convert the array to a comma delimited string for the graph
	

	
	#print $test;

	#gets any lines with the locus tag in it
	$matching_lines = shell_exec("grep $gene_name $ws_tmp_dir/$file.txt");
	
	#print $matching_lines;


	if ($matching_lines == "")

	{
		$graph_title = "no data for this locus_tag";
		$graph_size = "<div id=\"container\" style=\"width: 800px; height: 100px; margin: 0 auto\"></div>";
	}
	else
	{
		
		$graph_size = "<div id=\"container\" style=\"width: 800px; height: 400px; margin: 0 auto\"></div>";
	}

	#this allows for multiple lines for the same locus tag.	
	
	$lines = explode("\n", $matching_lines);
	

	$data_lines = array();
	$pval_lines = array();
	
	#which replicate
	$occurence = 1;

	

					
				
					foreach ($lines as $line)
					{
					if ($line=="")
					{		

					}

					else
					{
					
					
					#split the string by tabs to get the data	
					$cells = explode ("\t", $line);
					
					#$name = $cells[$desc_column];
					
								
					foreach($pos_columns as $col_number)
					{
						
						#print "column:$col_number";
						#print "data:$cells[$col_number]";
						$data_cell = $cells[$col_number];						
						if ($cells[$col_number] == "NA")
						{		
							$data_cell = 0;
						}						

						$position = $data_cell;
						
			
					}

					foreach($ori_columns as $col_number)
					{
						
						#print "column:$col_number";
						#print "data:$cells[$col_number]";
						$data_cell = $cells[$col_number];						
						if ($cells[$col_number] == "NA")
						{		
							$data_cell = 0;
						}						

						$orientation = $data_cell;
if ($orientation=='+')
{
						$mutation_point = "{type:'scatter', showInLegend: false, data: [{x:$position,y:0,marker:{symbol:'url($tmp_url"."right_arrow.png)'}}]}";
array_push ($data_lines, $mutation_point);
}
elseif ($orientation == '-')
{
$mutation_point = "{type:'scatter',showInLegend: false,  data: [{x:$position,y:0,marker:{symbol:'url($tmp_url"."/left_arrow.png)'}}]}";
array_push ($data_lines, $mutation_point);
}
			
					}


										
					$heading_number = 0;
					foreach($data_headings as $data_type)
					{
					
					$data_number = 0;					
					foreach($data_columns as $col_number)
					{
						
						#print "column:$col_number";
						#print "data:$cells[$col_number]";
						$data_cell = $cells[$col_number];						
						if ($cells[$col_number] == "NA")
						{		
							$data_cell = "null";
						}						
						if ($heading_number == $data_number)
						{
						$data_points[$data_type][$position]['y'] = $data_cell;

						}
					$data_number++;
					}

					

				
					$heading_number++;
					}
					




/*
										
					$pval_points = array();
					if($pval_heading)
					{
					
					foreach($pval_columns as $col_number)
					{
						
						#print "column pvalues:$col_number";
						#print "data:$cells[$col_number]";
						$data_cell = $cells[$col_number];							
						if ($cells[$col_number] == "NA")
						{		
							$data_cell = 0;
						}

						array_push ($pval_points, $data_cell);
				
			
					}

					


					$pval_points_comma = implode(",",$pval_points);
					
					$data_line =  "{
						name: '$pval_heading $occurence',
						data: [$pval_points_comma],
						type: 'scatter'
						}";

					array_push ($pval_lines, $data_line);
					
					
					}*/
					$occurence++;
					}
					}
					#print_r ($data_points);

					foreach($data_points as $data_type => $x)
					{
						
						$data_line =  "{
						type: 'scatter',
						marker:{symbol:'circle',radius:5},						
						name: $data_type,data: [";
													

						foreach ($x as $x_value => $y_value)
						{
								#print_r($y_value);

$y = $y_value[y];

$data_line = $data_line."{x:$x_value,y:$y,marker:{symbol:'circle'}},";





				
									
							#print "$data_type $x_value $y_value";
						}
						$data_line = $data_line."]}";						
						array_push ($data_lines, $data_line);

						#print "EMILY";
					}

					$all_data = implode (",",$data_lines);
					
					#$all_pval_lines = implode (",",$pval_lines);
					
					#$all_data = $all_data_lines.",".$all_pval_lines;
					#print_r ($data_lines);
					#print $all_data;
					?>















<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<?php
		
		
		print "<title>$gene_name</title>";
		
		
		
		#<!-- 1. Add these JavaScript inclusions in the head of your page -->
		print "<script type=\"text/javascript\" src=\"$jq_min_dir/jquery.min.js\"></script>";
		print "<script type=\"text/javascript\" src=\"$js_highcharts_dir/highcharts.js\"></script>";
		
		#<!-- 1a) Optional: add a theme file -->
		#<!--
		print "<script type=\"text/javascript\" src=\"$js_dir/themes/gray.js\"></script>";
		#-->
		
		#<!-- 1b) Optional: the exporting module -->
		print "<script type=\"text/javascript\" src=\"$js_highcharts_dir/modules/exporting.js\"></script>";
		
		?>
		<!-- 2. Add the JavaScript to initialize the chart on document ready -->
		<script type="text/javascript">
		
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'container',
						<?php

						print "defaultSeriesType: '$type',";
						?>
						marginRight: 130,
						marginBottom: 50
					},
					title: {
						
						<?php
						print "text: '$graph_title',
						x: -20";
						?>
					},

					
					 xAxis: {


						<?php

						print "min: $locus_start, max: $locus_end,endOnTick:false,";
						print "title: {text: 'base location of mutation'},";
						
if ($strand_dir == -1)
{
print "reversed: true";
}
						?>
					},


				

					yAxis: {
						title: {
							<?php							
							print "text: '$data_heading'";
							?>
						},

          gridLineColor:'#CCCCCC',


						plotLines: [{
							value: 0,
							width: 10
,zIndex:3,
						
<?php

if ($strand_dir == 1)
{

 	print "color: '#CC0000',";
}
elseif ($strand_dir == -1)
{
	print "color: '#66CC33',";
}
?>







						}]
					},
					tooltip: {
						formatter: function() {
				                return '<b>'+ this.series.name +'</b><br/>'+
								this.x +': '+ this.y +'';
						}
					},
					legend: {
						layout: 'vertical',
						align: 'right',
						verticalAlign: 'top',
						x: -10,
						y: 100,
						borderWidth: 0
					},

					series: [

<?php
if ($strand_dir == 1)
{
$gene_direction = $locus_end;

print" {type:'scatter',   showInLegend: false,      data: [{x:$gene_direction,y:0,marker:{symbol:'url($tmp_url"."right_gene.png)'}}]},";
}
elseif ($strand_dir == -1)
{
$gene_direction = $locus_end;
print" {type:'scatter',   showInLegend: false,  data: [{x:$gene_direction,y:0,marker:{symbol:'url($tmp_url"."/left_gene.png)'}}]},";
}
print $all_data;



?>
      


]
				});
				
				
			});
				
		</script>
		
	</head>
	<body>
		
		<!-- 3. Add the container -->
		
		<?php
#print $locus_end;
		print $graph_size;
		?>

		
				
	</body>
</html>



