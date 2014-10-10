<?php

/*
 * @todo Fix the MetaDataCountry in the extra tags
 */
class MetaTagsContentControllerEXT extends Extension {

	/**
	 * @var String
	 * folder where the combined css / js files will be stored
	 * if they are combined.
	 */
	private static $folder_for_combined_files = 'assets';

	/**
	 * @var String
	 * viewport setting
	 */
	private static $viewport_setting = 'width=device-width,initial-scale=1';


	/**
	 * google fonts to be used
	 * @var Array
	 **/
	private static $google_font_collection = array();

	/**
	 * should we use a favicon in the theme?
	 * @var Boolean
	 **/
	private static $use_themed_favicon = false;

	/**
	 * combine css files into one?
	 * @var Boolean
	 */
	private static $combine_css_files_into_one = false;

	/**
	 * combine js files into one?
	 * @var Boolean
	 */
	private static $combine_js_files_into_one = false;

	/**
	 * add all the basic js and css files - call from Page::init()
	 */
	private static $metatags_building_completed = false;

	public function addBasicMetatagRequirements($additionalJS = array(), $additionalCSS = array(), $force = false) {
		if($force) {
			self::$metatags_building_completed = false;
		}
		if(!self::$metatags_building_completed) {
			$themeFolder = SSViewer::get_theme_folder();
			$cssArrayLocationOnly = array();
			$jsArray =
				array(
					THIRDPARTY_DIR."/jquery/jquery.js",
					$this->owner->project().'/javascript/j.js'
				);
			array_merge($jsArray, $additionalJS);
			$cssArray =
				array(
					array("media" => null, "location" => $themeFolder.'/css/reset.css'),
					array("media" => null, "location" => $themeFolder.'/css/typography.css'),
					array("media" => null, "location" => $themeFolder.'/css/layout.css'),
					array("media" => null, "location" => $themeFolder.'/css/form.css'),
					array("media" => null, "location" => $themeFolder.'/css/menu.css'),
					array("media" => "print", "location" => $themeFolder.'/css/print.css'),
					array("media" => null, "location" => $themeFolder.'/css/individualPages.css')
				);
			array_merge($cssArray, $additionalCSS);
			foreach($jsArray as $js) {
				Requirements::javascript($js);
			}
			foreach($cssArray as $cssArraySub) {
				Requirements::css($cssArraySub["location"], $cssArraySub["media"]);
				$cssArrayLocationOnly[] = $cssArraySub["location"];
			}
			$folderForCombinedFiles = Config::inst()->get("MetaTagsContentControllerEXT", "folder_for_combined_files");
			if(Config::inst()->get("MetaTagsContentControllerEXT", "combine_css_files_into_one")) {
				Requirements::combine_files($folderForCombinedFiles."/MetaTagAutomation.css",$cssArrayLocationOnly);
			}
			if(Config::inst()->get("MetaTagsContentControllerEXT", "combine_js_files_into_one")) {
				Requirements::combine_files($folderForCombinedFiles."/MetaTagAutomation.js", $jsArray);
			}
			$googleFontArray = Config::inst()->get('MetaTagsContentControllerEXT', 'google_font_collection');
			if($googleFontArray && count($googleFontArray)) {
				$protocol = Director::protocol();
				foreach($googleFontArray as $font) {
					Requirements::insertHeadTags('<link href="' . $protocol . 'fonts.googleapis.com/css?family=' . urlencode($font) . '" rel="stylesheet" type="text/css" />');
				}
			}
			if (isset($_SERVER['HTTP_USER_AGENT']) &&  (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
				header('X-UA-Compatible: IE=edge,chrome=1');
			}
			self::$metatags_building_completed = true;
		}
	}


	/**
	 * this function will add more metatags to your template -
	 * make sure to add it at the start of your metatags
	 * We leave the / closing tags here, but they are not needed
	 * yet not invalid in html5
	 * @param Boolean $includeTitle - include the title tag
	 * @param Boolean $addExtraSearchEngineData - add extra tags describing the page
	 * @return String (HTML)
	 */
	function ExtendedMetatags($includeTitle = true, $addExtraSearchEngineData = true) {
		$this->addBasicMetatagRequirements();
		$themeFolder = SSViewer::get_theme_folder() . '/';
		$tags = "";
		$page = $this->owner;
		$siteConfig = SiteConfig::current_site_config();
		$title = $page->Title;
		if(!$title) {
			$title = $page->MenuTitle;
		}
		//base tag
		$base = Director::absoluteBaseURL();
		$tags .= "<base href=\"$base\" />";
		if($page->MetaDescription) {
			$description = '
			<meta name="description" http-equiv="description" content="'.Convert::raw2att($page->MetaDescription).'" />';
			$noopd = '';
		}
		else {
			$noopd = "NOODP, ";
			$description = '';
		}
		$lastEdited = new SS_Datetime();
		$lastEdited->value = $page->LastEdited;

		//use base url rather than / so that sites that aren't a run from the root directory can have a favicon
		$faviconBase = $base;
		$faviconFileBase = "";
		if(Config::inst()->get("MetaTagsContentControllerEXT", "use_themed_favicon")) {
			$faviconBase .= $themeFolder;
			$faviconFileBase = $themeFolder;
		}
		if($includeTitle) {
			$titleTag = '
			<title>'.trim(Convert::raw2att($siteConfig->PrependToMetaTitle.' '.$title.' '.$siteConfig->AppendToMetaTitle)).'</title>';
		}
		else {
			$titleTag = '';
		}
		$tags .= '
			<meta charset="utf-8" />
			<meta http-equiv="Content-type" content="text/html; charset=utf-8" />'.
			$titleTag;
		if(file_exists(Director::baseFolder().'/'.$faviconFileBase.'favicon.ico')) {
			$tags .= '
			<link rel="icon" href="'.$faviconBase.'favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="'.$faviconBase.'favicon.ico" type="image/x-icon" />';
		}
		if(file_exists(Director::baseFolder().'/'.$faviconFileBase.'apple-touch-icon.png')) {
			$tags .= '
			<link rel="apple-touch-icon" href="'.$faviconBase.'apple-touch-icon.png" type="image/x-icon" />';
		}
		//if(! Config::inst()->get("MetaTagsSTE", "hide_keywords_altogether")) {
			//$tags .= '<meta name="keywords" http-equiv="keywords" content="'.Convert::raw2att($keywords).'" />';
		//}
		if(!$page->ExtraMeta && $siteConfig->ExtraMeta) {
			$page->ExtraMeta = $siteConfig->ExtraMeta;
		}
		//if(!$siteConfig->MetaDataCountry) {$siteConfig->MetaDataCountry = Geoip::countryCode2name(Geoip::$default_country_code);}
		if(!$siteConfig->MetaDataCopyright) {$siteConfig->MetaDataCopyright = $siteConfig->Title;}
		if($addExtraSearchEngineData) {
			$tags .= '
			<meta name="robots" content="'.$noopd.'all, index, follow" />
			<meta name="googlebot" content="'.$noopd.'all, index, follow" />
			<meta name="rights" content="'.Convert::raw2att($siteConfig->MetaDataCopyright).'" />
			<meta name="created" content="'.$lastEdited->Format("Ymd").'" />
			<meta name="geo.country" content="'.$siteConfig->MetaDataCountry.'" />
			<meta http-equiv="imagetoolbar" content="no" />
			<meta name="viewport" content="'.Config::inst()->get("MetaTagsContentControllerEXT", "viewport_setting").'" />
			<meta http-equiv="Content-Language" content="'.i18n::get_locale().'" />
			'.$page->ExtraMeta.
			$description;
		}
		$tags .= $this->owner->OGTags();
		return $tags;
	}

	/**
	 * open graph protocol
	 * @see: http://ogp.me/
	 * @return String (HTML)
	 */
	public function OGTags(){
		$array = array(
			"title" => $this->owner->Title,
			"type" => "website",
			//"image" => $this->owner->BaseHref()."themes/main/img/h/apple-touch-icon-144x144-precomposed.png",
			"url" => $this->owner->AbsoluteLink(),
			"site_name" => $this->owner->SiteConfig()->Title,
			"description" => $this->owner->Title
		);
		$html = "";
		foreach($array as $key => $value){
			$html .= "
			<meta property=\"og:$key\" content=\"$value\" />";
		}
		return $html;
	}
}
