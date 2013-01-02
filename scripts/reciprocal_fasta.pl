#!/usr/local/bin/perl -w

=head1 NAME

reciprocal_fasta - run reciprocal Fasta searches on translations of
CDS features in a pair of EMBL files

=head1 SYNOPSIS

Examples:

 reciprocal_fasta -t 11 embl_file_1 embl_file_2 > msp_crunch_file

An MSPCrunch-format file is written to STDOUT

=head1 DESCRIPTION

This program requires an EMBL file containing CDS features. The
features will be translated using the specified translation table
(default is table 11; bacterial). Each CDS from the first EMBL entry
will be searched against a database of sequences from the second. If
the top hit covers at least 80% of the length of both sequences with
at least 30% identity, a reciprocal Fasta search of the top hit
sequence will be lanuched against a database of CDS sequences from the
first enrty. If the reciprocal top hit is the same as the original
query CDS then an MSPcrunch format data line will be written to
STDOUT.

The combined MSPcrunch output may then be read into ACT along with the
two original EMBL entries.

=head1 METHODS

None

=head1 AUTHOR

Keith James (kdj@sanger.ac.uk)

=head1 COPYRIGHT

Copyright (C) 2002 Genome Research Limited. All Rights Reserved.

=head1 DISCLAIMER

This software is provided "as is" without warranty of any kind. It may
be used, redistributed and/or modified under the same conditions as
Perl itself.

=cut

use strict;

#use lib "/usr/local/apache2/htdocs/microb/anno_perl/perl/lib/perl5/site_perl/5.8.5";
#use lib "/usr/local/sanger_perl/perl/lib/perl5/site_perl/5.8.5";
use lib "/usr/local/bioinf/gb_pipeline";

use Getopt::Std;
use IO::File;
use Bio::PSU::SeqFactory;
use Bio::PSU::SearchFactory;
use Time::HiRes qw(usleep ualarm gettimeofday tv_interval);
#use Bio::PSU::Utils::FeatureIdHelper::BioPSUHelper;

#Beginning of script time recorded
my $t0 = [gettimeofday];
print "Start Time:$t0";


#my $id_helper =
  #Bio::PSU::Utils::FeatureIdHelper::BioPSUHelper->new();

# Identity cutoff for reciprocal searches
my $id_cutoff  = 0.3;
# Length of hit cutoff for reciprocal searches
my $len_cutoff = 0.8;


my $qdb_file = shift;
my $sdb_file = shift;
my $outputdir = shift;


# Configuration of Fasta variables
#my $bindir = "/usr/local/fasta3/bin";
#my $fasta_bin = "$bindir/fasta35_t";
my $fasta_bin = "fasta35_t";
my $fasta_args = "-z 11 -Q -H -S -m 10";

my $query_db_file   = "$outputdir/reciprocal_db.q";
my $subject_db_file = "$outputdir/reciprocal_db.s";

my (%opts, $colour, $prob, $table, $type);
my $usage = "Usage: reciprocal_fasta [-t <int>] <EMBL file> <EMBL file>

\t-t Translation table number (default 11)

\tTables available are:

\t 1) Standard
\t 2) Vertebrate Mitochondrial
\t 3) Yeast Mitochondrial
\t11) Bacterial
";

# Process options
getopts('ht:', \%opts);

defined $opts{h} and die $usage;
defined $opts{t} and $table = $opts{t};

# Default translation table is bacterial (11)
$table ||= '11';

# Read in two EMBL files




unless (defined $qdb_file and defined $sdb_file)
{
    die $usage
}

#my $qdbi = Bio::PSU::SeqFactory->make(-file   => "<$outputdir"."/$qdb_file.embl",
#                                      -format => 'embl');
#my $sdbi = Bio::PSU::SeqFactory->make(-file   => "<ref_genomes/$sdb_file.embl",
#                                      -format => 'embl');


####tradis orthologs
my $qdbi = Bio::PSU::SeqFactory->make(-file   => "<$qdb_file",
                                      -format => 'embl');
my $sdbi = Bio::PSU::SeqFactory->make(-file   => "<$sdb_file.embl",
                                      -format => 'embl');
open (FILE, ">>orthologs.txt");
my $pseudo;

my($COG_locus_tag,$COG_code, $kegg_locus_tag,$kegg_code);
my %COG_hash = ();
my %kegg_hash = ();

my @kegg_data;
if (-e "$sdb_file.list")
{
open (INFILE, "$sdb_file.list") or die ("couldn't open the file  $sdb_file.list");
@kegg_data = <INFILE>;
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

if (exists $kegg_hash{$kegg_locus_tag})
{
	$kegg_hash{$kegg_locus_tag} = $kegg_hash{$kegg_locus_tag}."\t".$kegg_code;
}
else
{
	$kegg_hash{$kegg_locus_tag} = $kegg_code;
}
}
}



}

my @COG_data;
if (-e "ref_genomes/$sdb_file.ptt")
{
open (INFILE, "ref_genomes/$sdb_file.ptt") or die ("couldn't open the file  $sdb_file.ptt");
@COG_data = <INFILE>;
close(INFILE);






#make COG into hash with locus tag as the key.
foreach my $COG_line (@COG_data){

if ($COG_line =~ m/\S+
	\t
	\S+
	\t
	\S+
	\t
	\S+
	\t
	\S+
	\t
	(\S+)	
	\t
	\S+
	\t
	(\S+)/ox        # COG number

	    )
	{
	    ($COG_locus_tag,$COG_code) = ($1,$2);
#print $COG_locus_tag." ".$COG_code;
$COG_hash{$COG_locus_tag} = $COG_code;
}
}



}





my $qdb = $qdbi->next_seq;
my $sdb = $sdbi->next_seq;



# Filter out all but CDS
print STDERR "Filtering out non-CDS features\n";
$sdb = filter_cds($sdb);
$qdb = filter_cds($qdb);


# Generate unique ids
print STDERR "Generating unique CDS identity codes\n";
$qdb = generate_cds_id($qdb, "query");
$sdb = generate_cds_id($sdb, "subject");

# Cache translations
print STDERR "Caching CDS translations for searching\n";
my $qtr = generate_translation_cache($qdb, $table);
my $str = generate_translation_cache($sdb, $table);

# Cache CDS features with ids
print STDERR "Caching CDS EMBL features for annotation output\n";
my $qfc = generate_feature_cache($qdb);
my $sfc = generate_feature_cache($sdb);
#my $ofc = generate_feature_cache($odb);

# Write query database sequences to file (for reciprocal search)
my $qdbo = Bio::PSU::SeqFactory->make(-file   => ">$query_db_file",
                                      -format => 'fasta');
foreach my $id (sort keys %$qtr)
{
    $qdbo->write_seq($qtr->{$id});
}

# Write subject database sequences to file (for initial search)
my $sdbo = Bio::PSU::SeqFactory->make(-file   => ">$subject_db_file",
                                      -format => 'fasta');
foreach my $id (sort keys %$str)
{
    $sdbo->write_seq($str->{$id});
}

# Search each query CDS against the subject CDS database
foreach my $q (sort keys %$qtr)
{
    my $query = $qtr->{$q};

    # Run Fasta search of query against subject database
    my $query_file = $q . ".q";
    print STDERR "Started $fasta_bin with query ", $q, "\n";
    my $search = run_fasta($query, $fasta_bin, $fasta_args,
                           $subject_db_file, $query_file);

    while (my $result = $search->next_result)
    {
        my @hits;

        while (my $hit = $result->next_hit)
        {
            push(@hits, $hit);
        }

        if (@hits)
        {
            # Sort the hits by score
            @hits = sort { $a->sw_score <=> $b->sw_score } @hits;

            my $top = pop @hits;

            # Discard hit below the thresholds
            next if ($top->percent < $id_cutoff * 100.0);
            my $pc_query   = $top->overlap / $top->q_len;
            my $pc_subject = $top->overlap / $top->s_len;

            next unless ($pc_query   >= $len_cutoff and
                         $pc_subject >= $len_cutoff);

            my $s       = $top->s_id;
            my $subject = $str->{$s};

	    my $perc_against_reference = $top->percent;
		
            # Run a reciprocal Fasta search using the top hit in the
            # subject database against the query database
            my $subject_file = $subject->id . ".s";
            print STDERR "Started reciprocal search with query ", $subject->id, "\n";
            my $recip = run_fasta($subject, $fasta_bin, $fasta_args,
                                  $query_db_file, $subject_file);

            my @recip_hits;

            my $recip_result = $recip->next_result;
            while (my $recip_hit = $recip_result->next_hit)
            {
                push(@recip_hits, $recip_hit)
            }

            if (@recip_hits)
            {
                # Sort the reciprocal hits by score
                @recip_hits = sort { $a->sw_score <=> $b->sw_score } @recip_hits;

                my $recip_top = pop @recip_hits;

                my $rq = $recip_top->q_id;
                my $rs = $recip_top->s_id;

                if ($rs eq $q)
                {

                    my $query_feature   = $qfc->{$q};
                    my $subject_feature = $sfc->{$s};
		    my $perc_against_query = $recip_top->percent;
		    

                    	transfer_qualifiers($query_feature,
                                        $subject_feature, $perc_against_reference, $perc_against_query);

		
		   
		    
                    print_mspcruch_format($query_feature,
                                          $subject_feature,
                                          $recip_top);
                }
            }

            unlink("$outputdir/$subject_file");
        }
    }
    unlink("$outputdir/$query_file");
}


#### traids close ortholog file
close (FILE);



my $leftovers = filter_unann($qdb);


my $unanno = Bio::PSU::SeqFactory->make(-file   => ">$outputdir"."/leftover_$qdb_file.embl",
                                   -format => 'embl');


$unanno->write_seq($leftovers);





$qdbo = Bio::PSU::SeqFactory->make(-file   => ">$outputdir"."/$qdb_file.mod",
                                   -format => 'embl');
#$sdbo = Bio::PSU::SeqFactory->make(-file   => ">$sdb_file.mod",
#                                   -format => 'embl');

print STDERR "Writing modified EMBL files\n";
$qdbo->write_seq($qdb);
#$sdbo->write_seq($sdb);

print STDERR "Cleaning up temporary files\n";
unlink $query_db_file;
unlink $subject_db_file;

#Gets finishing time and time elapsed.
my $t1 = [gettimeofday];
print "Finishing Time:$t1";
my $t0_t1 = tv_interval $t0, $t1;
print "Duration:$t0_t1"; 




=head2

 Title   : filter_cds
 Usage   : filter_cds($seq_object)
 Function: Filters out all features except CDS features
 Returns : Bio::PSU::Seq object
 Args    : Bio::PSU::Seq object

=cut

sub filter_cds
{
    my $e = shift;

    my @cds;

    foreach my $f ($e->features)
    {
        # Discard non-CDS features
	####normal recip 
        next unless $f->key eq 'CDS' or $f->qexists('pseudo');
	
	

	##### recip for  tradis comp
	#next unless ($f->key eq 'CDS');
        push(@cds, $f);
    }

    return $e->clone(-features => \@cds);
}

=head2

 Title   : generate_cds_id
 Usage   : generate_cds_id($seq_object, $prefix)
 Function: Adds a cds_id qualifier to each feature consisting of
         : a scalar prefix and a unique integer
 Returns : Bio::PSU::Seq object
 Args    : Bio::PSU::Seq object, scalar prefix

=cut

sub generate_cds_id
{
    my ($e, $prefix) = @_;

    my $i = 0;

    foreach my $cds ($e->features)
    {
        my $cds_id;

        my $gene = $cds->gene();

  
            $cds_id = $prefix . "_" . $i;
        if ($cds->qexists("cds_id"))
	{}
	else
	{
        $cds->qadd("cds_id", $cds_id);
        ###!!!this is where to add remove any features that are present in all CDS!!!###
	$cds->qadd('inference', "ab initio prediction: Glimmer3.0");
	$cds->qremove('method', "Glimmer");
	}
	$i++;
    }

    return $e;
}

=head2

 Title   : generate_translation_cache
 Usage   : generate_translation_cache($seq_object, $translation_table)
 Function: Creates a hash of Bio::PSU::Seq objects which are translations
         : of the features in the Bio::PSU::Seq object supplied. The hash
         : is keyed on the content of the cds_id qualifier, so the
         : generate_cds_id function must be called first
 Returns : Hash reference
 Args    : Bio::PSU::Seq object, translation table identifier

=cut

sub generate_translation_cache
{
    my ($e, $table) = @_;

    my %translations;

    foreach my $cds ($e->features)
    {
        my $id = $cds->cds_id;
	#print "!!!!!!!!!!!!$id";
        my $aa = $cds->translate($table);
        $aa->id($id);
        $translations{$id} = $aa;
    }

    return \%translations;
}

=head2

 Title   : generate_feature_cache
 Usage   : generate_feature_cache($seq_object)
 Function: Creates a hash of Bio::PSU::Feature objects. The hash
         : is keyed on the content of the cds_id qualifier, so the
         : generate_cds_id function must be called first
 Returns : Hash reference
 Args    : Bio::PSU::Seq object

=cut

sub generate_feature_cache
{
    my $e = shift;

    my %features;

    foreach my $cds ($e->features)
    {
        my $id = $cds->cds_id;
	#print "!!!!!!!$id!";
        $features{$id} = $cds;
    }

    return \%features;
}

=head2

 Title   : run_fasta
 Usage   : my $search = run_fasta($cds, $exec, $opts, $db, $temp_file)
 Function: Creates a new Bio::PSU::IO::Fasta::Search object
 Returns : Bio::PSU::IO::Fasta::Search
 Args    : Bio::PSU::Feature object, fasta executable, fasta options,
         : protein database to search, temp file to write CDS

=cut

sub run_fasta
{
    my ($cds, $exec, $opts, $db, $query) = @_;

    # Create a stream to write the temp file containing the query CDS
    my $out = Bio::PSU::SeqFactory->make(-file   => ">$outputdir/$query",
                                         -format => 'fasta');
    $out->write_seq($cds);

    my $out_file = "fasta_${query}.$$.out";

    my $exec_string = "$exec $opts $outputdir/$query $db > $outputdir/$out_file";

    warn "starting: $exec_string\n";

    if (system $exec_string) {
      die "Unable to run $exec: $!\n";
    }

    # Return Fasta search object
    my $search = Bio::PSU::SearchFactory->make(-file    => "$outputdir/$out_file",
                                               -program => 'fasta');

    unlink ("$outputdir/$out_file");

    return $search;
}

=head2

 Title   : print_mspcruch_format
 Usage   : print_mspcruch_format($q_feature, $s_feature, $fasta_hit)
 Function: Writes an MSPCrunch format description of the hit
 Returns : Nothing
 Args    : Bio::PSU::Feature object, Bio::PSU::Feature object,
         : Bio::PSU::IO::Fasta::Hit object

=cut

sub print_mspcruch_format
{
    my ($q, $s, $rh) = @_;

    my $q_name = $q->gene;
    my $s_name = $s->gene;

   

    # If the query/subject originated on opposite strands, this should
    # be reflected in the MSPCrunch formatting.
    my ($q_start, $q_end, $s_start, $s_end);

    if ($q->strand == -1)
    {
        $q_start = $q->end;
        $q_end   = $q->start;
    }
    else
    {
        $q_start = $q->start;
        $q_end   = $q->end;
    }

    if ($s->strand == -1)
    {
        $s_start = $s->end;
        $s_end   = $s->start;
    }
    else
    {
        $s_start = $s->start;
        $s_end   = $s->end;
    }

    # It's a reciprocal hit, so the fallback for query name is the
    # subject id of the hit and vice versa
    print join(" ", ($rh->sw_score, $rh->percent,
                     $q_start, $q_end,
                     defined $q_name ? $q_name: $rh->s_id,
                     $s_start, $s_end,
                     defined $s_name ? $s_name: $rh->q_id)), "\n";
}




sub transfer_qualifiers
{
    my ($q, $s, $ref_perc, $query_perc) = @_;

    my @q_genes = $q->systematic_id;
    my @q_sys =  grep /^sys_/i, @q_genes;
    #print $q_sys[0];
    
   ###!!!Changed to locus_tag - the reference sequence doesn't have a systematic_id!!!###
    my @s_genes = $s->locus_tag;
    #my @s_sys =  grep /^SeD_A\d*/i, @s_genes;
    #print "***$s_sys[0]***";
    
    #my @s_product = $s->product;
    my @s_ec      = $s->EC_number;
    my $s_prot_id = $s->protein_id;
    #my @s_colour  = $s->colour;
    #my @s_class   = $s->class;
    my $orthologue = $s->locus_tag;
	my $old_orthologue = $s->old_locus_tag;    
	print "!!!***$orthologue***!!!";
    #$q->qadd('APEC01_orthologue', shift @s_sys);
    #$s->qadd('SeD_A_orthologue',  shift @q_sys);
    #$q->qadd('per_id', $query_perc);
    #$s->qadd('per_id', $ref_perc);
    #$q->qadd('product',   @s_product);
    $q->qadd('EC_number', @s_ec);
    ###!!!removes the original colour if there was one already recorded.!!!###
    $q->qremove('colour');
    #$q->qadd('class',     @s_class);
	    


#make a table of orthologs.
my $locus_tag = $q->locus_tag;

####Tradis
if ($s->qexists('pseudo'))
{
	$pseudo = "yes";
	#print "\nPSEUDO".$f->locus_tag."!!!!!$pseudo\n";
}
else
{
	$pseudo = "no";
	#print "\nPSEUDO!!!!!$pseudo\n";
}
#print FILE "$orthologue	$locus_tag	$pseudo\n";







	$q->qadd('product', $s->product);
	$q->qadd('gene', $s->gene);
	$q->qadd('inference', "similar to AA sequence: $orthologue");
	
	#add kegg matches from ortholog
	print $kegg_hash{$orthologue};
	if (exists $kegg_hash{$orthologue})
	{
		
		my $kegg_match = $kegg_hash{$orthologue};
		print "!!!!!$kegg_match!!!!!";		
		print FILE "$locus_tag	$orthologue	$kegg_match\n";
		$q->qadd('db_xref', "Kegg ortholog: $kegg_match");
	}	
	elsif
	{
		my $kegg_match = $kegg_hash{$orthologue};
		print "!!!!!$kegg_match!!!!!";		
		print FILE "$locus_tag	$orthologue	$kegg_match\n";
		$q->qadd('db_xref', "Kegg ortholog: $kegg_match");
	}	
	else
	{	
		print FILE "$locus_tag	$orthologue	na\n";
	}

		if (exists $COG_hash{$orthologue})
	{
		
		my $COG_match = $COG_hash{$orthologue};
		print "!!!!!$COG_match!!!!!";		

		$q->qadd('db_xref', "COG group: $COG_match");
	}

	print $kegg_hash{$old_orthologue};
	if (exists $kegg_hash{$old_orthologue})
	{
		
		my $kegg_match = $kegg_hash{$old_orthologue};
		print "!!!!!$kegg_match!!!!!";		

		$q->qadd('db_xref', "Kegg ortholog: $kegg_match");
	}	

		if (exists $COG_hash{$old_orthologue})
	{
		
		my $COG_match = $COG_hash{$old_orthologue};
		print "!!!!!$COG_match!!!!!";		

		$q->qadd('db_xref', "COG group: $COG_match");
	}	


	$q->qadd('biological_process', $s->biological_process);
	$q->qadd('function', $s->function);
	
	#$q->qadd('colour', 2);
	if ($s->qexists('pseudo')){
	$q->qremove('colour');
	#$q->qadd('colour', 8);
	my $pseudo = ();
	$q->qadd('note', $s->note);
	$q->qadd('pseudo', $pseudo);
	#sets the key to gene rather than CDS
	$q->key('gene');
	$q->qadd('note', "Percentage similarity is $query_perc");
	print $orthologue;
	}
	else
	{
		#pseudo genes do not have a protein id.
	$q->qadd('note', "Orthologous protein id is $s_prot_id, the percentage similarity is $query_perc");	
	}



}


=head2

 Title   : filter_unann
 Usage   : filter_unann($seq_object)
 Function: Filters out all unannotated features
 Returns : Bio::PSU::Seq object
 Args    : Bio::PSU::Seq object

=cut

sub filter_unann
{
    my $e = shift;

    my @unann;

    foreach my $f ($e->features)
    {
        # Discard non-CDS features
        next if ($f->qexists('product'));
	next if ($f->qexists('pseudo'));
	$f->qremove('cds_id');
	$f->qremove('inference');
        push(@unann, $f);

	
    }

    return $e->clone(-features => \@unann);
}
