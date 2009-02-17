<?php

class MetaTagger extends DataObjectDecorator {

	static $country = "New Zealand";
	static $copyright = 'owner';
	static $design = '';
	static $project = 'mysite';
	static $coding = "";

	static $themeFolderAndSubfolder = '';

	function addRequirements() {
		$themeDir = $this->owner->ThemeDir().'/';
		$jsArray =
			array(
				"jsparty/jquery/jquery.js",
				'mysite/javascript/j.js'
			);
		$cssArray =
			array(
				$themeDir.'css/reset.css',
				$themeDir.'css/layout.css',
				$themeDir.'css/typography.css',
				$themeDir.'css/form.css',
				$themeDir.'css/menu.css',
				$themeDir.'css/print.css'
			);
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
		Requirements::combine_files($themeDir."css/MainCombination.css",$cssArray);
		Requirements::combine_files("mysite/javascript/MainCombination.js", $jsArray);
		Requirements::combine_files("mysite/javascript/SapphirePrototypeCombination.js", $prototypeArray);

	}

	function MetaTagsSunnySideUp() {

		//$themeFolderAndSubfolder = "----";
		$tags = "";
		$page = $this->owner;
		$title = Convert::raw2xml(($page->MetaTitle) ? $page->MetaTitle : $page->Title );
		$keywords = Convert::raw2xml(($page->MetaKeywords) ? $page->MetaKeywords : $page->Title );
		if($page->MetaDescription) {
		 $description = '
			<meta name="description" http-equiv="description" content="'.Convert::raw2xml($page->MetaDescription).'" />';
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
			<meta name="keywords" http-equiv="keywords" content="'.$keywords.'" />'.$description.'
			<meta name="revisit" content="2 Days" />
			<meta name="revisit-after" content="2 Days" />
			<meta name="copyright" content="'.self::$copyright.'" />
			<meta name="alexa" content="100" />
			<meta name="doc-type" content="Web Page" />
			<meta name="coding" content="'.self::$coding.'" />
			<meta name="design" content="'.self::$design.'" />
			<meta name="date-modified-yyyymmdd" content="'.Date("Ymd").'" />
			<meta name="title" content="'.$title.'" />
			<meta name="country" content="'.self::$country.'" />
			<meta http-equiv="imagetoolbar" content="no" />
			<link rel="icon" href="/favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
			<!--[if IE 6]><style type="text/css">@import url('.self::$themeFolderAndSubfolder.'/css/ie6.css);</style><![endif]-->
			<!--[if IE 7]><style type="text/css">@import url('.self::$themeFolderAndSubfolder.'/css/ie7.css);</style><![endif]-->';
		return $tags;
	}
}
