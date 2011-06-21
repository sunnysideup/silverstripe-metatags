<?php

class MetaTagsLinks extends DataObject {

		//database
	public static $db = array(
		"WebsiteAddress" => "Varchar(255)",
		"SpecificPageAddress" => "Text",
		"UsernameUsed" => "Varchar(100)",
		"PageRank" => "Int",
		"LastChecked" => "Date",
		"Notes" => "Text"
	);
	public static $searchable_fields = array(
		"WebsiteAddress" => "PartialMatchFilter"
	);
	public static $field_labels = array(
		"WebsiteAddress" => "WebsiteAddress (e.g. http://www.thedirectory.com/)",
		"SpecificPageAddress" => "SpecificPageAddress (e.g. http://www.thedirectory.com/ourlisting/",
		"PageRank" => "Page Rank (as assigned by google - higher page ranked sites are more important)",
	);
	public static $summary_fields = array(
		"WebsiteAddress" => "WebsiteAddress"
	);

	public static $singular_name = "Referring Site";

	public static $plural_name = "Referring Sites";

	public static $default_sort = "Created DESC";

	public function onBeforeWrite(){
		parent::onBeforeWrite();
		if(!$this->LastChecked) {
			$this->LastChecked = $this->Created;
		}
		$this->PageRank = @$this->pagerank();
	}

	private function genhash ($url) {
		$hash = 'Mining PageRank is AGAINST GOOGLE\'S TERMS OF SERVICE. Yes, I\'m talking to you, scammer.';
		$c = 16909125;
		$length = strlen($url);
		$hashpieces = str_split($hash);
		$urlpieces = str_split($url);
		for ($d = 0; $d < $length; $d++) {
			$c = $c ^ (ord($hashpieces[$d]) ^ ord($urlpieces[$d]));
			$c = $this->zerofill($c, 23) | $c << 9;
		}
		return '8' . $this->hexencode($c);
	}

	private function zerofill($a, $b) {
		$z = hexdec(80000000);
			if ($z & $a) {
				$a = ($a>>1);
			$a &= (~$z);
			$a |= 0x40000000;
			$a = ($a>>($b-1));
		} else {
			$a = ($a>>$b);
		}
		return $a;
	}

	private function hexencode($str) {
		$out  = $this->hex8($this->zerofill($str, 24));
		$out .= $this->hex8($this->zerofill($str, 16) & 255);
		$out .= $this->hex8($this->zerofill($str, 8 ) & 255);
		$out .= $this->hex8($str & 255);
		return $out;
	}

	private function hex8 ($str) {
		$str = dechex($str);
		(strlen($str) == 1 ? $str = '0' . $str: null);
		return $str;
	}

	private function pagerank($url) {
		$googleurl = 'http://toolbarqueries.google.com/search?features=Rank&sourceid=navclient-ff&client=navclient-auto-ff&googleip=O;66.249.81.104;104&ch=' . $this->genhash($url) . '&q=info:' . urlencode($url);
		if(function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $googleurl);
			$out = curl_exec($ch);
			curl_close($ch);
		}
		else {
			$out = file_get_contents($googleurl);
		}
		return substr($out, 9);
	}



}
