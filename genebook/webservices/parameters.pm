#!/usr/bin/perl -w
### These are the directories/parameters referred to in GB's CGI scripts
### This is achieved by the following two lines of code:
## use lib "/var/www/tools/";
## use parameters;

package parameters;
use strict;
use Exporter;
our @ISA = 'Exporter';
our @EXPORT = qw($ws_tmp_dir $dbhost $dbuser $dbpass $dbname $web_tmp_dir);
our ($ws_tmp_dir, $dbhost, $dbuser, $dbpass, $dbname, $web_tmp_dir);







#The directory of the temporary folder
$ws_tmp_dir = "/var/www/html/tmp";
#The url of the temporary folder
$web_tmp_dir = "http://ris-valx02.roslin.ed.ac.uk/tmp";

#Database parameters
$dbhost = 'localhost';
$dbuser = 'micropath';
$dbpass = 'micropath';
$dbname = 'micropath';
