#!/usr/local/bin/perl -w

## Uses results from BLAST against KEGG to extract the pathways (and higher descriptions) associated with orthologs, this gives the counts for functional genes ##

use strict;
use Bio::SearchIO; 
use Bio::SeqIO;

my $kegg_code_file = shift;
my $organisms_list = shift;
my $directory = shift;
my $directory2 = shift;

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
#print "$directory"."$pathway_file"."_pathway.list\n";
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



my %path_desc_no;
my %path_desc_name;
my %upper_path_desc;


#remove pseudogenes from ortholog list




# Open pathway.list
open (FILE, "pathway.list");
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
	foreach my $org_name (@organisms)
	{
	
	$upper_path_desc{$pathway_upper_desc}->{$org_name} = 0;
	#print $pathway_upper_desc."\t".$upper_path_desc{$pathway_upper_desc}->{$org_name}."\n";
	}
	
}



if ($pathway_desc =~ m/(\w+)\t(.+)/)
	{
				
		$path_desc_no{$1}->{'desc'} = $2;
		$path_desc_no{$1}->{'upper_desc'} = $pathway_upper_desc;
		
		foreach my $org_name (@organisms)
		{			
			chomp($org_name);			
			$path_desc_no{$1}->{$org_name} = 0;
			#print $path_desc_no{$1}->{"$org_name"};


			#$path_desc_name{$1}->{$org_name} = "";
			#print $path_desc_name{$1}->{"$org_name"};
		}	
	}
}


my %pseudo_locus_tags;
open (OUTFILE2, ">keggpathway_to_locus_tag.txt") or die ("Couldn't open file $kegg_code_file\n");;
#go through each set of results and find the first match in the hash which has a pathway.
foreach my $org_name (@organisms)
{
	 
	chomp($org_name);
	my @orthologs;
	#open each ortholog file.
	print "$directory2"."recip/$org_name/orthologs.txt\n";
	if (-e "$directory2"."recip/$org_name/orthologs.txt")
	{
		open (FILE2, "$directory2"."recip/$org_name/orthologs.txt") or die ("unlucky");
		@orthologs = <FILE2>;
		close(FILE2);
	}


	#open fasta file
	print "$directory2"."$org_name"."_pseudos.fasta\n";
	if (-e "$directory2"."$org_name"."_pseudos.fasta")
	{
		open (FILE, "$directory2"."$org_name"."_pseudos.fasta");
		my @pseudo_check = <FILE>;
		close(FILE);

		foreach my $pseudo_fasta_line (@pseudo_check)
		{
			if ($pseudo_fasta_line =~ m/\>     # fasta
	   							 (\w+)/ox       # locus_tag
									       
						)
			{
				$pseudo_locus_tags{$1}= $1;
				#print $pseudo_locus_tags{$1};
			}			
		}
		

	}

	foreach my $ortholog_line (@orthologs)
	{
		my @pathway_counter = split (/\t/, $ortholog_line);
		

		foreach my $pathway_check (@pathway_counter)
		{
			
			#if the first entity in the array matches our list of pseudos ignore it.	
			my $locus_tag = $pathway_counter[0];
			#print $locus_tag;
			if (exists $pseudo_locus_tags{$locus_tag})
			{
				

				if ($pathway_check =~ m/\D+     # kegg code
	   							 (\d+)/ox       # pathway number
									       
						)
				{			
					if (exists ($path_desc_no{$1}))
					{
						#$path_desc_no{$1}->{$org_name} = $path_desc_no{$1}->{$org_name} +1;

				

						#print $pathway_counter[0]."\t";
						#print $pseudo_locus_tags{$pathway_counter[0]}."\n";

					}
				}
			

			}
				 

			else
			{
			
					

				if ($pathway_check =~ m/(\d+)/ox       # pathway number
									       
						)
				{			
					print $1."\n";					
					if (exists ($path_desc_no{$1}))
					{
						$path_desc_no{$1}->{$org_name} = $path_desc_no{$1}->{$org_name} +1;

						my $sig_num_path = sprintf("%05d", $1);
						print OUTFILE2 $locus_tag."\t$sig_num_path\n";
					}
					
					

				}

				
			}
		}
	}



}

close (OUTFILE2);




open (OUTFILE, ">keggpathways_counts_nonpseudo.txt") or die ("Couldn't open file $kegg_code_file\n");;
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
	#print "$upper_description\t$org_name\t".$upper_path_desc{$upper_description}->{$org_name}."\n";

	#print 	$upper_description."\tpop";
	#print $upper_path_desc{$upper_description}->{$org_name};
	#print "\n";

	$upper_path_desc{$upper_description}->{$org_name} = $upper_path_desc{$upper_description}->{$org_name} + $path_desc_no{$k}->{"$org_name"};
	

	
	}
	print OUTFILE "\n"

}
close (OUTFILE);
######################


open (OUTFILE2, ">kegg_upper_pathways_counts_nonpseudo.txt");
foreach my $k (sort keys %upper_path_desc ) 
{ 

	#print "1 ";
	print OUTFILE2 "$k";
	#print "\n".$k;
	#print "\n".$upper_path_desc{$k}->{'upper_desc'};
	#print  "\t".$path_desc_no{$k}->{'upper_desc'};
	foreach my $org_name (@organisms)
	{
	#print "2 ";  	
	#print OUTFILE2 $org_name;

	#my @duplicate_locus_tags = split (/\t/, $path_desc_name{$k}->{"$org_name"});
	
	
	
	print OUTFILE2 "\t".$upper_path_desc{$k}->{"$org_name"};
	
	

	
	}
	print OUTFILE2 "\n"

}
close (OUTFILE2);










