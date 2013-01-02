#! /usr/local/bin/perl -w

## This takes the predicted genes that didn't have an ortholog in the reciprocal fasta and extracts them into a separate file for BLAST comparison ##

use strict;

#use lib "/usr/local/apache2/htdocs/microb/anno_perl/perl/lib/perl5/site_perl/5.8.5";
use lib "/usr/local/bioinf/gb_pipeline";

#use Getopt::Std;
#use IO::File;
use Bio::PSU::SeqFactory;
use Bio::PSU::SearchFactory;
use Time::HiRes qw(usleep ualarm gettimeofday tv_interval);

print "Started converting embl leftovers to FASTA";

#Beginning of script time recorded
my $t0 = [gettimeofday];
print "Start Time:", @$t0, "\n";

my $leftover_file = shift;
my $outputdir = shift;
#my $anno_seq = shift;

open (OUTFILE, ">$outputdir"."/$leftover_file.fasta");

##open the embl file with leftovers, extract sequence and place into an array.
my $leftover_i = Bio::PSU::SeqFactory->make(-file   => "<$outputdir"."/$leftover_file.embl",
                                     -format => 'embl');

#make an array and add each entry into the array preceded by a > line...
#
my @leftover_seqs;

my $leftover_db = $leftover_i->next_seq;				  
foreach my $leftover_feature ($leftover_db->features)
{
	
	if ($leftover_feature->key eq 'gene')
	{
	my $seq_desc = ">".$leftover_feature->locus_tag."\n";
		
	#push (@leftover_seqs, $seq_desc);
	#push (@leftover_seqs, $leftover_feature->str."\n");

	#accounts for the gaps in the gallinarum 247 annotation (joins)
	my $start = $leftover_feature->start;
	#print "\t";
	my $end = $leftover_feature->end;
	#print "\n";
	my $strand = $leftover_feature->strand;
	my $no_joins_feature = Bio::PSU::Feature->new(-key    => 'gene',
                                      -start  => $start,
                                      -end    => $end,
                                      -strand => $strand);

	 $leftover_db->features($no_joins_feature);
	

	my $seq_desc2 = ">".$leftover_feature->locus_tag."\n";
	push (@leftover_seqs, $seq_desc2);
	push (@leftover_seqs, $no_joins_feature->str."\n");
	#print $no_joins_feature->str;
	# once array gets to a certain size send off to netblast
	}
}


##Open the annotated embl file, take the files annotated as hypothetical and add to fasta file.
#my $annotated_seq = Bio::PSU::SeqFactory->make(-file   => "<$outputdir"."/$anno_seq.mod",
#                                     -format => 'embl');


#my $anno_seqs = $annotated_seq->next_seq;				  
#foreach my $annotated_feature ($anno_seqs->features)
#{
	#print $annotated_feature->product();
#	if ((lc($annotated_feature->product()) eq "hypothetical protein") or (lc($annotated_feature->product()) eq "conserved hypothetical protein"))
#	{
#		print "hit";
#		print $annotated_feature->systematic_id;
#		my $seq_desc = ">".$annotated_feature->systematic_id."\n";
#	push (@leftover_seqs, $seq_desc);
#	push (@leftover_seqs, $annotated_feature->str."\n");
#	# once array gets to a certain size send off to netblast
#	
#	remove the 
##	
#	}
#}




foreach my $lft (@leftover_seqs)
{
	print (OUTFILE $lft);
}

print "Finished converting to FASTA";
