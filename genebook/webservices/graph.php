<?php

// displays quantitative data into dynamic graphs using highcharts javascript

	include("parameters.php");
	$gene_name = $_GET["gene"];
	$type = $_GET["type"];
	$file = $_GET["file"];
	$graph_title = $_GET["title"];
	escapeshellcmd($gene_name);	
	
	$header_first_line = shell_exec("head -1 $ws_tmp_dir/$file.txt");
	
	#gets any lines with the locus tag in it

	
	#print "egrep -i '^$gene_name\t|\t$gene_name\t|\t$gene_name$' tmp/$file.txt";

	$matching_lines = shell_exec("egrep -i '^$gene_name\t|\t$gene_name\t|\t$gene_name$' $ws_tmp_dir/$file.txt") or die ('gene not present in data');


	$header_line = rtrim($header_first_line);
	#print "<p>$header</p>";

	#split headers 
	$headers = explode("\t", $header_line);



	#define the data headings array
	$data_headings = array();
	$pval_headings = array();


	#counter to record the data based headings
	$data_counter = 0;
	$data_columns = array();
	$pval_columns = array();
	

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


	
	$data_counter++;
	}


	#print_r ($data_headings);
	
	#convert the array to a comma delimited string for the graph
	$data_headings_str = implode(",", $data_headings);
	$data_headings_full_str = "categories: [$data_headings_str]";
	
	
	#print $test;

	
	
	#print $matching_lines;


	if ($matching_lines == "")

	{
		$hits = "no data for this locus_tag";
		$graph_size = "<div id=\"container\" style=\"width: 800px; height: 100px; margin: 0 auto\"></div>";
	}
	else
	{
		$hits = $graph_title;
		$graph_size = "<div id=\"container\" style=\"width: 800px; height: 400px; margin: 0 auto\"></div>";
	}

	#this allows for multiple lines for the same locus tag.	
	
	$lines = explode("\n", $matching_lines);
	

	$data_lines = array();
	$pval_lines = array();
	$position = array();
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
					
			


					$data_points = array();					

					foreach($data_columns as $col_number)
					{
						
						#print "column:$col_number";
						#print "data:$cells[$col_number]";
						$data_cell = $cells[$col_number];						
						if ($cells[$col_number] == "NA")
						{		
							$data_cell = 0;
						}						

						array_push ($data_points, $data_cell);
						
			
					}

					


					$data_points_comma = implode(",",$data_points);
					#print $data_points_comma;
					$data_line =  "{
						name: '$data_heading $occurence',
						
						data: [$data_points_comma]}";	

					array_push ($data_lines, $data_line);

			




										
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
							$data_cell = "null";
						}

						array_push ($pval_points, $data_cell);
				
			
					}

					


					$pval_points_comma = implode(",",$pval_points);
					
					$data_line =  "{
						name: '$pval_heading $occurence',yAxis: 1,
						data: [$pval_points_comma],
						type: 'scatter'
						}";

					array_push ($pval_lines, $data_line);
					
					
					}
					$occurence++;
					}
					}




					$all_data_lines = implode (",",$data_lines);
					
					$all_pval_lines = implode (",",$pval_lines);
					
					$all_data = $all_data_lines.",".$all_pval_lines;
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
		print	"<script type=\"text/javascript\" src=\"$js_dir/themes/gray.js\"></script>";
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
						marginRight: 300,
						marginBottom: 25
					},
					title: {
						
						<?php
						print "text: '$hits',
						x: -20";
						?>
					},legend: {
						layout: 'vertical',
						 backgroundColor:'#FFFFFF',
						//floating: true,
						align: 'right',
						verticalAlign: 'top',
						x: -80,
						y: 100,
						borderWidth: 1
					},

					
					 xAxis: {
						<?php
						print $data_headings_full_str;
						?>
					},


				

					yAxis: [{labels: {
            formatter: function() {
               return this.value ;
            }},title: {
							<?php							
							print "text: '$data_heading'";
							?>
						}},{min:0,max:1,labels: {
            formatter: function() {
               return this.value;
            }},
						

title: {
							<?php							
							print "text: '$pval_heading'";
							?>
						},opposite:true
,plotBands: [{ // Light air
            from: 0,
            to: 0.05,
            color: 'rgba(68, 170, 213, 0.1)',
            label: {
               text: '0.05 p-val'}}]
						
						









					}],
					tooltip: {
						formatter: function() {
				                return '<b>'+ this.series.name +'</b><br/>'+
								this.x +': '+ this.y +'';
						}
					},
					
					series: [

<?php
print $all_data;
#print "{ name: 'fold change 1', yAxis: 1,data: [0.389323776,0.787546904,-0.311822997]},{ name: 'p-value 1', data: [0.679210996,0.197481608,0.999967375], type: 'scatter' } ";

?>
]
				});
				
				
			});
				
		</script>
		
	</head>
	<body>
		
		<!-- 3. Add the container -->
		
		<?php

		print $graph_size;
		?>

		
				
	</body>
</html>



