<?php
### These are all the directories which are referred to in GB, they are defined as variables here
## This should be referred to by files in gb's php folder like so:
## and files in webservice's folder like so:
## include("parameters.php");




#The URL of where the webservices are located

$ws_url = "http://ris-valx02.roslin.ed.ac.uk/webservices/";

#The URL of where the temp file is located (should this ever be referred to?

$tmp_url = "http://ris-valx02.roslin.ed.ac.uk/tmp/";

#The directory for 'other files' within GB

$gb_files_dir = "/var/www/html/gb/sites/all/files";

#The directory of the temporary folder

$ws_tmp_dir = "/var/www/html/tmp";

#The directory for Neato/Graphviz

$neato_dir = "/usr/bin/neato";

#The relative url for weblinks
$rel_web_url = "/gb/?q=genebook_search";

# Jquery
$jq_min_dir = "/gb/sites/all/modules/jquery_update/replace";

# js directory
$js_dir = "/gb/sites/all/js";

# javascript highcharts directory
$js_highcharts_dir = "$js_dir/Highcharts-2.1.6/js";




?>
