<?php 

require('oaidocs/harvester/ADR_OAIHarvester.php');
require('oaidocs/oai-to-solr/OaiToSolrXmlParser.php');

$harvester = new ADR_OAIHarvester();
$parser = new OaiToSolrXmlParser();

$dateFile = 'harvest.dat';
$hdlDate = fopen($dateFile, 'r');
$strDate = fread($hdlDate, filesize($dateFile));

echo $date . "\n";

//$harvester->harvest_sets(null,$time);

?>