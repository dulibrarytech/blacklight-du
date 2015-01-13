<?php 

/*
 *
 */

require('oaidocs/harvester/ADR_OAIHarvester.php');
require('oaidocs/oai-to-solr/OaiToSolrXmlParser.php');

$harvester 	= new ADR_OAIHarvester();
$parser 	= new OaiToSolrXmlParser();

$curDate 	= date('Y-m-d');
$dateFile 	= 'harvest.dat';
$logFile 	= 'auto-harvest-index.log';
$hdlDate 	= fopen($dateFile, 'r+');
$strDate 	= fread($hdlDate, filesize($dateFile));
$hdlLog 	= fopen($logFile, 'w');
$strLog		= "";
$output 	= "";

// Welcome 
echo "\n*** Blacklight-DU Automatic Harvest and Index ***\n";
echo "Today's date: " . $curDate . "\n";
echo "Previous harvest date: " . $strDate . "\n";

// Log this instance
$strLog = "New harvest date: " . $strDate . "\nPrevious harvest date: " . $strDate . "\n" . 
	"Harvest of OAI records from " . $strDate . " to present:\n";
if(!fwrite($hdlLog, $strLog)) { echo "Error writing to log file...\n"; }

// Harvest data
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
$output = shell_exec('sudo java -jar oaidocs/oai-to-solr/oai-dc-converted/post.jar oaidocs/oai-to-solr/oai-dc-converted/*.xml');
$strLog = "Post output: " . $output . "\n";
fwrite($hdlLog, $strLog);

// Move harvested xml files to the 'docs' dir
if(!file_exists('oaidocs/harvester/docs')) {

	mkdir('oaidocs/harvester/docs',0775);
}

// Store harvested files in docs/ folder
echo "Moving harvested files to docs/...\n";
$output = shell_exec('sudo mv oaidocs/oai-to-solr/*.xml oaidocs/harvester/docs/.');
$strLog = "Moving harvested files to docs/..." . $output . "\n";
fwrite($hdlLog, $strLog);

// Remove solr index files, so they are not re-indexed next time
echo "Removing solr index files...\n";
$output = shell_exec('sudo rm oaidocs/oai-to-solr/oai-dc-converted/*.xml');
$strLog = "Removing solr index files..." . $output . "\n";
fwrite($hdlLog, $strLog);

// Close files
fclose($hdlDate);
fclose($hdlLog);

?>