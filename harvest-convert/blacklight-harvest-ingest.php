<?php 

/*
 *
 */

require('oaidocs/harvester/ADR_OAIHarvester.php');
require('oaidocs/oai-to-solr/OaiToSolrXmlParser.php');

fclose(STDOUT);
$STDOUT = fopen('application.log', 'wb');

$curDate 	= date('Y-m-d');
$dateFile 	= 'harvest.dat';
$logFile 	= 'auto-harvest-index.log';
$hdlDate 	= fopen($dateFile, 'r+');
$strDate 	= fread($hdlDate, filesize($dateFile));

// Open output buffer
ob_start();

// Welcome 
echo "\n*** Blacklight-DU Automatic Harvest and Index ***\n";
echo "Today's date: " . $curDate . "\n";
echo "Previous harvest date: " . $strDate . "\n";

// Harvest data
// Harvester is currently set to output to the path oaidocs/oai-solr.  
echo "Beginning harvest of OAI records from " . $strDate . " to present...\n";
//$harvester->harvest_sets(null,$strDate);
echo "Harvest complete.\n";

// Remove existing date, and add the current date of this harvest.  The next auto-harvest will harvest from this date onward.
//ftruncate($hdlDate, 0);
//fwrite($hdlDate, $curDate);

// Parse harvested xml to solr index xml
echo "Parsing harvested data to solr xml...\n";
//$parser->parseOAI();
echo "Parse complete.\n";

// Post index files to solr
echo "Posting new files to solr...\n";
echo shell_exec('java -jar oaidocs/oai-to-solr/oai-dc-converted/post.jar oaidocs/oai-to-solr/oai-dc-converted/*.xml') . "\n";	// Output folder created by solr parser if not present

// Move harvested xml files to the 'docs' dir
if(!file_exists('oaidocs/harvester/docs')) {

	mkdir('oaidocs/harvester/docs',0775);
}

// Store harvested files in docs/ folder
echo "Moving harvested files to docs/...\n";
echo shell_exec('mv oaidocs/oai-to-solr/*.xml oaidocs/harvester/docs/.') . "\n"; 

// Remove solr index files, so they are not re-indexed next time
echo "Removing solr index files...\n";
echo shell_exec('rm oaidocs/oai-to-solr/oai-dc-converted/*.xml') . "\n";

// Write output to file
$output = ob_get_flush();
file_put_contents($logFile, $output)

// Close files
fclose($hdlDate);

?>