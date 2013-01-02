#!/usr/local/bin/perl -w

## This script extracts hypothetical proteins from a genbank/embl file and saves them as fasta ##

use strict;
use Bio::SeqIO;
use Bio::SeqFeature::Generic;

my $input_file = shift;
my $format = shift;
my $fasta_file = shift;

#open genome
my $seqio_object = Bio::SeqIO->new(-file => $input_file, -format => $format);
my $seqout = Bio::SeqIO->new(-file   => ">$fasta_file", -format => 'Fasta');




#create a new sequence object

my $seq_object = $seqio_object->next_seq;



my $gene_feat;
# my $product = ();
		  
foreach my $feature_obj ($seq_object->get_SeqFeatures)
{
	if ($feature_obj->has_tag("product"))
	{
		foreach my $product ($feature_obj->get_tag_values("product"))
		{
			my ($locus_tag) = $feature_obj->get_tag_values("locus_tag");
			#print $locus_tag;	
			if ((lc($product)) eq "hypothetical protein")
			{	
			
				my $fasta_obj = Bio::Seq->new(-seq => $feature_obj->seq->translate(-				codontable_id => 11)->seq, -display_id => $locus_tag, -alphabet => "protein" );
				$seqout->write_seq($fasta_obj);
	
				
			}



		}

	}
}






