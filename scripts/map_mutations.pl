#!/usr/bin/perl -w

####
# This script takes a base position (in this case of a mutation) and maps the corresponding gene to it.
#
# The input is a tab delimited table with positions and a genbank file.
#
####

use lib "/usr/local/BioPerl-1.6.901/";

use Bio::Perl;
use Bio::SeqFeature::Generic;
use strict;

my $genbank = shift;
my $output = shift;
my $data_file = shift;


# Open Files

my $seqio_object = Bio::SeqIO->new(-file => $genbank, -format => 'genbank');
my $seq_object = $seqio_object->next_seq;


my $start;
my $end = 0;
my $locus_tag1 = "origin";
my $locus_tag;
my @regions;

open (OUTFILE, ">$output");


#open the data file with base position in the first column, save into an array

open (DATA, $data_file) or die ("failed");
my @data_file = <DATA>;
close(DATA);


foreach my $feature ($seq_object->get_SeqFeatures())
{
	




	#if ($feature->primary_tag eq $feature1->primary_tag)
	if ($feature->primary_tag eq "gene")
	{	
		my @feat_ID = $feature->get_tag_values("locus_tag");
		$locus_tag = $feat_ID[0];
		
		$start = $feature->start;

		#grep the base positions in the intergenic region
		my @intergenics = grep {($_ > $end) && ($_ < $start)} @data_file;
		foreach my $data_line (@intergenics)
		{
			print OUTFILE "Intergenic:$locus_tag1-$locus_tag	$end	$start	$data_line";
		}
		#Also make array of intergenic regions
		$locus_tag1 = $feat_ID[0];
		#push into array of intergenic regions
	
		$end = $feature->end;
		my @gene = grep {($_ > $start) && ($_ < $end)} @data_file;
		foreach my $data_line (@gene)
		{
			print OUTFILE "$locus_tag	$start	$end	$data_line";
		}


	}

}
my $length = $seq_object->length;
print OUTFILE "Intergenic:$locus_tag - end	$end	$length";	



