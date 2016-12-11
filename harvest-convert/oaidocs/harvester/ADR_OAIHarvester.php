<?php

/* ADR OAI Harvester 
 * 
 * Author: 		Jeff Rynhart jeff.rynhart@du.edu
 * Description: Harvest record sets from the adr based on time range
 * NOTICE: 		Must set outputFolder to desired location
 *
 * University of Denver, University Libraries, 4/2014 
 *
 * Use:  	harvest_sets(array('codu:12345', 'codu:23456', 'codu:34567' ...))   
 *			
 * This harvester is designed to work with a "maximum response size" setting in the islandora oai provider.  The value set in 
 * the class variable must match the oai provider setting.  The purpose is to prevent extremely large collections from "bonking out" the islandora server.
 * How it works: The oai request from a single pid, without including from/until dates, will return all of the records in the collection.  If the amount of records returned is 
 * equal to this max amount, it can be reasonably assumed that there are more records to retrieve.  In this case the harvester "splits" the date range of the record
 * request in half, then attempts to gather all records from the first half.  If this first half also returns the max limit, it is then split in half and the 
 * process repeats.  Each date range is pushed on a stack and searched consecutively.  If < the limit is returned, that is all of the records that exist in
 * the date range, and they are written to an output file.
 */

require('file_helper.php');

class ADR_OAIHarvester {

	private $recCount;
	private $fileCount;
	private $toPointStack;
	private $outputFolder;
	private $OAI_maxRecordOutput = 200;		// The max record output setting in the Islandora OAI module config menu (Maximum response size)

	function __construct() {

		$this->recCount = 0;
		$this->fileCount = 0;
		$this->outputFolder = "docs/"; 	// must include trailing '/' here
		$this->toPointStack = array("");
	}

	// Harvest sets from coalliance.digitaldu.org
	// pidArr: Array of pids to harvest. Format: (array('codu:12345', 'codu:23456', 'codu:34567' ...))
	public function harvest_sets($pidArr = null, $startDate = null) {

		echo "Running auto harvest...\n";

		if(!file_exists($this->outputFolder)) {
			echo "Creating output folder '" . $this->outputFolder . "'...";
			mkdir($this->outputFolder,0775);
		}

		// Ping the repository for the current top set list
		if($pidArr != null && is_array($pidArr)) 
		{
			echo "File present, " . count($pidArr) . " sets listed.\n";
			foreach($pidArr as &$pid) {

				$pid = trim($pid);	// must remove any whitespace or newlines from the text file lines
				echo $pid . "\n"; // this is not necessary
			}
			$currentSets = $pidArr;

		}
		else 
		{
			echo "Retrieving current sets...\n";
			$currentSets = $this->getSetList();
			echo "Sets received: " .  print_r($currentSets,true) . "\n";
		}

		echo "Harvesting set records...\n";
		foreach($currentSets as $setPid)
		{
        	echo "Harvesting records from set " . $setPid . "\n";
        	$this->harvest_set($setPid,$startDate);
        }
	}

	public function harvest_set($setPid,$startDate = null) {

		if($setPid == null || $setPid == "")
		{
			echo "\nComplete.\n";
			return 0;
		}

		echo "Harvesting records from set " . $setPid . "...\n";

		if($startDate != null)
			$fromDate = $startDate;
		else	
			$fromDate = $this->getSetCreationDate($setPid);
    	$toDate = "";

    	if($fromDate !== false)
    	{
    		if(!$this->writeRecordSetSectionsToFiles($setPid,$fromDate,$toDate))
    		{
    			if($this->recCount !== 0) {

    				echo "Wrote " . $this->recCount . " records from set " . $setPid . " into " . $this->fileCount . ($this->fileCount > 1 ? " files.\n" : " file.\n");
    				$this->recCount = 0;
    			}

        		$this->fileCount = 0;
    		}
    		// else
    		// 	echo "No records found for set " . $setPid . "\n";	//	Function should never return true
    	}
    	else
    		echo "Error: No creation date found for set " . $setPid . ".  Is pid format valid?\n";
	}

	// Returns the date that the given set was created
	public function getSetCreationDate($setPid) {

		echo "Retrieving creation date for set " . $setPid . "\n";

		$setPid = str_replace("_", ":", $setPid);
		//$url = "http://coduFedora:denverCO@fedora.coalliance.org:8080/fedora/objects/" . $setPid . "/objectXML";
        //$url = "http://fedoraAdmin:f3d0r@@dm1ndu@lib-caspian.du.edu:8080/fedora/objects/" . $setPid . "/objectXML";
        $url = "http://fedoraAdmin:f3d0r@@dm1ndu@librepo01-vlp.du.edu:8080/fedora/objects/" . $setPid . "/objectXML";
		$retStr = false;
		$trimVal = "";
		$foundNode = false;

		$xmlStr = file_get_contents($url);

		$xmlStr = str_replace("foxml:", "", $xmlStr);
		$xmlObj = simplexml_load_string($xmlStr);

		if($xmlObj != null)
        {
        	foreach($xmlObj as $node)
	        {
            	if($node->getName() === "objectProperties")
    			{
    				$children = $node->children();
		            foreach($children as $childNode)
		            {
		            	if($childNode->getName() === "property")
	        			{
	        				foreach($childNode->attributes() as $a => $b)
			        		{
			        			if($foundNode === false)
			        			{
				        			$propNode = substr(trim($b),-11); // 

				        			if(trim($a) == "NAME" &&
				        				$propNode == "createdDate")
				        			{
				        				$foundNode = true;
				        			}
			        			}
			        			else
			        			{
			        				if(trim($a) == "VALUE")
			        				{
			        					$retStr = trim($b);
			        				}
			        				break;
			        			}
			        		}
	        			}
	        		}
    			} 
	        }
        }
        else {
        	echo "ERROR: Failed to load xml object for pid " . $setPid . "\n";
        }

        if($retStr != "")
        	$retStr =  substr($retStr, 0, 19) . "Z";	
        
        return $retStr;
	}

	// Return an array of all current ADR sets
	public function getSetList() {

		// String array to hold set pids
		$sets = array();

		$url = "https://specialcollections.du.edu/oai2?verb=ListSets";
		$xmlStr = file_get_contents($url);
		$xmlObj = simplexml_load_string($xmlStr);

		if($xmlObj != null)
        {
        	foreach($xmlObj as $node)
	        {
	        	if($node->getName() === "ListSets")
	        	{
	        		$children = $node->children();
		            foreach($children as $childNode)
		            {
		                if($childNode->getName() === "set")
		                {
		                	$children = $childNode->children();
				            foreach($children as $childNode)
				            {
				                if($childNode->getName() === "setSpec" && $childNode->getName() != "codu_top") // codu_top contains all records.  Will create duplicates 4/14/15
				                {
				                	array_push($sets, trim((string)$childNode));
				                }
				            }
				        }
				    }
				}
			}
        }
        else {
        	echo "ERROR: Failed to load xml object from 'ListSets' output\n";
        }
    
		return $sets;
	}

	// Writes all records from set that are dated with in the given date range to files.  
	// Each file will contain < [$OAI_maxRecordOutput] records
	protected function writeRecordSetSectionsToFiles($setPid,$from,$until) {

		// We have reached the omnipresent
		if($from == "" && $until == "")
			return false;

		$to = ($until == "") ? "present" : $until;
		echo "Retrieving records for " . $setPid . " from " . $from . " to " . $to . "\n";
		
		$xmlString = $this->listRecords($setPid,$from,$until); 
		if($xmlString === false) {

			echo "XML String is null from ListRecords on set " . $setPid . "\n";
			return false;
		}

		// Validate the returned xml

		// Count number of individual records are in the set  
		$count = $this->getRecordCount($xmlString);
		echo $count . " records found.\n";

		// Account for 'no records' ie empty record set.
		// First, check forward from the current 'to' point until the previous 'to' point.
		// If none there, 
		if($count === 0) 
		{
			$from = $until;

			// Don't pop the last one
			if(count($this->toPointStack) === 1)
				$until = "";
			$until = array_pop($this->toPointStack);

			if(!$this->writeRecordSetSectionsToFiles($setPid,$from,$until))
			{
				return false;
			}
		}
		else if($count < $this->$OAI_maxRecordOutput)
		{
			$this->writeToFile( $this->composeFilestring($setPid,$from,$until), $xmlString );
			$this->fileCount++;
			$this->recCount += $count;

			$from = $until;

			// Don't pop the last one
			if(count($this->toPointStack) === 1)
				$until = "";
			$until = array_pop($this->toPointStack);
			//$until = "";

			if(!$this->writeRecordSetSectionsToFiles($setPid,$from,$until))
				return false;
		}
		else 
		{
			echo "Splitting time range...\n";
			//$this->prevToPoint = $until;  // or: push until onto save array stack
			array_push($this->toPointStack, $until);

			$until = $this->getMidrangeDate($from,$until);
			if(!$this->writeRecordSetSectionsToFiles($setPid,$from,$until))
			{	
				// $from = $until; 
				// $until = $this->prevToPoint;
				// if(!$this->writeRecordSetSectionsToFiles($setPid,$from,$until))
					return false;
			}


		}
	}

	// Returns the datetime which falls at the approximate center of the given range
	protected function getMidrangeDate($from,$to) {

		$from = str_replace("Z", "", $from);
		$to = str_replace("Z", "", $to);
		$retStr = "";

		try 
		{
			$fromDateObj = new DateTime($from);
			if($to == "")
				$toDateObj = new DateTime("now");
			else
				$toDateObj = new DateTime($to);

			$diff = $fromDateObj->diff($toDateObj);
			$halfDiff = $this->getHalfDateTimeInterval($diff);
			$midrangeDate = $fromDateObj->add($halfDiff);

			$retStr = substr($midrangeDate->format(DateTime::ATOM),0,19) . "Z";
		}
		catch(Exception $e)
		{
			echo "getMidrangeDate(): " . $e->getMessage();
		}

		return $retStr;
	}

	protected function getHalfDateTimeInterval($interval) {

		// Get half the number of days in the interval
		$daySpan = round($interval->days / 2,0,PHP_ROUND_HALF_UP);
		$hr = $min = $sec = 0;

		// Sort the total days back into separate parts
		$year = floor($daySpan / 365);
		$rem = $daySpan % 365;
		$month = floor($rem / 30.5);
		$rem -= floor($month*30.5);

		$day = $rem > 0 ? $rem-1 : $rem;

		if($interval->d === 1) 
		{
			$hr = $interval->h + 12;
			$day += $hr > 24 ? 1 + ($hr-24) : 0;
		}
		else if($interval->d > 0)
		{
			$day = ceil($interval->d / 2);
		}
			

		// Get half the input time 
		if($interval->h === 1)
		{
			$min = $interval->i + 30; // Add half an hour to minutes
			$hr += $min > 60 ? 1 + ($min-60) : 0; // If this rolls the minutes over 60, add an hour, and add the difference back to the minutes
		} 
		else if($interval->h > 0)
		{
			$hr = ceil($interval->h / 2);
		}

		if($interval->i === 1)
		{
			$sec = $interval->s + 30;
			$min += $sec > 60 ? 1 + ($sec-60) : 0;
		}
		else if($interval->i > 0)
		{
			$min = ceil($interval->i / 2);
		}

		if($sec === 0 && $interval->s > 1)
		{
			$sec = ceil($interval->s / 2);
		}
		else // if $sec !=== 0 and interval->s >= 1
		{
				// divide seconds here
		}

		$init = "P" . abs($year) . "Y" . abs($month) . "M" . abs($day) . "DT" . abs($hr) . "H" . abs($min) . "M" . abs($sec) . "S";
		$halfInterval = new DateInterval($init);
		return $halfInterval;
	}

	// Write the data to the specified path/file
	private function writeToFile($filepath,$data) {

		if(file_put_contents($filepath, $data) === false) {

			echo "Error writing to file: " . $filepath . "\n";
			echo "Data type: " . gettype($data) . "\n";
			echo "Exiting script...\n";
			exit;
		}
		else {

			echo "Created file: " . $filepath . "\n";
		}
	}

	// Return a list of records from the ADR
	private function listRecords($pid, $from = "", $until = "") {

		if($from !== "")
			$from = "&from=" . $from;
		if($until !== "")
			$until = "&until=" . $until;

		$url = "https://specialcollections.du.edu/oai2?verb=ListRecords" . $from . $until . "&metadataPrefix=oai_dc&set=" . $pid;	
		$data = file_get_contents($url); 

		return $data;
	}

	// Create a filename containing the pid along with the from and to date
	private function composeFilestring($pid,$from,$until) {

		$untilTag = "";
		$fromTag = $from == "" ? "" : "_F_" . $from;
		if($from != "")
			$untilTag = $until == "" ? "_T_Present_" . date("Y-m-d") : "_T_" . $until; 

		$fromTag = str_replace(":", "-", $fromTag);
		$untilTag = str_replace(":", "-", $untilTag);

		return $this->outputFolder . $pid . $fromTag . $untilTag . ".xml";
	}

	// Return number of records found in given xml file, false if xml object failed to be created
	public function getRecordCount($xmlString) {

		$recordSetXmlObj = simplexml_load_string($xmlString);

		if($recordSetXmlObj != null)
        {
        	$count = 0;
        	foreach($recordSetXmlObj as $node)
	        {
	        	// This file contains a record set
	        	if($node->getName() === "ListRecords")
	        	{
	        		$children = $node->children();
		            foreach($children as $childNode)
		            {
		                if($childNode->getName() === "record")
		                {
		                	$count++;
		                }
		            }
		        }
		    }

		    return $count;
        }
        else
       	{
       		return false;
       	}	
	}
}



?>