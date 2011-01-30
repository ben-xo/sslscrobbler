<?php
		require_once("SevenDigitalApiReader.php");
		require_once("SevenDigitalQueryBuilder.php");
		
		class SevenDigitalApi {
			
		private $parameters;
		private $apiSwitch;
		
		public $Api;
		public $Country = "GB";
		public $ReleaseId = 1;
		public $ConsumerId = "";

		public $OutputType = "xml";
		
		function __construct() {
			
		}
			
		private function InitializeQueryBuilder() {
			return new SevenDigitalApiQueryBuilder($this->_parameters, $this->_apiSwitch);
		}
		
		private function OutputBasedOnOutputType() {
			return $this->OutputType == "json" ? $this->Api->TransformXmlToJson() : $this->Api->OutputAsXml() ;
		}		
		
		private function OutputData($apiSwitch, $parameters) {
			$this->_apiSwitch = $apiSwitch;
			$this->_parameters = $parameters;
			
			$queryBuilder = $this->InitializeQueryBuilder($apiSwitch, $parameters);

			$this->Api = new SevenDigitalApiReader($queryBuilder);	
				
			return $this->OutputType == "json" 
					? $this->Api->TransformXmlToJson() 
					: $this->Api->OutputAsXml() ;
		}
			
		// Api methods
		// TODO - There is some code duplication here that needs sorting
		public function GetReleasesById($releaseId) {
			
				$apiSwitch = "release/tracks";
				$parameters = array("releaseId" => $releaseId,
									"oauth_consumer_key" => $this->ConsumerId,
									"country" => $this->Country);
									
				return $this->OutputData($apiSwitch, $parameters);
		}
		
		public function GetReleasesByDate($fromDate, $toDate) {
			
			$apiSwitch = "release/bydate";
				$parameters = array("fromDate" => $fromDate,
									"toDate" => $toDate,
									"oauth_consumer_key" => $this->ConsumerId,
									"country" => $this->Country);
									
				return $this->OutputData($apiSwitch, $parameters);
		}
		
		public function GetTrackDetailsById($trackId) {
				$apiSwitch = "track/details";
				$parameters = array("trackid" => $trackId,
									"oauth_consumer_key" => $this->ConsumerId,
									"country" => $this->Country);
									
				return $this->OutputData($apiSwitch, $parameters);
		}
		
		public function GetTracksByTitle($query) {
				$apiSwitch = "track/search";
				$parameters = array("q" => $query,
									"oauth_consumer_key" => $this->ConsumerId,
									"country" => $this->Country);
									
				return $this->OutputData($apiSwitch, $parameters);
		}		
		
	}
?>