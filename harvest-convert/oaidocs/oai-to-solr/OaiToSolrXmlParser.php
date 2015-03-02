<?php

/* OAI:DC-to-Solr Parser Class
 * 
 * Author: 		Jeff Rynhart jeff.rynhart@du.edu
 * Description: Convert OAI:DC xml files to Solr index input format xml files. 
 * Input: 		OAI:DC .xml file(s) from current directory. 
 *				Filename format: 'codu_#####.xml'
 * Output: 		Solr-compatible index .xml file(s) *** Must create output dir prior to conversion: './oai-dc-output/' ***
 *
 * University of Denver, University Libraries, 5/2014 */


/* Note: Individual oai:dc records are enclosed by <doc> tags in the solr index file syntax.
 * 		 An entire file (multiple oai:dc records) is enclosed by <add> tags.
 *	     In this script, a 'record' refers to the individual oai:dc record, and 'document' refers to the entire [oai:dc].xml file
 *
 *			To use, run oai-solr-parser-script.php in the directory containing .xml files to convert.  Or, instantiate an object and run parseOAI in the directory.
 *			This script must be run from a location with permissions to access the ADR.*/

//require_once('file_helper.php');

class OaiToSolrXmlParser {

	protected $solr_indexStr = "";	// the main output string, containing everything from <add> to </add>
	protected $docString = "";		// The temp record string, containing everything from <doc> to </doc>.  

	// File variables
	protected $docCount = 0;
	protected $recCount = 0;
	protected $totalRecCount = 0;
	protected $urlPos = 0;
	protected $folderPos = 0;
	protected $folder = "00000";
	protected $outputFolder = "oai-dc-converted/";	// include trailing slash; use "" for local folder

	// Control variables
	protected $dateSet = false;
	protected $ID = "";

	// Error checking
	protected $typeSet = false;
	protected $IDSet = false;
	protected $localIDSet = false;
	protected $subjectSet = false;
	protected $formatSet = false;
	protected $creatorSet = false;
	protected $TNSet = false;
	protected $missingID = 0;
	protected $missingLocalID = 0;
	protected $missingTypeFacet = 0;
	protected $missingSubjectFacet = 0;
	protected $missingFormatFacet = 0;
	protected $missingTN = 0;

	// Exclude this field from format facets
	protected $formatFacetExcludeFields = array('');
	// If format data contains these chars, do not use it for a facet field
	protected $formatFacetExcludeChars = array('[', '.', ',');

	public function parseOAI() {

		echo "Parsing OAI-DC files...\n";
		echo "Connecting to fedora.coalliance.org...\n";

		// Only parses *.xml files
		$files = scandir('.');

		// Main parser loop
		foreach($files as $file)
		{
			if(substr($file, -4) === '.xml')
			{
				// Add document tag
				$this->solr_indexStr = "<add>\n";

				// Create output filename
				$filename = substr($file, 0, -4) . "_SOLR.xml";

				// Convert xml file to string, remove prefixes
				$xmlString = file_get_contents($file);
				$xmlString = str_replace('oai_dc:', '', $xmlString);
				$xmlString = str_replace('dc:', '', $xmlString);

				// Replace chars that will be sanitized by solr (leave solr filters in place for now)
				$xmlString = str_replace('&amp;', 'and', $xmlString);

				$this->folder = 10176;	// temp

				$xmlObj = simplexml_load_string($xmlString);

				if($xmlObj == null)
		            continue;

		        foreach($xmlObj as $node)
		        {
		        	// This file contains a record set
		        	if($node->getName() === "ListRecords")
		        	{
		        		$children = $node->children();
			            foreach($children as $childNode)
			            {
			                if($childNode->getName() === "record")
			                {
			                	$children = $childNode->children();
					            foreach($children as $childNode)
					            {
					            	// Grab the main identifier of this record
					                if($childNode->getName() === "header")
					                {
					                	$children = $childNode->children();
							            foreach($children as $childNode)
							            {
							            	if($childNode->getName() === "identifier")
					                		{
					                			$URLString = trim((string)$childNode);
					                			$this->ID = $this->getPidFromURL($URLString);   							
					                		}
					                	}
					                } 

					            	// Get all other data
					            	else if($childNode->getName() === "metadata")
			                		{
			                			$children = $childNode->children();
			                			foreach($children as $childNode)
					            		{	
					            			// This is the individual record dc data
					            			if($childNode->getName() === "dc")
			                				{
			                					// Add record tag 
			                					$this->docString .= "<doc>\n";
			                					$title = false;

			                					if($this->ID != "")
			                					{
			                						$this->IDStr = "codu:" . $this->ID;
			                						$this->docString .= "<field name='links'>http://hdl.handle.net/" . $this->folder . "/" . $this->IDStr . "</field>\n";
			                						$this->docString .= "<field name='id'>" . $this->IDStr . "</field>\n";
			                						$this->IDSet = true;
			                						
			                						$tempStr = $this->getThumbnailDataField();
	                								if($tempStr != "")
	                								{
	                									$this->TNSet = true;
	                									$this->docString .= $tempStr;
	                								}
			                					}

			                					$children = $childNode->children();
			                					foreach($children as $childNode)
					            				{
					            					if($childNode->getName() === "title" && !$this->isEmpty($childNode))
			                						{
			                							if($title === false)
			                							{
			                								// just using first instance of title
			                								$this->docString .= "<field name='title'>" . trim((string)$childNode) . "</field>\n";
			                								$title = true;
			                							}
			                						}
			                						else if($childNode->getName() === "creator" && !$this->isEmpty($childNode))
			                						{
			                							$this->docString .= "<field name='creator'>" . trim((string)$childNode) . "</field>\n";
			                							$this->creatorSet = true;
			                						}
			                						else if($childNode->getName() === "subject" && !$this->isEmpty($childNode))
			                						{
			                							$tempStr = $this->setSubjectFields((string)$childNode);
			                							if(stripos($tempStr, "subject_facet"))
			                							{
			                								$this->subjectSet = true;
			                							}
			                							
			                							if(stripos($this->docString, $tempStr) === FALSE)
			                							{
			                								$this->docString .= $tempStr;		                							
			                							}
			                						}
			                						else if($childNode->getName() === "type" && !$this->isEmpty($childNode))
			                						{
			                							$tempStr = $this->setTypeFields((string)$childNode);
			                							if(stripos($tempStr, "type_facet"))
			                							{
			                								$this->typeSet = true;
			                							}
			                							
			                							$this->docString .= $tempStr;
			                						}
			                						else if($childNode->getName() === "format" && !$this->isEmpty($childNode))
			                						{
			                							$tempStr = $this->setFormatFields((string)$childNode);
			                							if(stripos($tempStr, "format_facet"))
			                							{
			                								$this->formatSet = true;
			                							}
			                							
			                							$this->docString .= $tempStr;
			                						}
			                						else if($childNode->getName() === "date" && !$this->isEmpty($childNode) && !$this->dateSet)
			                						{
			                							$numericDate = preg_replace( '[^0-9-]', '', trim((string)$childNode) );
			                							$this->docString .= "<field name='pub_date'>" . $numericDate . "</field>\n";
			                							$this->dateSet = true;
			                						}
			                						else if($childNode->getName() === "abstract" && !$this->isEmpty($childNode))
			                						{
			                							$this->docString .= "<field name='abstract'>" . trim((string)$childNode) . "</field>\n";
			                						}
			                						else if($childNode->getName() === "identifier" && !$this->isEmpty($childNode))
			                						{
			                							$trimmedString = trim((string)$childNode);

			                							// If this record contains a uri (hdl.handle.net), use the listed codu: PID as the record ID.
			                							if(substr($trimmedString,0,10) === 'http://hdl' 
			                								&& $this->IDSet === false)
			                							{
			                								$this->docString .= "<field name='links'>" . $trimmedString . "</field>\n";
			                								$this->docString .= "<field name='id'>" . substr($trimmedString , -10) . "</field>\n";
			                								$this->IDSet = true;

			                								$tempStr = $this->getThumbnailDataField();
			                								if($tempStr != "")
			                								{
			                									$this->TNSet = true;
			                									$this->docString .= $tempStr;
			                								}
			                							}
			                							// Some records do not contain a uri.  Build the uri for the links field if this is a 'codu:' entry, and the ID has not yet been set.
			                							else if(substr($trimmedString,0,5) === 'codu:' 
			                									&& $this->IDSet === false)
			                							{
			                								$this->docString .= "<field name='links'>http://hdl.handle.net/" . $this->folder . "/" . trim((string)$childNode) . "</field>\n";
			                								$this->docString .= "<field name='id'>" . $trimmedString . "</field>\n";
			                								$this->IDSet = true;

			                								$tempStr = $this->getThumbnailDataField();
			                								if($tempStr != "")
			                								{
			                									$this->TNSet = true;
			                									$this->docString .= $tempStr;
			                								}
			                							}
			                							// Local id format A000.00.0000.0000.00000
			                							// else if(!is_numeric($trimmedString[0]) && $trimmedString[4] == '.')
			                							// {
			                							// 	$trimmedString = (ucfirst($trimmedString)); //  TEMP: Set 1st char to uppercase here, for correct Identifier
			                							// 	$this->solr_indexStr .= "<field name='local_identifier'>" . $trimmedString . "</field>\n";
			                							// 	$this->localIDSet = true;
			                							// }
			                						}
					            				}

					            				/* Record analysis:
					            				 * Detect missing fields here.  Mark in parser output string for visual notice (ie facet fields that will be seen immediately)
					            				 * Keep a tally of the missing facets for parser output
					            				 */
					            				if(!$this->typeSet) 
							            		{
							            			//$this->solr_indexStr .= "<field name='type_facet'>[no type facet]</field>\n";
							            			$this->missingTypeFacet++;
							            		}
							            		else
							            			$this->typeSet = false;

							            		if(!$this->subjectSet)
							            		{
							            			//$this->solr_indexStr .= "<field name='subject_facet'>[no subject facet]</field>\n";
							            			$this->missingSubjectFacet++;
							            		}
							            		else
							            			$this->subjectSet = false;

							            		if(!$this->formatSet)
							            		{
							            			//$this->solr_indexStr .= "<field name='format_facet'>[no format facet]</field>\n";
							            			$this->missingFormatFacet++;
							            		}
							            		else
							            			$this->formatSet = false;

							            		if(!$this->IDSet)
							            		{
							            			$this->docString .= "<field name='id'>[no id]</field>\n"; 
							            			$this->missingID++;
							            		}
							            		else
							            			$this->IDSet = false;

							            		if(!$this->localIDSet)
							            		{
							            			$this->missingLocalID++;
							            		}
							            		else
							            			$this->localIDSet = false;

							            		if(!$this->TNSet)
							            		{
							            			$this->missingTN++;
							            		}
							            		else
							            			$this->TNSet = false;

							            		if(!$this->creatorSet)
							            		{
							            			$this->docString .= "<field name='creator'>None specified</field>\n";
							            		}
							            		else
							            			$this->creatorSet = false;

							            		$this->dateSet = false;

					            				// Close the document (<record>) for solr, increment record count
					            				$this->docString .= "</doc>\n\n";

					            				// Append the current doc string to the main solr string
					            				$this->solr_indexStr .= $this->docString;
					            				
					            				// Reset vars for next iteration
					            				$this->docString = "";					 
					            				$this->recCount++;
					            				$this->ID = "";
			                				}
					            		}	
			                		}	                	 
					            }
			                }	                  
			            }
		        	}

		        	// This file contains an individual record
		        	else if($node->getName() === "dc")
		        	{
		        		// No reason for this yet
		        	}
		        }

		        // Close the file for solr, increment document count.  
		        $this->solr_indexStr .= "</add>";
		        $this->docCount++;

		        // Output total number of records converted in this file
		        $this->totalRecCount += $this->recCount;
		        echo "\n" . $this->recCount . " records converted in file " . $file . "...\n";
		        $this->recCount = 0;

		        // Output missing required data
		        echo $this->missingID . " ID fields missing from this file.\n";
		        $this->missingID = 0;
		        echo $this->missingTypeFacet . " Type fields missing from this file.\n";
		        $this->missingTypeFacet = 0;
		        echo $this->missingSubjectFacet . " Subject fields missing from this file.\n";
		        $this->missingSubjectFacet = 0;
		        echo $this->missingFormatFacet . " Format fields missing from this file.\n";
		        $this->missingFormatFacet = 0;
		        // echo $this->missingLocalID . " Local ID fields missing from this file.\n";
		        // $this->missingLocalID = 0;
		        echo $this->missingTN . " Thumbnail links missing from this file\n";
		        $this->missingTN = 0;

				// Write solr xml to file
				$data = $this->solr_indexStr;
		        if($this->writeToFile($filename, $data) === false)
		        	echo "File write error: " . $filename . " aborted.\n";
		        else
		        	echo "File " . $filename . " completed.\n";
			}
		}

		// Output total number of documents (xml files) parsed
		echo "\n" . $this->docCount . " documents parsed,";
		echo "\n" . $this->totalRecCount . " total records converted.\n\n";
	}

	protected function isEmpty($node) {

		return (string)$node === ""; 
	}

	protected function writeToFile($file, $data) {

		if(file_exists($this->outputFolder) === false) {
			
			mkdir($this->outputFolder,0775);
		}

		$file = $this->outputFolder . $file;
		echo "Writing " . $file . "...\n";
		$status = false;

		try {

			$fp = fopen($file, 'w');

			if ($fp === false) {
			
				echo "Error opening file: " . $file . "\n";
			}
			else {

				$status = fwrite($fp, $data);
				fclose($fp);
			}
		}
		catch(Exception $e) {

            echo $e->getMessage() . "\n";
        }

		return $status;
	}
 
 	public function getPidFromURL($URL) {

		$pos = strlen($URL)-1;
		$char = $URL[$pos];
		$PID = "";
		while(is_numeric($char) && $pos >= 0)
		{
			$PID .= $char;
			$char = $URL[--$pos];
		}

		return strrev($PID);
		// $len = strlen($URL);

		// return substr($URL, $len-5, 5);
	}

	protected function stringContainsExcludeChars($string,$excludeArray) {

		$found = false;
		foreach($excludeArray as $term)
		{
			if(stripos($string, $term) !== false)
				$found = true;
		}
		return $found;
	}

	// Normalize type facets
	// Set type data as facet if it matches one of the 'conversion' cases.  The purpose of this is to eliminate redundant type facets.
	// All other type data is stored in the regular type field
	protected function setTypeFields($string) {
	
	 	$returnString = "";
	 	//$string = strtolower($string);
	 	//$string = trim($string);
	 	$tempstring = trim($string, ".,");
		$tempstring = preg_replace('/\s\s+/', ' ', $string);	// Reduce any double whitespaces to one.;
	 	//$string = str_replace(" ", "", $string);
	 	$tempstring = strtolower($tempstring);
	 	
	 	// Add special cases here (conversions)
	 	if(substr($tempstring,0,6) == "moving")
	 	{
	 		$returnString = "<field name='type_facet'>Moving Image</field>\n";
	 	}
	 	else if(substr($tempstring,0,5) == "sound")
	 	{
	 		$returnString = "<field name='type_facet'>Sound Recording</field>\n";
	 	}
	 	else if(substr($tempstring,0,5) == "photo")
	 	{
	 		$returnString = "<field name='type_facet'>Still Image</field>\n";
	 	}
	 	else if(substr($tempstring,0,5) == "still")
	 	{
	 		$returnString = "<field name='type_facet'>Still Image</field>\n";
	 	}
	 	else if(substr($tempstring,0,3) == "art")
	 	{
	 		$returnString = "<field name='type_facet'>Art Reproduction</field>\n";
	 	}
	 	else if(substr($tempstring,0,5) == "scrap")
	 	{
	 		$returnString = "<field name='type_facet'>Scrapbook</field>\n";
	 	}
	 	else if(substr($tempstring,0,4) == "text")
	 	{
	 		$returnString = "<field name='type_facet'>Text</field>\n";
	 	}
	 	else if(substr($tempstring,0,3) == "map")
	 	{
	 		$returnString = "<field name='type_facet'>Map</field>\n";
	 	}
	 	else
	 	{
	 		$returnString = "<field name='type'>" . trim($this->ucEachWord($string)) . "</field>\n";
	 	}

	 	return $returnString;
	}

	protected function ucEachWord($string) {

		$retString = "";

		$wordChunks = explode(" ", $string);
		for($i = 0; $i < count($wordChunks); $i++){

			$retString .= ucfirst($wordChunks[$i]) . " ";
		}

		return $retString;
	}

	protected function getThumbnailDataField($pid = "") {

		$string = "";

		// Construct field string with link to thumbnail
		if($pid == "")
			$pid = "codu:" . $this->ID;

		$dsid = $this->getThumbnailDsid($pid);

		if($dsid != null)
			$string = "<field name='thumbnail'>http://digitaldu.coalliance.org/fedora/repository/" . $pid . "/" . $dsid . "</field>\n";

		return $string;
	}

	protected function getThumbnailDsid($pid) {

		$dsid = null;
		//echo "Connecting to remote server for thumbnail image...\n";	// <-----DEBUG
		$url = "http://coduFedora:denverCO@fedora.coalliance.org:8080/fedora/listDatastreams/" . $pid . "?xml=true";
		$xmlStr = file_get_contents($url);

		if($xmlStr === false) {

			echo "Failed to retrieve image datastream from ADR.\n";
		}

		// parse out the dsid for the thumbnail
		$xmlObj = simplexml_load_string($xmlStr);

		if($xmlObj != null)
        {
        	foreach ($xmlObj->children() as $child) 
        	{
    			if($child['dsid'] != "TN" &&
	        		$child['label'] == "thumbnail" &&
	        		substr($child['mimeType'],0,6) == "image/") {

	        		$dsid = $child['dsid'];
	        	//	echo "Image found.\n";
	        	}
    		}
        }
		else {

			echo "Failed to create xml object.\n";
		} 


		return $dsid;
	}

	// Current logic:
	// Format facet can be set with any oai:dc format field entry, if the data found passes these rules:
	// 1. It does not begin with a numeric character
	// 2. It is not in the formatFacetExcludeFields array
	// 3. If there are less than 4 words in the string
	protected function setFormatFields($string) {

		if($string != "")
		{
			$string = strtolower($string);
		 	$string = trim($string, ".,");
		 	$strLen = strlen($string);
		 	$returnString = "";

		 	// Singularize term
		 	if($string[$strLen-1] == 's')
		 		$string = substr($string, 0, -1);

		 	//Remove 'empty commas'
		 	if($string[0] == ',')
		 		$string = substr($string, 1);
		 	$string = str_replace(' ,', '', $string);

			// Reduce any double whitespaces to one.
			$string = preg_replace('/\s\s+/', ' ', $string); 

			// kludge
			if(isset($string[0]) === false || $string[0] == null)
				return "";

			$firstChar = $string[0];
		 	if(is_numeric($firstChar))
		 	{
		 		$returnString = "<field name='format'>" . trim($string) . "</field>\n";
		 	}
		 	else if(in_array($string, $this->formatFacetExcludeFields))
		 	{
		 		$returnString = "<field name='format'>" . trim($string) . "</field>\n";
		 	}
		 	// No records that contain exclude chars are facets
		 	else if($this->stringContainsExcludeChars($string,$this->formatFacetExcludeChars))
		 	{
		 		$returnString = "<field name='format'>" . trim($string) . "</field>\n";
		 	}
		 	// Typo mitigation
		 	else if($string == "born digtal")
		 	{
		 		$returnString = "<field name='format_facet'>born digital</field>\n";
		 	}

			// Catchall 
		 	else if(str_word_count($string,0) > 4)
		 	{
		 		$returnString = "<field name='format'>" . trim($string) . "</field>\n";
		 	}
		 	else
		 	{
		 		$returnString = "<field name='format_facet'>" . trim($string) . "</field>\n";
		 	}
		}
		else
			$returnString = "";

	 	return $returnString;
	}

	// Current logic:
	// Sets the facet field as any characters preceding the first ",", "(", or "--" in the OAI subject entry
	// Upon inspection of the data entry formats of the OAI-DC docs, this will truncate the entry and focus on the initial term(s) of the entry as a possible facet 
	// The entire entry will be stored as the subject field to preserve all input data.  
	function setSubjectFields($string) {

		$string = strtolower($string);
	 	$string = trim($string, ".,");
	 	$returnString = "";
	 	$tempString = "";

	 	// In the OAI docs, the DC subject data do not contain numeric chars (dates)
	 	// Names that have been inputted as DC subject data will not be used as subject facets until further notice.
	 	// From this point, anything with a numeric value will not be considered a facet field.  
	 	// This will also exclude names from the facet field (they have been input to the DC subject field containing dates)
		if(preg_match('#[0-9]#',$string) === false)
		{ 
		    for($i=0; $i<strlen($string); $i++) 
			{ 
			    if($string[$i] == "," || $string[$i] == "-" || $string[$i] == "(" || $string[$i] == ".") 
			    {
			    	break;
			    }
			    else
			    {
			    	$tempString .= $string[$i];
			    }
			} 
		} 

		$tempString = trim($tempString);

		// Make it a facet field
		if($tempString != "")
		{
			$returnString .= "<field name='subject_facet'>" . $tempString . "</field>\n";
		}
		// Remove unwanted chars and make it a standard subject field
		else
		{
			$string = str_replace('--', ': ', $string);
			$string = str_replace(' -- ', ': ', $string); // Doesn't work
			$string = preg_replace('/\s\s+/', ' ', $string); // Reduce any double whitespaces to one.  

			$returnString .= "<field name='subject'>" . $string . "</field>\n";
		}

	 	return $returnString;
	}

}

