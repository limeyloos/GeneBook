#!/usr/bin/perl -w

####
# This script takes a tab delimited file of tradis data and sorts the significantly attenuated genes into hashes for each organism.
####

##
# p-value below 0.05 and negative fitness score
##

use strict;

# make animal hashes (chick, calf, pig) which will contain STMs which match above criteria

my %STM_chick = ();
my %STM_calf = ();
my %STM_pig = ();

# make animal hashes of those which do not meet the criteria, fo rthe population of the enrichment analysis

my %STM_pop = ();


my $name = shift;
# open tradis_plus_STM.txt and save into array
my $file = shift;

open (FILE, $file);
my @tradis_data = <FILE>;
close(FILE);

# Go through each line and assign variables
my @split_line;
my $locus_tag;
my $chick_f;
my $chick_p;
my $calf_f;
my $calf_p;
my $pig_f;
my $pig_p;


foreach my $tradis_data (@tradis_data) 
{

	@split_line = split(/\t/, $tradis_data);
	$locus_tag = $split_line[0];
	
	$chick_f = $split_line[4];
	$chick_p = $split_line[5];
	$calf_f = $split_line[6];
	$calf_p = $split_line[7];
	$pig_f = $split_line[8];
	$pig_p = $split_line[9];

	#Make up an array for the population of genes/pathway in the enrichment analysis
	$STM_pop{$locus_tag} = $locus_tag;


	#$pig_f = -2;
	#$pig_p = 0.05;

	# If the FC is -ve and p-value is below 0.05 then put STM name into hash for each animal
	
	#ignores the null values
	if (($chick_f ne "-") and ($chick_p ne "-"))
	{
		if (($chick_f <= -2) and ($chick_p <= 0.05))
		{
			#print $locus_tag;
			$STM_chick{$locus_tag} = $locus_tag;
		}

	}

	if (($calf_f ne "-") and ($calf_p ne "-"))
	{
		if (($calf_f <= -3) and ($calf_p <= 0.05))
		{
			#print $locus_tag;
			$STM_calf{$locus_tag} = $locus_tag;
		}

	}
	if (($pig_f ne "-") and ($pig_p ne "-"))
	{
		if (($pig_f <= -3) and ($pig_p <= 0.05))
		{
			#print $locus_tag;
			$STM_pig{$locus_tag} = $locus_tag;
		}
	}
}

# Count number of genes sig for each species 
print "number of significant chick genes:  " . keys( %STM_chick ) . ".\n";
print "number of significant calf genes:  " . keys( %STM_calf ) . ".\n";
print "number of significant pig genes:  " . keys( %STM_pig ) . ".\n";

print "number of non-significant population genes:  " . keys( %STM_pop ) . ".\n";


my %path_descs;
my $genome_file = shift;
# Open stm_pathway.list
open (FILE, $genome_file);
my @stm_pathways = <FILE>;
close(FILE);

		

my $pathway_file = shift;
# Open pathway.list
open (FILE, $pathway_file);
my @pathway_desc = <FILE>;
close(FILE);

foreach my $pathway_desc (@pathway_desc) 
{
if ($pathway_desc =~ m/(\w+)\t(.+)/)
	{
		$path_descs{$1} = $2;
		
	}
}

# array of pathway descriptions
my $h = 0;
my $i = 0;
my $j = 0;
my $k = 0;
my $path;
my %path_locus_chick;
my %path_locus_calf;
my %path_locus_pig;
my %path_locus_pop;

foreach my $stm_pathways (@stm_pathways) 
{
# for each stm_pathway.list line if STM number is in hash then add to pathway hash	
#print $stm_pathways;	
if ($stm_pathways =~ m/$name:(\w+)[\t|\.S\t]path:$name(\w+)/)
	{
		$path = $2;

		#if $1 (locus_tag) matches a key in population hash then add to path_locus hash
		if (exists $STM_pop{$1})
		{

			if (exists $path_locus_pop{$path}[0])
			{
				push @{ $path_locus_pop{$path} }, $1;
				#print $path." ".$1." \n";
			}
			else
			{
				$path_locus_pop{$path} = [$1];
				#print $path." ".$1." \n";;
			}
			$h++;
			
		}


		#if $1 (locus_tag) matches a key in stm_animal hash then add to path_locus hash
		if (exists $STM_chick{$1})
		{

			if (exists $path_locus_chick{$path}[0])
			{
				push @{ $path_locus_chick{$path} }, $1;
				#print $path." ".$1." \n";
			}
			else
			{
				$path_locus_chick{$path} = [$1];
				#print $path." ".$1." \n";;
			}
			$i++;
			
		}
		
		if (exists $STM_calf{$1})
		{
			if (exists $path_locus_calf{$path}[0])
			{
				push @{ $path_locus_calf{$path} }, $1;
				#print $path." ".$1." ";
			}
			else
			{
				$path_locus_calf{$path} = [$1];
				#print $path." ".$1." ";
			}
			$j++;
		}
		
				if (exists $STM_pig{$1})
		{
			if (exists $path_locus_pig{$path}[0])
			{
				push @{ $path_locus_pig{$path} }, $1;
				#print $path." ".$1." ";
			}
			else
			{
				$path_locus_pig{$path} = [$1];
				#print $path." ".$1." ";
			}
			$k++;
		}

		
		
		
		
		#print $stm_pathways;
		#print "\n$1\n";
		#print $1." ".$2;
	}



# make hash of pathway descriptions

# make tab delimited file from both pathway hashes
}

print "chick paths mapped $i\n";
print "calf paths mapped $j\n";
print "pig paths mapped $k\n";
print "population paths mapped $h\n";

open (FILE, ">".$name."_total_tradis_pathways_log.txt");

my $number_of_paths;
#print "size of hash:  " . keys( %path_locus_chick ) . ".\n";
for my $pathway ( keys %path_descs )
{
	#print $path_descs{$pathway};
	
	print FILE "$pathway\t $path_descs{$pathway}\t";
	if (exists $path_locus_chick{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_chick{$pathway}};
	print FILE "$number_of_paths\t"; 
	}
	else 
	{
		print FILE "0\t";	
	}
	
	if (exists $path_locus_calf{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_calf{$pathway}};
	print FILE "$number_of_paths\t"; 
	}
	else 
	{
		print FILE "0\t";	
	}
	
	if (exists $path_locus_pig{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_pig{$pathway}};
	print FILE "$number_of_paths\t"; 
	}
	else 
	{
		print FILE "0\t";	
	}
	
	if (exists $path_locus_pop{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_pop{$pathway}};
	print FILE "$number_of_paths\n"; 
	}
	else 
	{
		print FILE "0\n";	
	}
}

close (FILE);


open (FILE, ">".$name."_total_tradis_pathway_names.txt");

my $l = 0;
my $m = 0;
my $n = 0;
my $o = 0;
#print "size of hash:  " . keys( %path_locus_chick ) . ".\n";
for my $pathway ( keys %path_descs )
{
	#print $path_descs{$pathway};
	
	
	#print $pathway;
	if (exists $path_locus_chick{$pathway}[0])
	{
	
	$number_of_paths = @{ $path_locus_chick{$pathway}};
	
	print FILE "chick\t$pathway\t $path_descs{$pathway}\t";
	print FILE "@{$path_locus_chick{$pathway}}\n";
	#$l++;
	
	}
	
	if (exists $path_locus_calf{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_calf{$pathway}};
	print FILE "calf\t$pathway\t $path_descs{$pathway}\t";
	print FILE "@{$path_locus_calf{$pathway}}\n";
	#$m++;
	}

	
	if (exists $path_locus_pig{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_pig{$pathway}};
	

	print FILE "pig\t$pathway\t $path_descs{$pathway}\t";
	print FILE "@{$path_locus_pig{$pathway}}\n";
	#$n++;

	}

	if (exists $path_locus_pop{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_pop{$pathway}};
	

	print FILE "population\t$pathway\t $path_descs{$pathway}\t";
	print FILE "@{$path_locus_pop{$pathway}}\n";
	#$o++;

	}

}

close (FILE);

open (FILE, ">".$name."_total_tradis_pathway_indnames.txt");


#print "size of hash:  " . keys( %path_locus_chick ) . ".\n";
for my $pathway ( keys %path_descs )
{
	#print $path_descs{$pathway};
	
	
	#print $pathway;
	if (exists $path_locus_chick{$pathway}[0])
	{
	$number_of_paths = @{ $path_locus_chick{$pathway}};
	foreach $locus_tag (@{$path_locus_chick{$pathway}})
	{
	print FILE "chick\t$pathway\t";
	print FILE "$locus_tag\n";
	$l++;
	}
	}
	
	if (exists $path_locus_calf{$pathway}[0])
	{
	foreach $locus_tag (@{$path_locus_calf{$pathway}})
	{
	print FILE "calf\t$pathway\t";
	print FILE "$locus_tag\n";
	$m++;
	}
	}

	
	if (exists $path_locus_pig{$pathway}[0])
	{
	foreach $locus_tag (@{$path_locus_pig{$pathway}})
	{
	print FILE "pig\t$pathway\t";
	print FILE "$locus_tag\n";
	$n++;
	}
	}

	if (exists $path_locus_pop{$pathway}[0])
	{
	foreach $locus_tag (@{$path_locus_pop{$pathway}})
	{
	print FILE "population\t$pathway\t";
	print FILE "$locus_tag\n";
	$o++;
	}
	}

}
print "l = $l";
print "m = $m";
print "n = $n";
print "o = $o";
close(FILE);
