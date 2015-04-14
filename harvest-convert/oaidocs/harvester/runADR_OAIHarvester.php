<?php

/*
 * Run the set harvester
 * Arg1: Harvest all records from this date until the present date.  
 *		(Pass a 0 here if havesting one set from creation date onward: ex. 'php runADR_OAIHarvester.php 0 codu:12345') sorry for complexity!
 * Arg2: Harvest records from this set (codu:nnnnn)
 * Batch: If a file named 'setPidList.txt' is present, all of the sets listed in the file (format codu:nnnnn) will be harvested
 */

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
