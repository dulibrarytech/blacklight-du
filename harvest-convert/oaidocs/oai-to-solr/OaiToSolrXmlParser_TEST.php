<?php 

require_once('OaiToSolrXmlParser.php');

class OaiToSolrXmlParser_TEST extends OaiToSolrXmlParser {

	public function test_stringContainsExcludeChars() {

		if($this->stringContainsExcludeChars("This is a bad, bad, string, yes", $this->formatFacetExcludeChars))
			echo "test_stringContainsExcludeChars: Ok\n";
		else
			echo "test_stringContainsExcludeChars: Bad\n";
	}

	public function test_setFormatFields() {

		$retStr = $this->setFormatFields(", , 2 copies, Cover is brown, with paper binding");
		echo $retStr . "\n";
	}

	public function test_setThumnailDataField() {

		echo $this->setThumnailDataField('codu_59240');
	}
}

echo "OaiToSolrXmlParser Tests:\n";
$testInst = new OaiToSolrXmlParser_TEST();
// $testInst->test_stringContainsExcludeChars();
// $testInst->test_setFormatFields();
$testInst->test_setThumnailDataField();

?>