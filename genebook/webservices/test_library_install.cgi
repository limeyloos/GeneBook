#!/usr/bin/perl -w
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

use lib "/var/www/tools/";
use strict;

#define the paths from the parameters file.

use parameters;
#print $ws_tmp_dir;


my $query = new CGI;
my $seq_id = $query->param("seq_id");
my $bam = $query->param("bam");
my $fasta = $query->param("fasta");
my $gene = $query->param("gene");
my $genome_id = $query->param("genome");
my $buffer = $query->param("buffer");




####### connect to database ################
# PERL MYSQL CONNECT()
my $connect = Mysql->connect($dbhost, $dbname, $dbuser, $dbpass);

# SELECT DB
$connect->selectdb($dbname);

# MySQL QUERY
my $myquery = "select gene, locus_tag, Strand_Dir, Locus_Start, Locus_End, Pseudo, (select Locus_Start from gene where locus_tag = '$gene') as genestart, (select Locus_End from gene where locus_tag = '$gene') as geneend from gene where Genome_ID = \"$genome_id\" and Locus_End >= ((select Locus_Start from gene where locus_tag = '$gene')-$buffer) and Locus_Start <= ((select Locus_End from gene where locus_tag = '$gene')+$buffer)";

#print $myquery;

# EXECUTE THE QUERY
my $execute = $connect->query($myquery);

my $results_gene_s;
my $results_gene_e;
my @cds_features_forward;
my @cds_features_reverse;

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




#print $results_start." ".$results_end;

my $gene_feature = new Bio::SeqFeature::Generic(-start=>$results_start,
						-end=>$results_end,
						-strand=>$results_strand_dir,
						-primary_tag=>'gene',
						 -tag => {
								locus_tag => $results_locus_tag,
                                                 		gene     => $results_gene,
								
							});

if ($results_pseudo eq "True")
{

$gene_feature->add_tag_value("pseudo","");

}


if ($results_strand_dir == "1")
{
	push (@cds_features_forward,$gene_feature);
}
else
{
	push (@cds_features_reverse,$gene_feature);
}

}

if ($results_gene_s < 0)
{
$results_gene_s = 0;
}
#print $results_gene_s;
#print "\n";
#print $results_gene_e;
my $length  = $results_gene_e-$results_gene_s;

my $sam = Bio::DB::Sam->new(#-bam  =>"/nfs_netapp/erichar4/BAMS/dublin_negative_strand_sorted.bam",
                             #-fasta=>"/nfs_netapp/erichar4/our_genomes/dub_3246_CM001151.fasta",
                             #-bam  =>"/nfs_netapp/erichar4/BAMS/typhim_positive_strand_sorted.bam",
                             #-bam  =>"/nfs_netapp/erichar4/BAMS/528_06_rpt2_GCCAAT_L001_trimmed_vs_typhim_474_sorted.bam",
                             	-bam => $ws_tmp_dir."/".$bam,
				-fasta=>$ws_tmp_dir."/".$fasta,
                             
                             
                             -expand_flags => 1
                             );



#my $end = time();
#printf("open file %.2f\n", $end - $start);



 my @targets    = $sam->seq_ids;
 my @alignments = $sam->get_features_by_location(#-seq_id => 'CP002487.1',
 	 					-seq_id => $seq_id,
 	 					-type   => 'read_pair',
                                                 -start  => $results_gene_s,
                                                 -end    => $results_gene_e
 						
 						);

my $i = 0;

#$end = time();
#printf("sam by_location %.2f\n", $end - $start);
###Bio Graphics###








#$end = time();
#printf("added tracks %.2f\n", $end - $start);


my $panel = Bio::Graphics::Panel->new(
                                      -length    => $length,
                                      -width     => 1000,
                                      -pad_left  => 10,
                                      -pad_right => 10,
					-start => $results_gene_s,
					#-image_class => 'GD::SVG'
                                     );

my $full_length = Bio::SeqFeature::Generic->new(
                                                -start => $results_gene_s,
                                                -end   => $results_gene_e,
                                               );







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


$panel->add_track($full_length,
                  -glyph   => 'arrow',
                  -tick    => 2,
                  -fgcolor => 'black',
                  -double  => 1,
                 );

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
#printf("added gb track %.2f\n", $end - $start);


########################################Next must add the note which will be the matching pairs.



###parse cigar/MD format and add to annotation 

 #open (MDFILE, '>mdfile.txt');


my @feature_array;


my %read_frequency;
my %mate_pairs;
my $start1;
my $end1;

for my $a (@alignments) {

    # where does the alignment start in the reference sequence
    my $seqid  = $a->seq_id;
   # print $seqid."\n";
    my $start  = $a->start;
    my $end    = $a->end;


    
   my ($f_mate,$s_mate) = $a->get_SeqFeatures;
   

my $paired_length = $a->length;

    	
	 $start1 = $f_mate->start;
	my $flag = $f_mate->flag;
	#print "$flag\t";
    	 $end1 = $f_mate->end;   


my $start2;
my $end2;

if ($s_mate)
{
	
	my  	    $start2 = $s_mate->start;
    my	    $end2 = $s_mate->end;
    
    
   # print "first $start1 $end1 second $start2 $end2\n";
    
       
    if ($mate_pairs{"$start1,$end1"})
		{
		#print "if $mate_pairs{\"$start1,$end1\"}"."\t$start2,$end2\n";

$mate_pairs{"$start1,$end1"} = $mate_pairs{"$start1,$end1"}." s$start2"."e$end2";	
			
		}
		
	else
	{
	#print "else $mate_pairs{\"$start1,$end1\"}"."\t$start2,$end2\n"	;
	$mate_pairs{"$start1,$end1"} = "s$start1"."e$end1 s$start2"."e$end2";	

	
	}
    
	
	
	    if ($mate_pairs{"$start2,$end2"})
		{
		#print "if2 $mate_pairs{\"$start2,$end2\"}"."\t$start1,$end1\t $start2 $end2\n";

$mate_pairs{"$start2,$end2"} = $mate_pairs{"$start2,$end2"}." s$start1"."e$end1";	
			
		}
		
	else
	{
	#print "else2 $mate_pairs{\"$start2,$end2\"}"."\t$start1,$end1\t $start2 $end2\n"	;
	$mate_pairs{"$start2,$end2"} = "s$start2"."e$end2 s$start1"."e$end1";	

	
	}
	
	
	
	
	
	
	
	
	
	
	if ($read_frequency{"$start2,$end2"})               
{
$read_frequency{"$start2,$end2"} = $read_frequency{"$start2,$end2"}+1;

#print "read freq $start2,$end2"." frequency ".$read_frequency{"$start2,$end2"}." mate  paired length $paired_length\n";
#print "\n\n\n only in second";
}
else
{
$read_frequency{"$start2,$end2"} = 1;
#print "read freq $start2,$end2"." frequency ".$read_frequency{"$start2,$end2"}." mate  paired length $paired_length\n";
	
	
}  
}
    
if ($read_frequency{"$start1,$end1"})               
{
$read_frequency{"$start1,$end1"} = $read_frequency{"$start1,$end1"}+1;

#print "read freq $start1,$end1"." frequency ".$read_frequency{"$start,$end"}." mate  paired length $paired_length\n";

}
else
{
$read_frequency{"$start1,$end1"} = 1;
#print "first one $start1,$end1"." frequency ".$read_frequency{"$start,$end"}." mate $start2 $end2 paired length $paired_length\n";



}
    


    

#my $feature = Bio::SeqFeature::Generic->new(-start=>$start,-end=>$end, -primary=>$read_frequency{"$start:$end"});

$i++;






 #$track->add_feature($feature2);
 

#last;

}


#open (COUNTS, ">counts.txt");

#printf("iterated through reads %.2f\n", $end - $start);

    for my $key ( keys %read_frequency ) 
	{
       	 my $value = $read_frequency{$key};
	my $r = 150-2*($value);
if ($r < 0)
{
	$r = 0;
}

	my $g = 200-2*($value);

if ($g < 0)
{
	$g = 0;
}

        my $rgb = "$r,$g,255";
	my @locs = split(",",$key);
	my $start = $locs[0];
	my $end = $locs[1];
	my $note;
	
	if($mate_pairs{"$start,$end"})
	{
		$note = $mate_pairs{"$start,$end"};
	}
	else
	{
		$note = " ";	
	}
	#print COUNTS "$start\t$end\t$value\n";
	if (($results_gene_s<=$start)&&($results_gene_e >= $end))
	{
	my $feature = Bio::SeqFeature::Generic->new(-start=>$locs[0],-end=>$locs[1], -primary=>"s$start"."e$end", -tag => {rgb => $rgb,
                                                 note     => $note});
	push (@feature_array,$feature);
	}

  }

#$end = time();
#printf("reads %.2f\n", $end - $start);







#$feature->add_SeqFeature($feature3);
#$feature->add_hit($data);
 #$track->add_feature(@feature_array);

 #$track->add_feature($feature3);
my $id = 1;
$panel->add_track([@feature_array],
                             # -glyph     => 'segments',
                              -bump => +1,
                              -label     => 1,
			      -height	 => 1,
                          	-bgcolor => 'blue',
				-fgcolor => sub { 
my $feature = shift; 
my $colour = "";
if ($feature->has_tag('rgb'))
{
for my $val ( $feature->get_tag_values('rgb'))  {
      $colour = $val;   
   }
}


return "rgb($colour)" ;
} ,

				
                             );

#$end = time();
#printf("add_track %.2f\n", $end - $start);
#print $panel->png;




#open (FILE_MAP, ">$ws_tmp_dir/alignment_map.html");
my ($url,$map,$mapname) = $panel->image_and_map(
	                                -root => '/var/www/html',
	                                -url  => '/tmp',
	                                -link => sub {
	                                      my $feature = shift;
	                                      my $name = $feature->start;
	                                      my $id = $feature->primary_tag;
	                                      
  my $note;
if ($feature->has_tag('note'))
{
for my $val ( $feature->get_tag_values('note'))  {
     $note = $val;   
   }
}


	                                      
	                                      
	                                      return "http://www.google.com/search?q=$name\" id='$id' data-maphilight='{\"groupBy\":\".$id\"}' class=\"$note";
	                                 }
	                                 );
#$end = time();
#printf("image map %.2f\n", $end - $start);


print "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js\"></script>
	<script type=\"text\/javascript\" src=\"http://davidlynch.org/projects/maphilight/jquery.maphilight.min.js\"></script>
	<script type=\"text\/javascript\">\$(function() {
		\$('.map').maphilight();
	});</script>";

print "<H2>$seq_id</H2>";
	#$end = time();
	#printf("header %.2f\n", $end - $start);
	print "<IMG class = 'map' SRC='$url' USEMAP='#$mapname' BORDER='0' />";
	#$end = time();
	#printf("image %.2f\n", $end - $start);
	print $map;
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
