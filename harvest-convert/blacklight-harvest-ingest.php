<?php 

/*
 *
 */

require('oaidocs/harvester/ADR_OAIHarvester.php');
require('oaidocs/oai-to-solr/OaiToSolrXmlParser.php');

$harvester = new ADR_OAIHarvester();
$parser = new OaiToSolrXmlParser();

$curDate = date('Y-d-m');
$dateFile = 'harvest.dat';
$hdlDate = fopen($dateFile, 'rw');
$strDate = fread($hdlDate, filesize($dateFile));



echo "*** Blacklight-DU Automatic Harvest and Index ***\n";
echo "Today's date: " . $curDate . "\n";
echo "Most recent harvest prior to today: " . $strDate
echo "Beginning harvest of OAI records from " . $strDate . " to present...\n";
//$harvester->harvest_sets(null,$strDate);

// Remove existing date, and add the current date of this harvest.  The next auto-harvest will harvest from this date onward.
//ftruncate($hdlDate, 0);

fclose($hdlDate);

?>