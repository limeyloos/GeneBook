#!/usr/local/bin/perl -w

## This script takes a list of 'bad annotations' from extracted from the GenBank discrepancy file and gets the FASTA sequences for these genes ##

use strict;
use Bio::SeqIO;
use Bio::SeqFeature::Generic;

my $input_file = shift;
my $format = shift;
my $fasta_file = shift;
my $bad_anno = shift;

#open genome
my $seqio_object = Bio::SeqIO->new(-file => $input_file, -format => $format);
my $seqout = Bio::SeqIO->new(-file   => ">$fasta_file", -format => 'Fasta');

my %bad_annos;
#open bad_anno file
open (INFILE, $bad_anno) or die ("couldn't open $bad_anno");;
my @lines = <INFILE>;
close (INFILE);
foreach my $locus_tag (@lines) {
chomp($locus_tag);
$bad_annos{$locus_tag} = "$locus_tag";
print $bad_annos{$locus_tag};
}



#create a new sequence object

my $seq_object = $seqio_object->next_seq;


my $i = 0;

		  
foreach my $feature_obj ($seq_object->get_SeqFeatures)
{
	
	if ($feature_obj->has_tag("locus_tag") and $feature_obj->primary_tag eq "gene")
	{
		
		my ($locus_tag) = $feature_obj->get_tag_values("locus_tag");	
		
		if (defined($bad_annos{"$locus_tag"}) and $bad_annos{"$locus_tag"} eq "$locus_tag")
		{
			
			$i++;			
			$bad_annos{"$locus_tag\n"} = "";			
			#print $feature_obj->primary_tag;	
			my $fasta_obj = Bio::Seq->new(-seq => $feature_obj->seq->translate(-codontable_id => 11)->seq, -display_id => $locus_tag, -alphabet => "protein" );
			$seqout->write_seq($fasta_obj);
		}

	}
}


print $i;



