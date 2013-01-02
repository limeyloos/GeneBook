#!/usr/local/bin/perl -w

## Uses results from BLAST against KEGG to extract the pathways (and higher descriptions) associated with orthologs, this gives the counts for pseudogenes ##

use strict;
use Bio::SearchIO; 
use Bio::SeqIO;

# kegg_codes.txt
my $kegg_code_file = shift;

# salmonella_names.txt 
my $organisms_list = shift;
my $directory = shift;

#opens the kegg code file.
open(INFILE, "$directory"."$kegg_code_file") or die ("Couldn't open file $kegg_code_file\n");
#<> puts the data into an array.
my @kegg_codes = <INFILE>;
close(INFILE);


#opens the list of genomes which have been blasted.
open(INFILE, "$directory"."$organisms_list") or die ("Couldn't open file $organisms_list\n");
#<> puts the data into an array.
my @organisms = <INFILE>;
close(INFILE);


#define the hash for all pathways
my %pathways;



my %kegg_hash;
my $kegg_locus_tag;
my $kegg_code = " ";


#fill the hash with the data from each pathway file.
foreach my $pathway_file (@kegg_codes)
{
chomp($pathway_file);
if (-e "$directory"."$pathway_file"."_pathway.list")
{
open (INFILE, "$directory"."$pathway_file"."_pathway.list") or die ("couldn't open the file  $pathway_file"."_pathway.list");
my @kegg_data = <INFILE>;
close(INFILE);

#make kegg into a hash with locus tag as the key.
foreach my $kegg_line (@kegg_data){


if ($kegg_line =~ m/\w+\W       # kegg code
	    (\w+)          # locus tag
	    \t\w+\W 		 #tab
	    (\w+)/ox        # KO number

	    )
	{
	   ($kegg_locus_tag,$kegg_code) = ($1,$2);
#print $1." ".$2;

#hash with locus tag as key and list of tab delimited of kegg pathways
if (exists $kegg_hash{$kegg_locus_tag})
{
	$kegg_hash{$kegg_locus_tag} = $kegg_hash{$kegg_locus_tag}."\t".$kegg_code;
}
else
{
	$kegg_hash{$kegg_locus_tag} = $kegg_code;
}
}

#print OUTFILE "$kegg_locus_tag\t$kegg_hash{$kegg_locus_tag}\n";
}



}
}

my $size =  keys( %kegg_hash );
#print $size;


my %pathway_hits;
my %path_desc_no;
my %path_desc_name;
my %upper_path_desc;



# Open pathway.list
open (FILE, "$directory"."pathway.list");
my @pathway_desc = <FILE>;
close(FILE);

my $pathway_upper_desc;
foreach my $pathway_desc (@pathway_desc) 
{


if ($pathway_desc =~ m/\#\#    # two hashes showing level of description
	    (.+)/ox          # locus tag

	    )
{
	$pathway_upper_desc = $1;
	#print $pathway_upper_desc."\n";
	foreach my $blast_file (@organisms)
	{
	$upper_path_desc{$pathway_upper_desc}->{$blast_file} = 0;
	#print $upper_path_desc{$pathway_upper_desc}->{$blast_file};
	}
	
}



if ($pathway_desc =~ m/(\w+)\t(.+)/)
	{
				
		$path_desc_no{$1}->{'desc'} = $2;
		$path_desc_no{$1}->{'upper_desc'} = $pathway_upper_desc;
		
		foreach my $blast_file (@organisms)
		{			
			chomp($blast_file);			
			$path_desc_no{$1}->{$blast_file} = 0;
			#print $path_desc_no{$1}->{"$blast_file"};
			#print "blast file1 $blast_file\n";

			#$path_desc_name{$1}->{$blast_file} = "";
			#print $path_desc_name{$1}->{"$blast_file"};
		}	
	}
}

my $counter;
open (OUTFILE, ">$directory/gall287pseudo/"."keggpathways_names.txt");
open (OUTFILE2, ">$directory/gall287pseudo/"."keggpathways_names_soap.txt");
#go through each set of blast results and find the first match in the hash which has a pathway.
foreach my $blast_file (@organisms)
{
	 
	chomp($blast_file);
	print "$directory"."$blast_file"."_kegg.blast";
	if (-e "$directory"."$blast_file"."_kegg.blast")
	{
		
		#open the blast file
		
		my $in = new Bio::SearchIO(-format => 'blast', -file   => "$directory"."$blast_file"."_kegg.blast");
		while( my $result = $in->next_result ) 
		{
		
			#print "opened 	$blast_file"."_kegg.blast\n";		
			#the number of hits - 0 if no matches in blast
			my $num_hits = $result->num_hits;
			
			#the locus tag of the query sequence.	
			my $locus_tag = $result->query_name;
			#print OUTFILE "$num_hits\t$locus_tag\n";

			#$pathway_hits{$locus_tag}; 
			$pathway_hits{$locus_tag}->{'match'} = "no";
			while( my $hit = $result->next_hit ) 
			{
			

				while( my $hsp = $hit->next_hsp ) 
				{
					#print OUTFILE "$locus_tag\t".$hit->name."\n";
					#parse the hit name to just be the locus tag separate by :
	

					my $percent_hit_length = sprintf("%.0f",(($hsp->length('hit'))/($hit->length))*100);
					#print $percent_hit_length, "\n";
					my $percent_query_length = sprintf("%.0f",($hsp->length('query')/$result->query_length)*100);
					my $positives = sprintf("%.0f", 100*($hsp->frac_conserved));
					
					

					if ($positives >= 75 and $percent_query_length >= 85)
					{
					$pathway_hits{$locus_tag}->{'match'} = "maybe";
					print OUTFILE2 $locus_tag."\t".$hit->name."\t".$hit->description."\n";						
					#print "$locus_tag	$positives	$percent_query_length\n";
					my @hit_split = split(/:/, $hit->name);
					my $hit_locus_tag = $hit_split[1];
					#is the hit name in the hash? if yes are there any pathways, if no go to next hit.
					if (exists ($kegg_hash{$hit_locus_tag}))
					{
						
																		
						my @pathway_codes = split (/\t/, $kegg_hash{$hit_locus_tag});
						foreach my $pathway_codes (@pathway_codes)
						{
						if ($pathway_codes =~ m/\D+     # kegg code
	   							 (\d+)/ox       # pathway number
									       
						)
							{
								#print "$1\n";								
								$pathway_hits{$locus_tag}->{'match'} = "yes";								
								
								if (exists ($pathway_hits{$locus_tag}->{$1}))
								{
									 $pathway_hits{$locus_tag}->{$1} = $1;
								
									
									$path_desc_name{$1}->{$blast_file}->{$locus_tag} = $locus_tag;	
									#print $path_desc_name{$1}->{$blast_file}->{$locus_tag};

									#if (exists ($path_desc_name{$1}->{"$blast_file"}))
									#{
									# 	$path_desc_name{$1}->{"$blast_file"} = $locus_tag."\t".$path_desc_name{$1}->{"$blast_file"};
									#}
									#else
									#{
								#		$path_desc_name{$1}->{"$blast_file"} = $locus_tag;						
								#	}
									
						
								}
								else
								{
									#print "blast file2 $blast_file	$1\n";									
									$pathway_hits{$locus_tag}->{$1} = $1;
									#print "locus_tag".$locus_tag."\t".$pathway_hits{$locus_tag}->{$1};
									#print "before ".$path_desc_no{$1}->{"$blast_file"};						
									$path_desc_no{$1}->{"$blast_file"} = $path_desc_no{$1}->{"$blast_file"} +1 ;
									#print "after ".$path_desc_no{$1}->{"$blast_file"}."\n";

									


								}
							print OUTFILE "$locus_tag\t".$pathway_hits{$locus_tag}->{$1}."\n";
													
							}
						}



						 						
						
					}
				}
					
				}
			}
		}



	}
}
print $counter;
close (OUTFILE);

#use Data::Dumper;
#print OUTFILE Dumper(%path_desc_no);
#print OUTFILE "#####################################################";
#print OUTFILE Dumper(%path_desc_name);


open (OUTFILE, ">$directory/gall287pseudo/"."keggpathways_names_75i85l.txt");
foreach my $k (sort keys %path_desc_name ) 
{ 

	#print "1 ";
	
	foreach my $org_name (@organisms)
	{
	#print "2 ";  	
	print OUTFILE $org_name;
	print OUTFILE "\t $k";
	#my @duplicate_locus_tags = split (/\t/, $path_desc_name{$k}->{"$org_name"});
	foreach my $key (keys %{$path_desc_name{$k}->{"$org_name"}})
	{
	#print @{$key};
	print OUTFILE "\t".$path_desc_name{$k}->{"$org_name"}->{$key};
	}
	

	print OUTFILE "\n";
	}
	

}
close (OUTFILE);
open (OUTFILE, ">$directory/gall287pseudo/"."keggpathways_counts_75i85l.txt");
foreach my $k (sort keys %path_desc_no ) 
{ 

	#print "1 ";
	print OUTFILE "$k";
	print OUTFILE "\t".$path_desc_no{$k}->{'desc'};
	my $upper_description = $path_desc_no{$k}->{'upper_desc'};
	#print $upper_description;

	foreach my $org_name (@organisms)
	{
	#print "2 ";  	
	#print OUTFILE $org_name;

	#my @duplicate_locus_tags = split (/\t/, $path_desc_name{$k}->{"$org_name"});
	
	
	
	print OUTFILE "\t".$path_desc_no{$k}->{"$org_name"};
	
	$upper_path_desc{$upper_description}->{$org_name} = $upper_path_desc{$upper_description}->{$org_name} + $path_desc_no{$k}->{"$org_name"};
	

	
	}
	print OUTFILE "\n"

}
close (OUTFILE);
######################
open (OUTFILE, ">$directory/gall287pseudo/"."kegg_upper_pathways_counts_75i85l.txt");
foreach my $k (sort keys %upper_path_desc ) 
{ 

	print "$k";
	print OUTFILE "$k";
	print OUTFILE "\t".$path_desc_no{$k}->{'upper_desc'};
	foreach my $org_name (@organisms)
	{
	#print "2 ";  	
	#print OUTFILE $org_name;

	#my @duplicate_locus_tags = split (/\t/, $path_desc_name{$k}->{"$org_name"});
	
	
	
	print OUTFILE "\t".$upper_path_desc{$k}->{"$org_name"};
	
	

	
	}
	print OUTFILE "\n"

}
close (OUTFILE);


open (OUTFILE, ">$directory/gall287pseudo/"."kegg_matches.txt");
foreach my $l (sort keys %pathway_hits)
{
	print OUTFILE $l."\t".$pathway_hits{$l}->{'match'}."\n";
}
close (OUTFILE);









