<?php

/*
 * @todo Fix the MetaDataCountry in the extra tags
 */
class MetaTagsContentControllerEXT extends Extension {

	/* combined files */

	protected static $folder_for_combined_files = 'assets';
		static function set_folder_for_combined_files($s) {self::$folder_for_combined_files = $s;}

	protected static $combine_css_files_into_one = false;
		static function set_combine_css_files_into_one($b) {self::$combine_css_files_into_one = $b;}

	protected static $combine_js_files_into_one = false;
		static function set_combine_js_files_into_one($b) {self::$combine_js_files_into_one = $b;}

	/**
	 * add all the basic js and css files - call from Page::init()
	 */
	private static $metatags_building_completed = false;

	function addBasicMetatagRequirements($additionalJS = array(), $additionalCSS = array(), $force = false) {
		if($force) {
			self::$metatags_building_completed = false;
		}
		if(!self::$metatags_building_completed) {
			$themeFolder = SSViewer::get_theme_folder() . '/';
			$cssArrayLocationOnly = array();
			$jsArray =
				array(
					THIRDPARTY_DIR."/jquery/jquery.js",
					$this->owner->project().'/javascript/j.js'
				);
			array_merge($jsArray, $additionalJS);
			$cssArray =
				array(
					array("media" => null, "location" => $themeFolder.'css/reset.css'),
					array("media" => null, "location" => $themeFolder.'css/typography.css'),
					array("media" => null, "location" => $themeFolder.'css/layout.css'),
					array("media" => null, "location" => $themeFolder.'css/form.css'),
					array("media" => null, "location" => $themeFolder.'css/menu.css'),
					array("media" => "print", "location" => $themeFolder.'css/print.css'),
					array("media" => null, "location" => $themeFolder.'css/individualPages.css')
				);
			array_merge($cssArray, $additionalCSS);
			foreach($jsArray as $js) {
				Requirements::javascript($js);
			}
			foreach($cssArray as $cssArraySub) {
				Requirements::css($cssArraySub["location"], $cssArraySub["media"]);
				$cssArrayLocationOnly[] = $cssArraySub["location"];
			}
			if(self::$combine_css_files_into_one) {
				Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomation.css",$cssArrayLocationOnly);
			}
			if(self::$combine_js_files_into_one) {
				Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomation.js", $jsArray);
			}
			$googleFontArray = MetaTagsSTE::get_google_font_collection();
			if($googleFontArray && count($googleFontArray)) {
				$protocol = Director::protocol();
				foreach($googleFontArray as $font) {
					Requirements::insertHeadTags('<link href="' . $protocol . 'fonts.googleapis.com/css?family=' . urlencode($font) . '" rel="stylesheet" type="text/css" />');
				}
			}
			Requirements::insertHeadTags('<meta http-equiv="X-UA-Compatible" content="ie=edge,chrome=1" />', 'use-ie-edge');
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
		$title = $page->MetaTitle ? $page->MetaTitle : $page->Title;
		//base tag
		$base = Director::absoluteBaseURL();
		$tags .= "<base href=\"$base\" />";
		if(! MetaTagsSTE::$hide_keywords_altogether) {
			$keywords = Convert::raw2xml(($page->MetaKeywords) ? $page->MetaKeywords : $page->Title );
		}
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
		if(MetaTagsSTE::$use_themed_favicon) {
			$faviconBase .= $themeFolder;
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
			$titleTag
			.'
			<link rel="icon" href="'.$faviconBase.'favicon.ico" type="image/x-icon" />
			<link rel="apple-touch-icon" href="'.$faviconBase.'apple-touch-icon.png" type="image/x-icon" />
			<link rel="shortcut icon" href="'.$faviconBase.'favicon.ico" type="image/x-icon" />';
		if(! MetaTagsSTE::$hide_keywords_altogether) {
			$tags .= '<meta name="keywords" http-equiv="keywords" content="'.Convert::raw2att($keywords).'" />';
		}
		if(!$page->ExtraMeta && $siteConfig->ExtraMeta) {
			$page->ExtraMeta = $siteConfig->ExtraMeta;
		}
		//if(!$siteConfig->MetaDataCountry) {$siteConfig->MetaDataCountry = Geoip::countryCode2name(Geoip::$default_country_code);}
		if(!$siteConfig->MetaDataCopyright) {$siteConfig->MetaDataCopyright = $siteConfig->Title;}
		if($addExtraSearchEngineData) {
			$tags .= '
			<meta name="robots" content="'.$noopd.'all, index, follow" />
			<meta name="googlebot" content="'.$noopd.'all, index, follow" />
			<meta name="copyright" content="'.$siteConfig->MetaDataCopyright.'" />
			<meta name="coding" content="'.$siteConfig->MetaDataCoding.'" />
			<meta name="design" content="'.$siteConfig->MetaDataDesign.'" />
			<meta name="date-modified-yyyymmdd" content="'.$lastEdited->Format("Ymd").'" />
			<meta name="country" content="'.$siteConfig->MetaDataCountry.'" />
			<meta http-equiv="imagetoolbar" content="no" />
			<meta name="viewport" content="width=device-width,initial-scale=1" />
			<meta http-equiv="Content-Language" content="'.i18n::get_locale().'" />
			'.$page->ExtraMeta.
			$description;
		}
		$tags .= $this->OGPBasic();
		return $tags;
	}

	/**
	 * open graph protocol
	 *
	 */
	protected function OGPBasic(){
		$array = array(
			"title" => $this->Title,
			"type" => "website",
			"image" => $this->owner->BaseHref()."themes/main/img/h/apple-touch-icon-144x144-precomposed.png",
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
