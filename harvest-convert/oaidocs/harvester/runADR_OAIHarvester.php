<?php

require('ADR_OAIHarvester.php');
 
$harvester = new ADR_OAIHarvester();
error_reporting(0);

if(!isset($argv[2])) 
{
	$time = null;	
	if(isset($argv[1]))
		$time = $argv[1];

	$fileArr = file('setPidList.txt');
	if($fileArr != false) 
	{

		echo "Using setPidList.txt...\n";
		$harvester->harvest_sets($fileArr);
	}
	else 
	{

		echo "Using auto harvest...\n";
		$harvester->harvest_sets(null,$time);
	}
}	
else
{
	$time = $argv[1];
	$set = $argv[2];
	echo "Single record harvest: " . $set . "\n";
	$harvester->harvest_set($set,$time);
}


?>
