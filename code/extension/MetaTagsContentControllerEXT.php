<?php

/**
 * adds meta tag functionality to the Page_Controller
 *
 *
 *
 */
class MetaTagsContentControllerEXT extends Extension {

	/**
	 * length of auto-generated meta descriptions in header
	 * @var Boolean
	 */
	private static $use_separate_metatitle = 0;

	/**
	 * length of auto-generated meta descriptions in header
	 * @var Int
	 */
	private static $meta_desc_length = 24;

	/**
	 * what should be included on every page?
	 * @var Array
	 */
	private static $default_css = array(
		'reset' =>  null,
		'typography' => null,
		'layout' => null,
		'form' => null,
		'menu' => null,
		'individualPages' => null,
		'responsive' => null,
		'print' => 'print'
	);

	/**
	 * what should be included on every page?
	 * @var Array
	 */
	private static $default_js = array(
		"framework/thirdparty/jquery/jquery.js",
		"mysite/javascript/j.js"
	);

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
	 * place metatags into a cache....
	 * @var Boolean
	 */
	private static $cache_metatags = true;

	/**
	 * add all the basic js and css files - call from Page::init()
	 * @var Array
	 */
	private static $_metatags_building_completed = array();

	public function addBasicMetatagRequirements($additionalJS = array(), $additionalCSS = array(), $force = false) {
		if($force) {
			unset(self::$_metatags_building_completed[$this->owner->dataRecord->ID]);
		}
		if(!isset(self::$_metatags_building_completed[$this->owner->dataRecord->ID])) {
			$folderForCombinedFiles = Config::inst()->get("MetaTagsContentControllerEXT", "folder_for_combined_files");
			$folderForCombinedFilesWithBase = Director::baseFolder()."/".$folderForCombinedFiles;
			$combineJS = Config::inst()->get("MetaTagsContentControllerEXT", "combine_js_files_into_one");
			$combineCSS = Config::inst()->get("MetaTagsContentControllerEXT", "combine_css_files_into_one");
			$jsFile = $folderForCombinedFiles."/MetaTagAutomation.js";
			$cssFile = $folderForCombinedFiles."/MetaTagAutomation.css";
			//javascript
			if($combineJS && file_exists($folderForCombinedFilesWithBase.$jsFile) ) {
				Requirements::javascript($jsFile);
			}
			else {
				$jsArray = Config::inst()->get("MetaTagsContentControllerEXT", "default_js");
				$jsArray = array_merge($jsArray, $additionalJS);
				foreach($jsArray as $js) {
					Requirements::javascript($js);
				}
				if($combineJS) {
					Requirements::combine_files($jsFile, $jsArray);
				}
			}

			//css
			if($combineCSS && file_exists($folderForCombinedFilesWithBase.$cssFile)) {
				Requirements::css($cssFile);
			}
			else {
				// css
				$themeFolder = SSViewer::get_theme_folder();
				$cssArrayLocationOnly = array();
				foreach( Config::inst()->get("MetaTagsContentControllerEXT", "default_css") as $name => $media) {
					$cssArray[] = array("media" => $media, "location" => $themeFolder.'/css/'.$name.'.css');
				}
				$cssArray = array_merge($cssArray, $additionalCSS);
				foreach($cssArray as $cssArraySub) {
					Requirements::css($cssArraySub["location"], $cssArraySub["media"]);
					$cssArrayLocationOnly[] = $cssArraySub["location"];
				}
				if($combineCSS) {
					Requirements::combine_files($folderForCombinedFiles."/MetaTagAutomation.css",$cssArrayLocationOnly);
				}
			}

			//google font
			$googleFontArray = Config::inst()->get('MetaTagsContentControllerEXT', 'google_font_collection');
			if($googleFontArray && count($googleFontArray)) {
				$protocol = Director::protocol();
				foreach($googleFontArray as $font) {
					Requirements::insertHeadTags('
			<link href="' . $protocol . 'fonts.googleapis.com/css?family=' . urlencode($font) . '" rel="stylesheet" type="text/css" />');
				}
			}

			//ie header...
			if (isset($_SERVER['HTTP_USER_AGENT']) &&  (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
				header('X-UA-Compatible: IE=edge,chrome=1');
			}
			self::$_metatags_building_completed[$this->owner->dataRecord->ID] = true;
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
		$cacheKey = 'metatags_ExtendedMetaTags_'.abs($this->owner->ID);
		$cache = SS_Cache::factory($cacheKey);
		if (!($tags = $cache->load($cacheKey)) || 1 == 1) {
			$themeFolder = SSViewer::get_theme_folder() . '/';
			$tags = "";
			$page = $this->owner;
			$siteConfig = SiteConfig::current_site_config();
			$title = "";
			if(Config::inst()->get("MetaTagsContentControllerEXT", "use_separate_metatitle") == 1) {
				$title = $page->MetaTitle;
			}
			else {
				$title = $page->Title;
				if(!$title) {
					$title = $page->MenuTitle;
				}
			}
			//base tag
			$base = Director::absoluteBaseURL();
			$tags .= "<base href=\"$base\" />";
			if($page->MetaDescription) {
				$description = '
			<meta name="description" content="'.Convert::raw2att($page->MetaDescription).'" />';
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
			if($includeTitle) {
				$titleTag = '
			<title>'.trim(Convert::raw2att($siteConfig->PrependToMetaTitle.' '.$title.' '.$siteConfig->AppendToMetaTitle)).'</title>';
			}
			else {
				$titleTag = '';
			}
			$tags .= '
			<meta charset="utf-8" />'.
				$titleTag;
			$needsFavicon = true;
			if(file_exists(Director::baseFolder().'/'.$faviconFileBase.'favicon.ico')) {
				$needsFavicon = false;
				$tags .= '
			<link rel="shortcut icon" href="'.$faviconBase.'favicon.ico" type="image/x-icon" />';
			}
			if(!$page->ExtraMeta && $siteConfig->ExtraMeta) {
				$page->ExtraMeta = $siteConfig->ExtraMeta;
			}
			if(!$siteConfig->MetaDataCopyright) {$siteConfig->MetaDataCopyright = $siteConfig->Title;}
			if($addExtraSearchEngineData) {
				$tags .= '
			<meta name="robots" content="'.$noopd.'all, index, follow" />
			<meta name="googlebot" content="'.$noopd.'all, index, follow" />
			<meta name="rights" content="'.Convert::raw2att($siteConfig->MetaDataCopyright).'" />
			<meta name="created" content="'.$lastEdited->Format("Ymd").'" />
			<meta name="geo.country" content="'.$siteConfig->MetaDataCountry.'" />
			<meta name="viewport" content="'.Config::inst()->get("MetaTagsContentControllerEXT", "viewport_setting").'" />
				'.$page->ExtraMeta.
				$description;
			}
			$tags .= $this->OGTags();
			$tags .= $this->iconTags($faviconBase, $needsFavicon);
			$cache->save($tags, $cacheKey);
		}
		return $tags;

	}

	/**
	 * open graph protocol
	 * @see: http://ogp.me/
	 * @return String (HTML)
	 */
	protected function OGTags(){
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

	protected function iconTags($baseURL = "", $includePNGFavicon = false){
		if(!$baseURL) {
			$baseURL = Director::absoluteBaseURL();
		}
		$cacheKey = 'metatags_ExtendedMetaTags_iconsTags_'.preg_replace("/[^A-Za-z0-9 ]/", '', $baseURL);
		$baseURL = rtrim($baseURL, "/");
		$cache = SS_Cache::factory($cacheKey);
		if (!($html = $cache->load($cacheKey)) | 1 == 1) {
			$html = '';
			$sizes = array(
				"16",
				"32",
				"57",
				"72",
				"76",
				"96",
				"114",
				"120",
				"128",
				"144",
				"152",
				"180",
				"192",
				"310"
			);
			foreach($sizes as $size) {
				$themeFolder = SSViewer::get_theme_folder();
				$file = "/".$themeFolder.'/icons/'.'icon-'.$size.'x'.$size.'.png';
				if(file_exists(Director::baseFolder().$file)) {
					$html .= '
<link rel="icon" type="image/png" sizes="'.$size.'x'.$size.'"  href="'.$baseURL.$file.'" />
<link rel="apple-touch-icon" type="image/png" sizes="'.$size.'x'.$size.'"  href="'.$baseURL.$file.'" />';
				}
				elseif($this->owner->getSiteConfig()->FaviconID) {
					if($favicon = $this->owner->getSiteConfig()->Favicon()) {
						$generatedImage = $favicon->setWidth($size);
						$html .= '
<link rel="icon" type="image/png" sizes="'.$size.'x'.$size.'"  href="'.$baseURL.$generatedImage->Link().'" />
<link rel="apple-touch-icon" type="image/png" sizes="'.$size.'x'.$size.'"  href="'.$baseURL.$generatedImage->Link().'" />';
					}
				}
			}
			if($includePNGFavicon) {
				$themeFolder = SSViewer::get_theme_folder();
				$faviconLocation = "/".$themeFolder.'/icons/favicon.ico';
				if(file_exists(Director::baseFolder().$faviconLocation)) {
					$faviconLink = $baseURL.$faviconLocation;
				}
				else {
					$generatedImage = $favicon->setWidth(16);
					$faviconLink = $baseURL.$generatedImage->Link();
				}
				$html .= '
<link rel="shortcut icon" href="'.$faviconLink.'" type="image/png" />';
			}
			$cache->save($html, $cacheKey);
		}
		return $html;
	}

}
