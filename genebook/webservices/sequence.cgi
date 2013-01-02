#!/usr/bin/perl -w
### This CGI gets a region of sequence from the genome based on how far up and down stream the user wants to see

print "Content-type: text/html\n\n";

use CGI;
use CGI::Carp qw(warningsToBrowser fatalsToBrowser); 
use lib "/usr/local/BioPerl-1.6.901/";
use Bio::Perl;
use Mysql;
use lib "/var/www/tools/";
use strict;
use Bio::DB::Fasta;
use Bio::SeqIO;
use Text::Wrap;

#get the paths from the parameters file.
use parameters;
my $query = new CGI;
my $gene = $query->param("gene");
my $genome_id = $query->param("genome");
#the number of base pairs up and down stream of the central gene
my $padding = $query->param("padding");
my $type = $query->param("type");


# MySQL QUERY to get the genes up and downstream of the gene in question (based on the buffer)
my $myquery = "Select Strand_Dir, (Locus_Start) as genestart, (Locus_End) as geneend, Genome_ID from gene where locus_tag = '$gene'";

my $connect = Mysql->connect($dbhost, $dbname, $dbuser, $dbpass);

# EXECUTE THE QUERY
my $execute = $connect->query($myquery);
my $results_gene;
my $b ;
my $a;
my $genome;

while (my @results = $execute->fetchrow())
{
$a = $results[0];

$b = $results[1];
$b = $b - $padding ;
if ($b < 0)
{
	$b = $results[1];
}
$results_gene = $results[2] + $padding;
$genome = $results[3];
#print "<p>".$gene." ".$a." X ".$b." x ".$results_gene;
}


 use Data::Dumper;

my $seqio_obj = Bio::SeqIO->new(-file => '/var/www/html/tmp/'.$genome.'_sequence.fasta', -format=>'fasta');
my $seq_obj = $seqio_obj->next_seq;
my $sub_seq = $seq_obj->subseq($b,$results_gene);
$sub_seq = Bio::Seq->new(-seq => $sub_seq, -alphabet => 'dna', -strand=>$a);
my $revcom = $sub_seq->revcom;
my $translation = $sub_seq->translate();
my $rev_trans = $revcom->translate();

if ($a == -1)
{

	if ($type eq "aa")
	{
		print $rev_trans->seq;
	}
	elsif ($type eq "dna")
	{
		print $revcom->seq;
	}
	else
	{
		print $rev_trans->seq;
	}

}
else
{

	if ($type eq "aa")
	{
		print $translation->seq;
	}
	elsif ($type eq"dna")
	{
		print $sub_seq->seq;
	}
	else
	{
		print $translation->seq;
	}

}


