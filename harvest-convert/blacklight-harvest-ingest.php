<?php 

/*
 *
 */

require('oaidocs/harvester/ADR_OAIHarvester.php');
require('oaidocs/oai-to-solr/OaiToSolrXmlParser.php');

$harvester = new ADR_OAIHarvester();
$parser = new OaiToSolrXmlParser();

$curDate = date('Y-m-d');
$dateFile = 'harvest.dat';
$hdlDate = fopen($dateFile, 'r+');
$strDate = fread($hdlDate, filesize($dateFile));

echo "\n*** Blacklight-DU Automatic Harvest and Index ***\n";
echo "Today's date: " . $curDate . "\n";
echo "Previous harvest date: " . $strDate . "\n";
echo "Beginning harvest of OAI records from " . $strDate . " to present...\n";

// Harvest data
$harvester->harvest_sets(null,$strDate);

// Remove existing date, and add the current date of this harvest.  The next auto-harvest will harvest from this date onward.
//ftruncate($hdlDate, 0);
//fwrite($hdlDate, $curDate);

// Parse harvested xml to solr index xml
//$parser->parseOAI();

// Post index files to solr
$output = shell_exec('sudo java -jar oaidocs/oai-to-solr/oai-dc-converted/post.jar oaidocs/oai-to-solr/oai-dc-converted/*.xml');

// Move harvested xml files to the 'docs' dir
if(!file_exists('oaidocs/harvester/docs')) {

	mkdir('oaidocs/harvester/docs',0775);
}
$output = shell_exec('sudo mv oaidocs/oai-to-solr/*.xml oaidocs/harvester/docs/.');

// Remove solr index files, so they are not re-indexed next time
$output = shell_exec('sudo rm oaidocs/oai-to-solr/oai-dc-converted/*.xml');

fclose($hdlDate);

?>