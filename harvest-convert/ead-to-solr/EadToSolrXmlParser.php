<?php

/* OAI:DC-to-Solr Parser Class
 * 
 * Author: 		Jeff Rynhart jeff.rynhart@du.edu
 * Description: Convert EAD xml files to Solr index input format xml files. 
 * Input: 		EAD xml file(s) from current directory. 
 *				Filename format: 'codu_#####.xml'
 * Output: 		Solr-compatible index .xml file(s) 
 *
 * University of Denver, University Libraries, 5/2014 */

/* Notes:
 * 
 * At this point, the parser is set up for one 'archdesc' node (a single archive per file)
 * If the file contains more than one archdesc, only the first one will be converted.
 *
 * Run parseEad in the folder containing the ead.xml files that are to be converted.*/

class EadToSolrXmlParser {

	protected $outputFolder = "ead-output/";	// include trailing slash; "" for local folder
	protected $solr_indexStr = "";

	protected $XTF_URL = "http://digital.library.du.edu/findingaids/view?docId=ead/"; 

	public function parseEad() {

		echo "Parsing EAD files...\n";

		// Only parses *.xml files
		$files = scandir('.');

		// Main parser loop
		foreach($files as $file)
		{
			if(substr($file, -4) === '.xml')
			{
				// Add document tag
				$this->solr_indexStr = "<add>\n";
				$this->solr_indexStr .= "<doc>\n";

				// Convert xml file to string
				$xmlString = file_get_contents($file);
				$xmlObj = simplexml_load_string($xmlString);

				if($xmlObj == null)
		            continue;

		        // Get title
		        $node = $xmlObj->xpath('archdesc/did/unittitle');
		        $tempTitle = $node[0];
		        $node = $xmlObj->xpath('archdesc/did/unitdate');
		        $tempDate = $node[0];
		        $this->solr_indexStr .= "<field name='title'>" . $tempTitle . ", " . $tempDate . "</field>\n";

		        // Get creator
		        $node = $xmlObj->xpath('archdesc/did/origination/persname');
		        if(count($node) > 0)
		        	$this->solr_indexStr .= "<field name='creator'>" . trim($node[0]) . "</field>\n";

		        // Get subject
		        $nodes = $xmlObj->xpath('archdesc/controlaccess/controlaccess/subject');
		        if(count($nodes) === 0)
		        {
		        	$nodes = $xmlObj->xpath('archdesc/controlaccess/controlaccess/corpname');
		        	if(count($nodes) === 0)
		        	{
		        		$nodes = $xmlObj->xpath('archdesc/controlaccess/controlaccess/persname');
			        	if(count($nodes) === 0)
			        	{
			        		$nodes = $xmlObj->xpath('archdesc/controlaccess/controlaccess/geogname');
			        	}
		        	}
		        }
		        foreach($nodes as $node)
		        {
		        	if($node != "")
		        	{
		        		// Standardize delimiters
		        		$node = str_replace("__", "--", $node);
		        		$node = str_replace(";", "--", $node);

		        		// Explode subjects on expected delimiter
		        		if(stripos($node, "--"))
		        		{
		        			$subjects = explode("--", $node);
		        			foreach($subjects as $subject)
		        			{
		        				if(!preg_match('#[0-9]#',$subject))
		        				{
		        					$this->solr_indexStr .= "<field name='subject_facet'>" . trim($subject) . "</field>\n";
		        				}
		        			}

		        			$node = str_replace("--", ", ", $node);
		        			$this->solr_indexStr .= "<field name='subject'>" . trim($node) . "</field>\n";
		        		}
		        		else
		        		{
		        			$this->solr_indexStr .= "<field name='subject'>" . trim($node) . "</field>\n";

		        			if(!preg_match('#[0-9]#',$node))
		        				$this->solr_indexStr .= "<field name='subject_facet'>" . trim($node) . "</field>\n";
		        		}
		        	}
		        }	

		        // Get description
		        $node = $xmlObj->xpath('archdesc/abstract');
		        if(count($node) > 0)
		        	$this->solr_indexStr .= "<field name='abstract'>" . trim($node[0]) . "</field>\n";
		        else 
		        {
		        	$node = $xmlObj->xpath('archdesc/did/abstract');
		        	if(count($node) > 0)
		        		$this->solr_indexStr .= "<field name='abstract'>" . trim($node[0]) . "</field>\n";
		        	else
		        	{
		        		$node = $xmlObj->xpath('archdesc/scopecontent/p');
		        		if(count($node) > 0)
		        			$this->solr_indexStr .= "<field name='abstract'>" . trim($node[0]) . "</field>\n";
		        	}
		        }

		        // Get Publisher
		        $node = $xmlObj->xpath('archdesc/did/repository/corpname');
		        if(count($node) > 0)
		        	$this->solr_indexStr .= "<field name='publisher'>" . trim($node[0]) . "</field>\n";

		        // Add type
		        $this->solr_indexStr .=  "<field name='type_facet'>Archival Resource</field>\n";

		        // Create link to XTF
		        $this->solr_indexStr .= "<field name='links'>" . $this->XTF_URL . $file . "</field>\n";

		        // Use filename for unique id
		        $this->solr_indexStr .= "<field name='id'>" . substr($file, 0, -4) . "</field>\n";

				// Close document
				$this->solr_indexStr .= "</doc>\n";
				$this->solr_indexStr .= "</add>\n";

				// Remove unwanted chars for consistancy
        		$this->solr_indexStr = str_replace(".<", "<", $this->solr_indexStr); // Remove trailing period
        		$this->solr_indexStr = str_replace(",<", "<", $this->solr_indexStr); // remove trailing comma

				// Convert ampersands as they will not be ingested by solr.  
		        $this->solr_indexStr = str_replace('&', 'and', $this->solr_indexStr);

				// Create filename and write it
				$file = $this->outputFolder . substr($file, 0, -4) . "_SOLR.xml";
				if($this->writeToFile($file))
					echo "Wrote " . $file . ".\n";
				else
					echo "Error writing " . $file . ".\n";
			}
		}
	}

	protected function writeToFile($file) {

		if(!file_exists($this->outputFolder))
			mkdir($this->outputFolder,0775);

		$fp = fopen($file, 'w');
		
		if (!$fp) {
			throw new Exception("Cannot open file.");
		}

		$status = fwrite($fp, $this->solr_indexStr);
		fclose($fp);

		return $status;
	}	
}














?>