#!/usr/bin/perl -w
### This CGI makes a coverage plot of NGS data based on base position counts

print "Content-type: text/html\n\n";
use Time::HiRes qw( time );
my $start = time();

use lib "/usr/local/BioPerl-1.6.901/";
use lib "/nfs_netapp/erichar4/perl/Bio-Graphics-2.25/lib/";
#use lib "/nfs_netapp/erichar4/perl/GD-2.46/";
#use lib "nfs_netapp/erichar4/perl/GD-2.46/GD/";
use CGI;
use CGI::Carp qw(warningsToBrowser fatalsToBrowser); 
use Bio::Perl;
use Bio::SeqFeature::Generic;
use Bio::Graphics;
use Bio::DB::Sam;
use Bio::SeqUtils;
use Mysql;
#use GD;
 use Data::Dumper;
use lib "/var/www/tools/";
use strict;

#get the paths from the parameters file.
use parameters;

# Get the parameters from the URL
my $query = new CGI;
my $seq_id = $query->param("seq_id");
my $bam = $query->param("bam");
my $fasta = $query->param("fasta");
my $gene = $query->param("gene");
my $genome_id = $query->param("genome");
#the number of base pairs up and down stream of the central gene
my $buffer = $query->param("buffer");


####### connect to database ################
# PERL MYSQL CONNECT()
my $connect = Mysql->connect($dbhost, $dbname, $dbuser, $dbpass);

# SELECT DB
$connect->selectdb($dbname);

# MySQL QUERY to get the genes up and downstream of the gene in question (based on the buffer)
my $myquery = "select gene, locus_tag, Strand_Dir, Locus_Start, Locus_End, Pseudo, (select Locus_Start from gene where locus_tag = '$gene') as genestart, (select Locus_End from gene where locus_tag = '$gene') as geneend from gene where Genome_ID = \"$genome_id\" and Locus_End >= ((select Locus_Start from gene where locus_tag = '$gene')-$buffer) and Locus_Start <= ((select Locus_End from gene where locus_tag = '$gene')+$buffer)";

#print $myquery;

# EXECUTE THE QUERY
my $execute = $connect->query($myquery);

my $results_gene_s;
my $results_gene_e;
my @cds_features_forward;
my @cds_features_reverse;
my $query_output;
while (my @results = $execute->fetchrow())
{
my $results_gene = $results[0];
my $results_locus_tag = $results[1];
my $results_strand_dir = $results[2];
my $results_start = $results[3];
my $results_end = $results[4];
my $results_pseudo = $results[5];
$results_gene_s = $results[6]-$buffer;
$results_gene_e = $results[7]+$buffer;
$query_output ="result!";



# Each gene is made into a feature
my $gene_feature = new Bio::SeqFeature::Generic(-start=>$results_start,
						-end=>$results_end,
						-strand=>$results_strand_dir,
						-primary_tag=>'gene',
						 -tag => {
								locus_tag => $results_locus_tag,
                                                 		gene     => $results_gene,
								
							});
# add pseudo note if the gene is a pseudogene
if ($results_pseudo eq "True")
{

$gene_feature->add_tag_value("pseudo","");

}

# record strandedness of gene
if ($results_strand_dir == "1")
{
	push (@cds_features_forward,$gene_feature);
}
else
{
	push (@cds_features_reverse,$gene_feature);
}

}

# Catch anomolies
if ($query_output eq "")
{
	print "This locus_tag does not exist";
	exit;
}

#for non circular genomes, if the start-buffer is a negative number
if ($results_gene_s < 0)
{
$results_gene_s = 0;
}

my $length  = $results_gene_e-$results_gene_s;

#create new SAM instance
my $sam = Bio::DB::Sam->new(	
				
				#-bam => "http://www.ark-genomics.org/tmp/Richardson_BAM/typhim_474_sorted.bam", #testing FTP
                             	-bam => $ws_tmp_dir."/".$bam,
				-fasta=>$ws_tmp_dir."/".$fasta,
                             -expand_flags => 1
                             );



#my $end = time();
#printf("open file %.2f\n", $end - $start);


#get counts for the region defined by the gene and buffer
my ($coverage) = $sam->features(#-seq_id => 'CP002487.1',
 	 					-seq_id => $seq_id,
 	 					-type   => 'coverage',
                                                 -start  => $results_gene_s,
                                                 -end    => $results_gene_e,

 						);






#print Dumper($coverage);
#$end = time();
#printf("sam by_location %.2f\n", $end - $start);




#bio graphics to create panel 
my $panel = Bio::Graphics::Panel->new(
                                      -length    => $length,
                                      -width     => 1000,
                                      -pad_left  => 50,
                                      -pad_right => 50,
					-start => $results_gene_s,
					
					#-image_class => 'GD::SVG'
                                     );

#length of panel as a tick __|__|__|__
my $full_length = Bio::SeqFeature::Generic->new(
                                                -start => $results_gene_s,
                                                -end   => $results_gene_e,
                                               );

#coverage track
$panel->add_track($coverage,
                  -glyph   => 'wiggle_xyplot',
		-graph_type => 'line',
		-height => 50,
	-bgcolor => 'blue',
#max_score => 3000
	
                 );


#track for forward features
$panel->add_track([@cds_features_forward],
                  -glyph   => 'generic',
                  -bump =>0,
                  -stranded => 1,
		  -bgcolor => sub { 
             	     			my $feature = shift; 
					if ($feature->has_tag('pseudo'))
             	  	  		{
						return 'pink'
					}		
					else
					{
						return 'blue'
					}
             	 		 } ,

             	  -label => sub {
             	  	  		my $feature = shift;
             	  	  		if ($feature->has_tag('gene'))
             	  	  		{
             	  	  			for my $val ($feature ->get_tag_values('gene'))
             	  	  			{
             	  	  				return $val
             	  	  			}
             	  	  		}
             	  	  		else
             	  	  		{
             	  	  			for my $val ($feature ->get_tag_values('locus_tag'))
             	  	  			{
             	  	  				return $val
             	  	  			}
             	  	  		}



             	 		 }

                 );


# add the tick track
$panel->add_track($full_length,
                  -glyph   => 'arrow',
                  -tick    => 2,
                  -fgcolor => 'black',
                  -double  => 1,
                 );

# add the reverse feature track (added after tick so that the forward and reverse straddle this)
$panel->add_track([@cds_features_reverse],
                  -glyph   => 'generic',
          	  -bump =>0,
		  -stranded => -1,
                  -bgcolor => sub 
				{ 
                   			my $feature = shift; 
					if ($feature->has_tag('pseudo'))
             	  	  		{
						return 'pink'
					}		
					else
					{
						return 'green'
					}
				} ,
		  -label       => sub 
				{
					my $feature = shift;
					if ($feature->has_tag('gene'))
					{
						for my $val ($feature ->get_tag_values('gene'))
						{
            						return $val
         					}
					}							
					else
					{
						for my $val ($feature ->get_tag_values('locus_tag'))
						{
           						return $val
         					}
					}
				}
                 );

#$end = time();
#printf("added tracks %.2f\n", $end - $start);


#Save the image of the alignment file - should the path be printed so that the user can come to the image at a later date?
open (FILE, ">$ws_tmp_dir/$seq_id"."_$gene"."_alignment.png");
print FILE $panel->png;

#$end = time();
#printf("image map %.2f\n", $end - $start);




print "<H2>$seq_id</H2>";
	#$end = time();
	#printf("header %.2f\n", $end - $start);
	#$end = time();
	#printf("image %.2f\n", $end - $start);
print "<IMG SRC='$web_tmp_dir/$seq_id"."_$gene"."_alignment.png'></img>";
	#$end = time();
	#printf("map %.2f\n", $end - $start);
#close (FILE_MAP);
#$end = time();
#printf("close map %.2f\n", $end - $start);







# open (FILE, '>alignment.png');
# print FILE $panel->png;
# close (FILE); 
#close(MDFILE);
#$end = time();
#printf("%.2f\n", $end - $start);
