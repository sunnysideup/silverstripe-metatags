<?php

class MetaTagger extends DataObjectDecorator {

	static $country = "New Zealand";
	static $copyright = 'owner';
	static $design = '';
	static $project = 'mysite';
	static $coding = "";

	static $theme_folder = '';

	static $combine_files_in_one_file = false;

	static function set_theme_folder($folderName) {
		self::$theme_folder = $folderName;
	}

	static function set_combine_files_in_one_file($value) {
		self::$combine_files_in_one_file = $value;
	}

	function addRequirements() {
		if(!self::$theme_folder) {
			self::$theme_folder = $this->owner->ThemeDir().'/';
		}
		$jsArray =
			array(
				"jsparty/jquery/jquery.js",
				'mysite/javascript/j.js'
			);
		$cssArray =
			array(
				self::$theme_folder.'css/reset.css',
				self::$theme_folder.'css/layout.css',
				self::$theme_folder.'css/typography.css',
				self::$theme_folder.'css/form.css',
				self::$theme_folder.'css/menu.css',
				self::$theme_folder.'css/print.css'
			);
		if(Session::set("testforiesix")) {
			$cssArray[] = self::$theme_folder.'css/ie6.css';
		}
		$prototypeArray =
			array(
				"sapphire/javascript/Validator.js",
				"jsparty/prototype.js",
				"jsparty/behaviour.js",
				"jsparty/prototype_improvements.js"
			);

		foreach($jsArray as $js) {
			Requirements::javascript($js);
		}
		foreach($cssArray as $css) {
			Requirements::css($css);
		}
		if(self::$combine_files_in_one_file) {
			Requirements::combine_files(self::$theme_folder."css/MainCombination.css",$cssArray);
			Requirements::combine_files("mysite/javascript/MainCombination.js", $jsArray);
			Requirements::combine_files("mysite/javascript/SapphirePrototypeCombination.js", $prototypeArray);
		}
	}

	function starttestforiesix() {
		Session::set("testforiesix", true);
	}

	function stoptestforiesix() {
		Session::set("testforiesix", false);
	}

	function MetaTagsSunnySideUp() {

		//$themeFolderAndSubfolder = "----";
		$tags = "";
		$page = $this->owner;
		$title = Convert::raw2xml(($page->MetaTitle) ? $page->MetaTitle : $page->Title );
		$keywords = Convert::raw2xml(($page->MetaKeywords) ? $page->MetaKeywords : $page->Title );
		if($page->MetaDescription) {
		 $description = '
			<meta name="description" http-equiv="description" content="'.Convert::raw2att($page->MetaDescription).'" />';
		 $noopd = '';
		}
		else {
		 $noopd = "NOODP, ";
		 $description = '';
		}
		$tags .= '
			<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
			<title>'.$title.'</title>
			<meta name="robots" content="'.$noopd.'all, index, follow" />
			<meta name="googlebot" content="'.$noopd.'all, index, follow" />
			<meta name="keywords" http-equiv="keywords" content="'.Convert::raw2att($keywords).'" />'.$description.'
			<meta name="copyright" content="'.self::$copyright.'" />
			<meta name="coding" content="'.self::$coding.'" />
			<meta name="design" content="'.self::$design.'" />
			<meta name="date-modified-yyyymmdd" content="'.Date("Ymd").'" />
			<meta name="country" content="'.self::$country.'" />
			<meta http-equiv="imagetoolbar" content="no" />
			<link rel="icon" href="/favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
			<!--[if IE 6]><style type="text/css">@import url('.self::$theme_folder.'css/ie6.css);</style><![endif]-->
			<!--[if IE 7]><style type="text/css">@import url('.self::$theme_folder.'css/ie7.css);</style><![endif]-->';
		return $tags;
	}
}
