<?php

/**
*
* PLEASE USE MetaTagAutomation - this is only here to legacy reasons!
* @TODO: make this an extension of MetatagAutomation and only leave the bare necesitities
*/



class MetaTagger extends DataObjectDecorator {
	static $country = "New Zealand";
	static $copyright = 'owner';
	static $design = '';
	static $project = 'mysite';
	static $coding = "";

	static $theme_folder = '';

	static $combine_files_in_one_file = false;

	static function set_theme_folder($folderName) {
		MetaTagger::$theme_folder = $folderName;
	}

	static function set_combine_files_in_one_file($trueOrFalse) {
		MetaTagger::$combine_files_in_one_file = $trueOrFalse;
	}
}

class MetaTagger_controller extends Extension {

	static $allowed_actions = array(
		"starttestforie",
		"stoptestforie"
	);



	function addRequirements() {
		if(!MetaTagger::$theme_folder) {
			MetaTagger::$theme_folder = $this->owner->ThemeDir().'/';
		}
		$jsArray =
			array(
				THIRDPARTY_DIR."/jquery/jquery.js",
				'mysite/javascript/j.js'
			);
		$cssArray =
			array(
				MetaTagger::$theme_folder.'css/reset.css',
				MetaTagger::$theme_folder.'css/layout.css',
				MetaTagger::$theme_folder.'css/typography.css',
				MetaTagger::$theme_folder.'css/form.css',
				MetaTagger::$theme_folder.'css/menu.css',
				MetaTagger::$theme_folder.'css/print.css'
			);
		$prototypeArray =
			array(
				"sapphire/javascript/Validator.js",
				THIRDPARTY_DIR."/prototype.js",
				THIRDPARTY_DIR."/behaviour.js",
				THIRDPARTY_DIR."/prototype_improvements.js"
			);

		foreach($jsArray as $js) {
			Requirements::javascript($js);
		}
		foreach($cssArray as $css) {
			Requirements::css($css);
		}
		if(MetaTagger::$combine_files_in_one_file) {
			Requirements::combine_files(MetaTagger::$theme_folder."css/MainCombination.css",$cssArray);
			Requirements::combine_files("mysite/javascript/MainCombination.js", $jsArray);
			Requirements::combine_files("mysite/javascript/SapphirePrototypeCombination.js", $prototypeArray);
		}
		if(Session::get("testforie") > 0) {
			Requirements::insertHeadTags('<style type="text/css">@import url('.MetaTagger::$theme_folder.'css/ie'.Session::get("testforie").'.css);</style>');
		}
		else {
			Requirements::insertHeadTags('<!--[if IE 6]><style type="text/css">@import url('.MetaTagger::$theme_folder.'css/ie6.css);</style><![endif]-->');
			Requirements::insertHeadTags('<!--[if IE 7]><style type="text/css">@import url('.MetaTagger::$theme_folder.'css/ie7.css);</style><![endif]-->');
			Requirements::insertHeadTags('<!--[if IE 8]><style type="text/css">@import url('.MetaTagger::$theme_folder.'css/ie8.css);</style><![endif]-->');
		}
	}


	function starttestforie() {
		Session::set("testforie", Director::urlParam("ID"));
		Requirements::customScript('alert("starting test for IE'.Session::get("testforie").' - to stop go to '.$this->owner->URLSegment.'/stoptestforie");');
		return array();
	}

	function stoptestforie() {
		Requirements::customScript('alert("stopped test for IE'.Session::get("testforie").' - to start go to '.$this->owner->URLSegment.'/starttestforie");');
		Session::set("testforie", 0);
		return array();
	}

	/* need to work out how this can be called from the content_controller */
	function handleAction(HTTPRequest $request) {
		if(7 == Session::get("testforie")) {
			$request->addHeader('X-UA-Compatible', 'IE=EmulateIE7');
		}
		return parent::handleAction($request);
	}

	function MetaTagsSunnySideUp($includeTitle = true) {

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
			<meta http-equiv="Content-type" content="text/html; charset=utf-8" />'.
			($includeTitle ? '<title>'.$title.'</title>' : '').'
			<meta name="robots" content="'.$noopd.'all, index, follow" />
			<meta name="googlebot" content="'.$noopd.'all, index, follow" />
			<meta name="keywords" http-equiv="keywords" content="'.Convert::raw2att($keywords).'" />'.$description.'
			<meta name="copyright" content="'.MetaTagger::$copyright.'" />
			<meta name="coding" content="'.MetaTagger::$coding.'" />
			<meta name="design" content="'.MetaTagger::$design.'" />
			<meta name="date-modified-yyyymmdd" content="'.Date("Ymd").'" />
			<meta name="country" content="'.MetaTagger::$country.'" />
			<meta http-equiv="imagetoolbar" content="no" />
			'.$page->ExtraMeta.'
			<link rel="icon" href="/favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />';
		return $tags;
	}



}
