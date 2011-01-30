<?php
	class SevenDigitalApiQueryBuilder {
		public $BaseUrl = "http://api.7digital.com/1.2/";
		private $url = "";
		private $query = "";		
							
		function __construct($parameters, $apiSwitch) {
			$this->parameters = $parameters;
			$this->apiSwitch = $apiSwitch;			
		}
		
		function OutputApiUrl() {		
			$url = $this->BaseUrl.$this->apiSwitch;
			$i = 0;
			foreach($this->parameters as $key => $value) {
				if($i == 0) {
					$url .= "?";
				}
				$url.= urlencode($key)."=".urlencode($value);
				if($i < sizeof($this->parameters)) {
					$url .= "&";
				}
				$i++;
			}
			return $url;
		}
	}
?>