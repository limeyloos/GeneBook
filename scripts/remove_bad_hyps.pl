#!/usr/local/bin/perl -w

## This script removes any hypothetical proteins which overlap/are contained in another protein and are below a certain size. Those which are bigger need to be checked manually ##

use strict;
use Bio::SearchIO; 
use Bio::SeqIO;


my $list = shift;
my $gb_file = shift;
my $outputs = shift;
my $genome_desc = shift;
my $i = 0;
my $j = 0;


#open the list of dodgy CDSs into a hash where the key is the locus tag and the value is the product only include into the list if hypothetical protein/putative uncharacterized protein or conserved hypothetical protein
open (INFILE, $list) or die ("couldn't open $list");;
my @lines = <INFILE>;
close (INFILE);

#hash of locus tags and products
my %check_hyp = ();

#type of discrepancy
my $discrep_type = "";

foreach my $line (@lines)
{
	my @split;
	my $product;
	my $locus_tag;
	my @protein_range;
	my $size;	
	my $protein_size;	

	

	#print $line;
	if ($discrep_type ne "" and $line ne "\n")
	{
	#print $line;
	#print $discrep_type;	
	@split = split(/\t/, $line);
	$product = $split[1];
	$locus_tag = $split[3];
	chomp ($locus_tag);
	@protein_range = split(/\D+/, $split[2]);
	$size = @protein_range;	
	$protein_size = (($protein_range[$size-1])-($protein_range[$size-2]));
	}	
	#print $protein_size."\n";
	#look for certain discrepancies
	if(lc($line) =~ m/^(DiscRep_ALL:OVERLAPPING_CDS)/i)
	{
		$discrep_type = "Overlapping_CDS";
		
	}	
	elsif(lc($line) =~ m/^(DiscRep_ALL:CONTAINED_CDS)/i)
	{
		$discrep_type = "Contained_CDS";
	}
	elsif(lc($line) =~ m/^(DiscRep_ALL:RNA_CDS_OVERLAP)/i)
	{
		$discrep_type = "RNA_CDS_Overlap";
	}
	elsif($line eq "\n")
	{
		$discrep_type = "";
	}
		




	if(($discrep_type eq "Contained_CDS") and (lc($product) =~ m/^(hypothetical protein|conserved hypothetical protein|putative uncharacterized protein)/i))
	{
		
		unless ($protein_size>250 and lc($product)=~ m/^putative uncharacterized protein/i)		
		{
			print $line." contained\n";				
			$check_hyp{$locus_tag} = $product;
				
		}				

	}


	if(($discrep_type eq "Overlapping_CDS") )
	{
		
		if(lc($product) =~m/^(hypothetical protein|conserved hypothetical protein|putative uncharacterized protein)/i) 
		{	
			if ($protein_size<=250)		
			{
				print $line." overlapping and small\n";				
				$check_hyp{$locus_tag} = $product;
				
			}
			else
			{	
				$check_hyp{$locus_tag} = "note";
			}

		}	
		else

		{
			print $line." overlapping\n";
			$check_hyp{$locus_tag} = "note";
			
		}	

	}
	
	
	


	if(($discrep_type eq "RNA_CDS_Overlap") and (lc($product) =~ m/^(hypothetical protein|conserved hypothetical protein|putative uncharacterized protein)/i))
	{
		#print $product;		
		$check_hyp{$locus_tag} = $product;
		#print $check_hyp{$locus_tag};
	}

} 


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

print $i;














=head2

 Title   : update_genbank
 Usage   : update_genbank()
 Function: Adds the new annotation to the genbank file.
 Returns : Returns an updated genbank file.
 Args    : Bio::Seq::IO object

=cut

sub update_genbank
{
	
	#feature_object array
	my @feature_obj_array = ();
	foreach my $feature_obj ($seq_object->get_SeqFeatures)
	{
		my %unique_notes;
					if ($feature_obj->has_tag('note'))	
					{				
						my @notes = $feature_obj->get_tag_values("note");
												
						$feature_obj->remove_tag('note');						
						foreach my $note (@notes)
						{
							#print "$note";
							# get unique notes, remove tag and then add again - eliminated repeated notes.
							$unique_notes{$note} = $note;	
	
							
							

						}
						foreach my $key ( keys %unique_notes) 
							{ 
								#print $key,"\n";								
								$feature_obj->add_tag_value('note', $unique_notes{$key});					
							}
					}		
		
		
		my $gb_locus_tag;		
		#check locus_tag against hash
		if ($feature_obj->has_tag("locus_tag"))
		{
			($gb_locus_tag) = $feature_obj->get_tag_values("locus_tag");

			if ($feature_obj->has_tag("product"))
			{
				my ($product) = $feature_obj->get_tag_values("product"); 			
				if (lc($product) =~ m/^(hypothetical protein|conserved hypothetical protein|putative uncharacterized protein)/i)			
				{
					$feature_obj->remove_tag('product');

					#replace the product tag with the new description
					$feature_obj->add_tag_value('product','hypothetical protein');	
				}
			}
			#print $gb_locus_tag;			
			if (defined($check_hyp{ $gb_locus_tag }))
			{			
									
				if ($check_hyp{ $gb_locus_tag } ne "note")				
				{	
				
					$i++;	
					print "\n $gb_locus_tag";
				
					if ($feature_obj->has_tag('gene'))	
					{
						push (@feature_obj_array, $feature_obj);
					}

					
				}


					
				#$seq_object->remove_SeqFeature($feature_obj);
				
				elsif ($check_hyp{ $gb_locus_tag } eq "note")
				{		
					if ($feature_obj->primary_tag eq 'CDS')
					{					
						$feature_obj->add_tag_value('note',"overlaps CDS with the same product name");
						push (@feature_obj_array, $feature_obj);	
					}
					else
					{
						push (@feature_obj_array, $feature_obj);
					}
					
				}
			}
			else
			{
				#push the feature into an array
				push (@feature_obj_array, $feature_obj);
			}
		
		
		}
		else
		{
			push (@feature_obj_array, $feature_obj);
		}
	}
	
	#remove all features
	$seq_object->flush_SeqFeatures();


	#add features without the excluded features from above
	$seq_object->add_SeqFeature(@feature_obj_array);


	foreach my $feature_obj2 ($seq_object->get_SeqFeatures)
	{
	

		my $gb2_locus_tag;



		#for each feature add a .tbl entry excpet for source which is added in Sequin.
		if ($feature_obj2->primary_tag ne 'source')
		{
						
		($gb2_locus_tag) = $feature_obj2->get_tag_values("locus_tag");			


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


