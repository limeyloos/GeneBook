#!/usr/local/bin/perl -w

## Extracts the pseudogenes from the annotation ##

use strict;
use Bio::SeqIO;
use Bio::SeqFeature::Generic;

my $input_file = shift;
my $format = shift;
my $fasta_file = shift;
# my $pseudo_file = shift;

#open genome
my $seqio_object = Bio::SeqIO->new(-file => $input_file, -format => $format);
my $seqout = Bio::SeqIO->new(-file   => ">$fasta_file", -format => 'embl');

#open (FILE, ">$pseudo_file");


#create a new sequence object

my $seq_object = $seqio_object->next_seq;

my $embl_seqobject = Bio::Seq->new(-seq => $seq_object->seq);

my $gene_feat;
# my $product = ();

my $locus_tag;
		  
foreach my $feature_obj ($seq_object->get_SeqFeatures)
{
	
		
	if ($feature_obj->has_tag("pseudo"))
	{
			
		$embl_seqobject->add_SeqFeature($feature_obj);
		#print FILE "$locus_tag	yes\n";
				
	}
	else
	{
		#print FILE "$locus_tag	no\n";
	}
	#}


}

#close FILE;
$seqout->write_seq($embl_seqobject);



