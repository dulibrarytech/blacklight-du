<?php 

/*
 * Auto Harvest and Blacklight Ingest
 * University of Denver, 1/2015, Jeff Rynhart
 *
 * A file named 'harvest.dat' must be present in the same folder as this script.  
 * 	This file should contain a date in the format 'yyyy-mm-dd'.  This is the date of the last data harvest for Blacklight
 */

require('ADR_OAIHarvester.php');
require('../oai-to-solr/OaiToSolrXmlParser.php');

$curDate 	= date('Y-m-d');
$dateFile 	= 'harvest.dat';
$logFile 	= 'auto-harvest-index.log';	/* TODO: add current date to log filename.  Remove 'APPEND' from write */

$harvester = new ADR_OAIHarvester();
$parser = new OaiToSolrXmlParser();

// Get previous harvest date.  If file not present, exit script.  Send email.
$hdlDate = fopen($dateFile, 'r+');
if($hdlDate === FALSE) {

	echo "harvest.dat not found or inaccessible, exiting script.\n";
	exit;
}
else {

	$strDate = fread($hdlDate, filesize($dateFile));
}

// Open output buffer
ob_start();

// Welcome 
echo "\n*** Blacklight-DU Automatic Harvest and Index ***\n";
echo "Today's date: " . trim($curDate) . "\n";
echo "Previous harvest date: " . trim($strDate) . "\n";

// Harvest data
// Harvester is currently set to output to the path oaidocs/oai-solr.  
echo "Beginning harvest of OAI records from " . trim($strDate) . " to present...\n";
$harvester->harvest_sets(null,trim($strDate));
echo "Harvest complete.\n";

// Remove existing date, and add the current date of this harvest.  The next auto-harvest will harvest from this date onward.
ftruncate($hdlDate, 0);
fwrite($hdlDate, trim($curDate));

// Copy files to solr parser dir
echo "Moving harvested files to solr parser...\n";
echo shell_exec('cp docs/*.xml ../oai-to-solr/.') . "\n";

// Parse harvested xml to solr index xml
echo "Parsing harvested data to solr xml...\n";
$parser->parseOAI();
echo "Parse complete.\n";

// Post index files to solr
echo "Posting new files to solr...\n";
echo shell_exec('java -Durl=http://localhost:8889/solr/update -jar oaidocs/oai-to-solr/oai-dc-converted/post.jar oaidocs/oai-to-solr/oai-dc-converted/*.xml') . "\n";	// Output folder created by solr parser if not present 

// Remove solr index files, so they are not re-indexed next time
echo "Removing solr index files...\n";
echo shell_exec('rm ../oai-to-solr/oai-dc-converted/*.xml') . "\n";
echo shell_exec('rm ../oai-to-solr/*.xml') . "\n";

// Write output to file
$output = ob_get_flush();
file_put_contents($logFile, $output, FILE_APPEND);

// Close files
fclose($hdlDate);

?>
