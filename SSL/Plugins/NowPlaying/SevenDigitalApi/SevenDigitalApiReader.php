<?php
	require_once("xml2json.php");
	
	class SevenDigitalApiReader{
		public $queryBuilder;
		private $xml= "";
		private $xslFile = "7digital.xsl";	
		public $Status = "OK";
						
		function __construct($queryBuilder) {		
			$this->queryBuilder = $queryBuilder;
			$this->FireLink();
		}							
		
		private function FireLink() {
		    $this->xml = file_get_contents($this->queryBuilder->OutputApiUrl());
		    // TODO - Error can currently be returned withi xml / JSON
			// or passed as a Status property of this class
			// Should really read the xml for the errormessage and pass that to statusmessage?
			if(strpos($this->xml, "errorMessage")){
				$this->Status = "Error";				
			}
		}
				
		function PerformXslTransform() {
			$xmlObject = new DOMDocument();
			$xmlObject->loadXml ($this->xml);
		
		
			$xslt = new XSLTProcessor(); 
			$xsl = new DOMDocument(); 
			$xsl->load( $this->xslFile, LIBXML_NOCDATA); 
			$xslt->importStylesheet( $xsl ); 
			
			// TODO - Need to pass back xsl styled xml as string, currently does not work
		}
		
		function OutputAsXml() {
			return $this->xml;			
		}
		
		function TransformXmlToJson() {
			$jsonContents = xml2json::transformXmlStringToJson($this->xml);
			
			return $jsonContents;
		}			
	}
?>