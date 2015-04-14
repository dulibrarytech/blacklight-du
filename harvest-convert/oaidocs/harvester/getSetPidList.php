<?php

require('ADR_OAIHarvester.php');

$harvester = new ADR_OAIHarvester();
error_reporting(0);

$list = $harvester->getSetList();

foreach($list as $item) {

	echo $item . "\n";
}