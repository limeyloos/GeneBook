#!/usr/local/bin/perl -w

## This script takes the output from swissprot and TREMBL BLAST results and adds the results to the annotation if positives >= 75 & percent_hit_length >= 85 and percent_query_length >= 85 ##

use strict;
use Bio::SearchIO; 
use Bio::SeqIO;


my $sp_input_file = shift;
my $tr_input_file = shift;
my $gb_file = shift;
my $outputs = shift;
my $genome_desc = shift;
my $i = 0;
my $j = 0;
my $k = 0;


# define array for no hit locus tags.
my %no_hits = ();
#define hash for hit information.
my %hits = ();
#fill the hash with hit information from swissprot and trembl blasts.
run_fasta($sp_input_file, "swissprot");
#print "".keys(%no_hits)."\n";
run_fasta($tr_input_file, "trembl");

#define hash for gene name information
my %gene_names = ();

#print "".keys(%no_hits)."\n";

#open the genbank file
my $seqio_object = Bio::SeqIO->new(-file => $gb_file, -format => 'genbank');
my $seq_object = $seqio_object->next_seq;

#an array to store the tbl format
my @tbl_array = (">Feature $genome_desc\n");
update_genbank();

open (FILE, ">$genome_desc.tbl");
foreach my $line (@tbl_array)
{
	print FILE $line;


}
close (FILE);

#save the genbank sequence
my $seq_out2 = Bio::SeqIO->new('-file' => ">$outputs", -format => 'genbank');
$seq_out2->write_seq($seq_object);


=head2

 Title   : parse_blast
 Usage   : parse_blast($input_file)
 Function: Opens and parses the blast data
 Returns : A hash array with protein information (strings delimited with \t)
 Args    : The name of the blast file to be opened.

=cut

sub run_fasta
{
	my ($input_file, $database) = @_;
	$i = 0;	
	$j = 0;


	#open the blast file
	my $in = new Bio::SearchIO(-format => 'blast', 
                           -file   => $input_file);

	

	
	while( my $result = $in->next_result ) {
	
				
		#the number of hits - 0 if no matches in blast
		my $num_hits = $result->num_hits;
		
		#the locus tag of the query sequence.	
		my $locus_tag = $result->query_name;
		
		#Check for no hit and put into an array.	
		if ($num_hits == 0 )
		{			
			#put into an array
			$no_hits{$locus_tag} = "";
			print $locus_tag;
			
		$i++;	
			
	
		}
		else
		{
						
			delete $no_hits{$locus_tag};
	  		#go through each hit
	 	 	while( my $hit = $result->next_hit ) 
			{
	   			 ## $hit is a Bio::Search::Hit::HitI compliant object
	    			while( my $hsp = $hit->next_hsp ) 
				{
	

					## ensure that the hit is above 85% across the length of both query and hit and above 75 	positives too.      
					my $percent_hit_length = sprintf("%.0f",(($hsp->length('hit'))/($hit->length))*100);
					#print $percent_hit_length, "\n";
					my $percent_query_length = sprintf("%.0f",($hsp->length('query')/$result->query_length)*100);
					my $positives = sprintf("%.0f", 100*($hsp->frac_conserved));
	
					my @uniprot_id = split("\\|", $hit->name);
					my $hit_name = $uniprot_id[2];
					my $db;
					if ($uniprot_id[0] eq "tr")
					{					
					$db = "trembl";
					}
					elsif ($uniprot_id[0] eq "sp")
					{
					$db = "swissprot";
					}


					my $hit_desc_no_org;
					#parse the hit description to exclude the organism information (OS=).
					my $hit_desc = $hit->description;
					if ($hit_desc =~ m/(.+)\sOS=/ox)
					{
						$hit_desc_no_org = $1;
					}



					if ($positives >= 75 and $percent_hit_length >= 85 and $percent_query_length >= 85)
					{
						#put into array if the key doesn't already exist.
						if (defined($hits{$locus_tag}))
						{
							#do nothing
							#$i++;
						}
						else
						{
							$hits{ $locus_tag } = "$positives	$percent_hit_length	$percent_query_length	$hit_desc_no_org	$hit_name	$db";	
						$j++;

						#delete the no_hit hash entry if present as it is now a hit.
						#print $locus_tag;
					
						#The locus_tag	
						#print $locus_tag;
						#The description

						#The positives etc
						$k++;	
						#print "$k $positives	$percent_hit_length	$percent_query_length	$hit_desc_no_org	$hit_name	$database\n";						

						
						}
					}
					else
					{
						#print "no hit to $locus_tag $database \n";
						$no_hits{$locus_tag} = "";
					}

				}		
    
    			}  
 		}
		


	}

print $j."\n";	
print "".keys(%no_hits)."\n";
}



=head2

 Title   : update_genbank
 Usage   : update_genbank()
 Function: Adds the new annotation to the genbank file.
 Returns : Returns an updated genbank file.
 Args    : Bio::Seq::IO object

=cut

sub update_genbank
{
	
	
	foreach my $feature_obj ($seq_object->get_SeqFeatures)
	{
		
		my $gb_locus_tag;		
		#check locus_tag against hash
		if ($feature_obj->has_tag("locus_tag") and $feature_obj->primary_tag eq "CDS")
		{
			#my ($translation) = $feature_obj->get_tag_values("translation");			
			if ($feature_obj->has_tag("translation"))
			{			
			$feature_obj->remove_tag('translation');			
			}
			#genbank locus_tag			
			($gb_locus_tag) = $feature_obj->get_tag_values("locus_tag");
			#print $gb_locus_tag;
						
			if (defined($no_hits{ $gb_locus_tag }) )
			{
				$feature_obj->remove_tag('product');
		

				#replace the product tag with the new description
				$feature_obj->add_tag_value('product','hypothetical protein');
			}
			

			#check that there is a suitable hit in the blast outputs
			if (defined($hits{ $gb_locus_tag }) )
			{
				#get the protein function data from the hash for the given locus tag.
				my $protein_info = $hits{ $gb_locus_tag };
				#print $protein_info;
				(my $positives,my $percent_hit_length,my $percent_query_length,my $hit_desc_no_org, my $hit_name, my $database) = split("\t",$protein_info);

				#split the $hit_name so that we get the gene name to be added to the gene feature
				(my $gene_name, my $leftover_gname) = split("_", $hit_name);
								
				if ($database eq "swissprot" and ($gene_name =~ /\d/))
				{

				}				
				elsif ($database eq "swissprot")
				{
				$gene_names{$gb_locus_tag} = lc($gene_name);
				#print $gene_names{$gb_locus_tag};	
				}

				my ($product_to_note) = $feature_obj->get_tag_values("product");
				#remove the product tag 
				$feature_obj->remove_tag('product');
				
							
				
				#print $hit_desc_no_org."\t";

				#replace the product tag with the new description
				$feature_obj->add_tag_value('product',$hit_desc_no_org);

				#add a note to describe the blast hit				
				#$feature_obj->add_tag_value('note',"Blastp match to $hit_name against $database with $positives% positives, $percent_query_length% query length coverage and $percent_hit_length% hit length coverage");
				
				#add inference to describe blast hit
				$feature_obj->add_tag_value('note',"similar to $database $hit_name");
				
#				if ((lc($hit_desc_no_org) eq lc($product_to_note)) or (lc($product_to_note) =~ m/^(hypothetical protein|conserved hypothetical protein|putative uncharacterized protein)/i))
#				{
#
#				}
#				else
#				{
#				$feature_obj->add_tag_value('note',$product_to_note);
#				}
				#print "Blastp match to $hit_name against $database with $positives% positives, $percent_query_length% query length coverage and $percent_hit_length% hit length coverage\n";

				
				
			}
		
		
		}
	}
	

	foreach my $feature_obj2 ($seq_object->get_SeqFeatures)
	{
		my $gb2_locus_tag;	

		if ($feature_obj2->has_tag("locus_tag"))
		{				
			($gb2_locus_tag) = $feature_obj2->get_tag_values("locus_tag");		
			#print $gb2_locus_tag;		
			if (defined($gene_names{ $gb2_locus_tag }) and $feature_obj2->primary_tag eq "gene" )
			{
				if ($feature_obj2->has_tag('gene'))				
				{
					$feature_obj2->remove_tag('gene');			
				}
				$feature_obj2->add_tag_value('gene',$gene_names{ $gb2_locus_tag });

			}
		


		#for each feature add a .tbl entry excpet for source which is added in Sequin.
		if ($feature_obj2->primary_tag ne 'source')
		{
			#print $feature_obj->strand;			
			if ($feature_obj2->strand eq -1)
			{
				push (@tbl_array, $feature_obj2->end."\t".$feature_obj2->start."\t".$feature_obj2->primary_tag."\n");
			}
			elsif ($feature_obj2->strand eq 1)
			{			
				push (@tbl_array, $feature_obj2->start."\t".$feature_obj2->end."\t".$feature_obj2->primary_tag."\n");
			}
		}
		
		for my $tag ($feature_obj2->get_all_tags) 
		{             
			#only want locus tag if the primary tag is a gene.
			if (($feature_obj2->primary_tag ne 'gene') and (($tag eq "locus_tag") or ($tag eq "codon_start") or ($tag eq "gene")))		
			{
				
			}
			elsif ($feature_obj2->primary_tag eq 'source')
			{

			}			
			elsif ($tag eq "protein_id")
			{
				push (@tbl_array, "\t\t\t".$tag."\t"."gnl|iah|$gb2_locus_tag\n");
			}			
			else
			{	
				            
				for my $value ($feature_obj2->get_tag_values($tag)) 
				{                
					push (@tbl_array, "\t\t\t".$tag."\t".$value."\n");           
				}
			}
		}
	
}

		
	}
}




