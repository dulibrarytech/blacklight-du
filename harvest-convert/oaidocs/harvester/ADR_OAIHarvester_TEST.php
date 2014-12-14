<?php 

require_once('ADR_OAIHarvester.php');

class ADR_OAIHarvester_TEST extends ADR_OAIHarvester {
	
	private $AOHInstance;

	function __construct() {

		//$this->AOHInstance = new ADR_OAIHarvester();
	}

	public function test_getSetCreationDate() {

		return $this->getSetCreationDate("codu_59239") . "\n";

		// Use this if no access to ADR:
		/*$xmlStr = '<?xml version="1.0" encoding="UTF-8"?>
			<foxml:digitalObject VERSION="1.1" PID="codu:59239"
			xmlns:foxml="info:fedora/fedora-system:def/foxml#"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="info:fedora/fedora-system:def/foxml# http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
			<foxml:objectProperties>
			<foxml:property NAME="info:fedora/fedora-system:def/model#state" VALUE="Active"/>
			<foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="&apos;Sets In Order: The Magazine of Square Dancing&apos; and &apos;Square Dancing&apos;"/>
			<foxml:property NAME="info:fedora/fedora-system:def/model#ownerId" VALUE=""/>
			<foxml:property NAME="info:fedora/fedora-system:def/model#createdDate" VALUE="2012-03-02T15:57:18.495Z"/>
			<foxml:property NAME="info:fedora/fedora-system:def/view#lastModifiedDate" VALUE="2012-10-18T16:33:37.952Z"/>
			</foxml:objectProperties></foxml:digitalObject>';*/
	}

	public function test_getSetList() {

		$arr = $this->getSetList();
		foreach($arr as $elt)
		{
			echo $elt . "\n";
		}
	}

	public function test_writeRecordSetSectionsToFiles() {

		$fromDate = "2012-03-02T15:57:18";
		$setPid = "codu_59239";
		return $this->writeRecordSetSectionsToFiles($setPid,$fromDate,"");
	}

	public function test_getMidrangeDate() {

		//return $this->getMidrangeDate('2012-03-01T00:00:00', '2012-03-31T23:59:59') . "\n";

		$from = "2012-07-01T07:09:53Z";
		$to = "2012-07-15T07:09:53Z";

		// echo "From: " . $from . "; " . "To: " . $to . "\n";
		// $to = $this->getMidrangeDate($from,$to);
		// echo "From: " . $from . "; " . "To: " . $to . "\n";
		// $to = $this->getMidrangeDate($from,$to);
		// echo "From: " . $from . "; " . "To: " . $to . "\n";
		// $to = $this->getMidrangeDate($from,$to);
		// echo "From: " . $from . "; " . "To: " . $to . "\n";
		// $to = $this->getMidrangeDate($from,$to);
		// echo "From: " . $from . "; " . "To: " . $to . "\n";
		// $to = $this->getMidrangeDate($from,$to);
		// echo "From: " . $from . "; " . "To: " . $to . "\n";
		// $to = $this->getMidrangeDate($from,$to);
		// echo "From: " . $from . "; " . "To: " . $to . "\n";
		// $to = $this->getMidrangeDate($from,$to);
		echo "From: " . $from . "; " . "To: " . $to . "\n";
		$to = $this->getMidrangeDate($from,$to);
		echo $to . "\n";
		
	}

	public function test_getHalfDateTimeInterval() {

		$from = new DateTime('2012-07-03T07:09:53Z');
		$to = new DateTime('2012-07-04T07:09:53Z');
		
		$dateInterval = $from->diff($to);
		$halfInterval = $this->getHalfDateTimeInterval($dateInterval);

		return print_r(var_dump($halfInterval),true);
	}
}


echo "ADR_OAIHarvester test:\n";
$test = new ADR_OAIHarvester_TEST();

// echo "Test getMidrangeDate():\n";
// echo $test->test_getMidrangeDate();

// echo "Test getSetCreationDate():\n";
// echo $test->test_getSetCreationDate() === false ? "faalse\n" : $test->test_getSetCreationDate();

// echo "Test getSetList():\n";
// $test->test_getSetList();

echo "Test getMidrangeDate():\n";
echo $test->test_getMidrangeDate();

// echo "Test writeRecordSetSectionsToFiles():\n";
// echo $test->test_writeRecordSetSectionsToFiles();

// echo "Test getHalfDateTimeInterval():\n";
// echo $test->test_getHalfDateTimeInterval();

?>