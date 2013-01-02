get_hyp_prot.pl - This script extracts hypothetical proteins from a genbank/embl file and saves them as fasta 

parse_blast2genbank.pl - This script takes the output from swissprot and TREMBL BLAST results and adds the results to the annotation if positives >= 75 & percent_hit_length >= 85 and percent_query_length >= 85

get_bad_anno_prots.pl - This script takes a list of 'bad annotations' from extracted from the GenBank discrepancy file and gets the FASTA sequences for these genes

get_pseduogenes.pl - Extracts the pseudogenes from the annotation 

test_blast_kegg.pl - Uses results from BLAST against KEGG to extract the pathways (and higher descriptions) associated with orthologs, this gives the counts functional genes 

blast_kegg.pl - Uses results from BLAST against KEGG to extract the pathways (and higher descriptions) associated with orthologs, this gives the counts for pseudogenes

embl2fasta_leftovers.pl - This takes the predicted genes that didn't have an ortholog in the reciprocal fasta and extracts them into a separate file for BLAST comparison

reciprocal_latest_fasta_gsv_AB.pl - modification of the Sanger script, performs reciprocal fasta against a reference genome to transfer annotation

remove_bad_hyps.pl - This script removes any hypothetical proteins which overlap/are contained in another protein and are below a certain size

map_mutations.pl - This script takes a base position (in this case of a mutation) and maps the corresponding gene to it.

tradis_sig_genes.pl - This script takes a tab delimited file of tradis data and sorts the significantly attenuated genes into hashes for each organism