<?php
/**
*
* @Author Martijn van Nieuwenhoven & Nicolaas Francken
*
* @Silverstripe version 2.3.2
* @package metatags
*
**/

class MetaTagAutomation extends SiteTreeDecorator {

	/* pop-ups and form interaction */
	protected static $disable_update_popup = false;
		static function set_disable_update_popup($b) {self::$disable_update_popup = $b;}

	/* meta descriptions */
	protected static $meta_desc_length = 12;
		static function set_meta_desc_length($i) {self::$meta_desc_length = $i;}
		static function get_meta_desc_length() {return self::$meta_desc_length;}

	/* meta keywords
	*/
	protected static $hide_keywords_altogether = true;
		static function set_hide_keywords_altogether($b) {self::$hide_keywords_altogether = $b; }
		static function get_hide_keywords_altogether() {return self::$hide_keywords_altogether; }

	protected static $google_font_collection = array();
		static function add_google_font($s) {self::$google_font_collection[$s] = $s;}
		static function remove_google_font($s) {unset(self::$google_font_collection[$s]);}
		static function get_google_font_collection() {return self::$google_font_collection;}

	/* favicon */
	protected static $use_themed_favicon = false;
		static function set_use_themed_favicon($b) {self::$use_themed_favicon = $b;}
		static function get_use_themed_favicon() {return self::$use_themed_favicon;}

	public function extraStatics() {
		return array (
			'db' => array(
				'AutomateMetatags' => 'Boolean',
			),
			'defaults' => array(
				'AutomateMetatags' => true
			)
		);
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

	public function updateCMSFields(FieldSet &$fields) {
		if(self::get_hide_keywords_altogether()) {
			$fields->removeFieldFromTab("Root.Content.Metadata", "MetaKeywords");
		}
		$automatedFields =  $this->updatedFieldsArray();
		if(count($automatedFields)) {
			$updated_field_string = " (the following fields will be automatically updated: <i>".implode("</i>, <i>", $automatedFields)."</i>).";
			$fields->addFieldToTab('Root.Content.Metadata', new CheckboxField('AutomateMetatags', _t('MetaManager.UPDATEMETA','Allow Meta (Search Engine) Fields to be updated automatically? '). $updated_field_string), "URL");
			if($this->owner->AutomateMetatags) {
				foreach($automatedFields as $fieldName => $fieldTitle) {
					$newField = $fields->dataFieldByName($fieldName)->performReadonlyTransformation();
					$newField->setTitle($newField->Title()." (automatically updated when you save this page)");
					$fields->replaceField($fieldName, $newField);
				}
			}
		}
		$fields->dataFieldByName("ExtraMeta")->setTitle($fields->dataFieldByName("ExtraMeta")->Title()." (advanced users only)");
		if(1 == self::$disable_update_popup){
			Requirements::clear('sapphire/javascript/UpdateURL.js');
			Requirements::javascript(SS_METATAG_DIR.'/javascript/UpdateURL.js');
		}
		$linkToManager = "/" . MetaTagCMSControlPages::get_url_segment() ."/";
		$fields->addFieldToTab('Root.Content.Metadata', new LiteralField("LinkToManagerHeader", "<p>Open the Meta Tag Manager to <a href=\"$linkToManager\" target=\"_blank\">Review and Edit</a> the Meta Data for all pages on this site. Also make sure to review the general <a href=\"/admin/show/root/\">settings for Search Engines</a>.</p>"), "URL");
		return $fields;
	}

	/**
	 * Update Metadata fields function
	 */
	public function onBeforeWrite () {
		parent::onBeforeWrite();
		$siteConfig = SiteConfig::current_site_config();
		// if UpdateMeta checkbox is checked, update metadata based on content and title
		// we only update this from the CMS to limit slow-downs in programatic updates
		if(isset($_REQUEST['AutomateMetatags']) && $_REQUEST['AutomateMetatags']){
			if($siteConfig->UpdateMetaTitle){
				// Empty MetaTitle
				$this->owner->MetaTitle = '';
				// Check for Content, to prevent errors
				if($this->owner->Title){
					$this->owner->MetaTitle = $this->cleanInput($this->owner->Title, 0);
				}
			}
			if($siteConfig->UpdateMenuTitle){
				// Empty MetaTitle
				$this->owner->MenuTitle = '';
				// Check for Content, to prevent errors
				if($this->owner->Title){
					$this->owner->MenuTitle = $this->cleanInput($this->owner->Title, 0);
				}
			}
			if($siteConfig->UpdateMetaDescription && self::$meta_desc_length ){
				// Empty MetaDescription
				// Check for Content, to prevent errors
				if($this->owner->Content && !$this->owner->MetaDescription){
					$this->owner->MetaDescription = DBField::create("HTMLText", $this->owner->Content)->Summary(MetaTagAutomation::get_meta_desc_length(), 15, "");

				}
			}
		}
		
 	}


	public function onAfterWrite(){
		// TODO : find a nicer way to reload the page and when exactly it needs reloading
		//LeftAndMain::ForceReload ();
		parent::onAfterWrite ();
		$siteConfig = SiteConfig::current_site_config();
		$oldMetaTitle = $this->owner->MetaTitle;
		if($siteConfig->PrependToMetaTitle) {
			if(strpos($this->owner->MetaTitle, $siteConfig->PrependToMetaTitle) === 0) {
				$this->owner->MetaTitle = str_replace($siteConfig->PrependToMetaTitle, "", $this->owner->MetaTile);
			}
		}
		if($siteConfig->AppendToMetaTitle) {
			if(strpos($this->owner->MetaTitle, $siteConfig->AppendToMetaTitle) === (strlen($this->owner->MetaTitle) - strlen($siteConfig->AppendToMetaTitle))) {
				$this->owner->MetaTitle = str_replace($siteConfig->AppendToMetaTitle, "", $this->owner->MetaTile);
			}
		}
		if($this->owner->MetaTitle != $oldMetaTitle) {
			$this->owner->write();
		}
	}

	private function updatedFieldsArray(){
		$siteConfig = SiteConfig::current_site_config();
		$updateDatedFieldArray = array();
		if(self::$disable_update_popup) 					{ $updateDatedFieldArray["URLSegment"] = _t('SiteTree.URLSegment','URL Segment ');}
		if($siteConfig->UpdateMenuTitle) 	  			{ $updateDatedFieldArray["MenuTitle"] = _t('SiteTree.METADESC','Menu Title '); }
		if($siteConfig->UpdateMetaTitle) 					{ $updateDatedFieldArray["MetaTitle"] = _t('SiteTree.METATITLE','Title '); }
		if($siteConfig->UpdateMetaDescription) 	  { $updateDatedFieldArray["MetaDescription"] = _t('SiteTree.METADESC','Description '); }
		return $updateDatedFieldArray;
	}

	function populateDefaults () {
		$this->owner->AutomateMetatags = true;
	}

	/*
	private function SiteConfigVar($fieldName) {
		if($siteConfig = SiteConfig::current_site_config()) {
			if(isset($siteConfig->$fieldName)) {
				return $siteConfig->$fieldName;
			}
		}
		return false;
	}
	*/
	private function cleanInput($string, $numberOfWords = 0) {
		$newString = str_replace("&nbsp;", "", $string);
		$newString = str_replace("&amp;", " and ", $newString);
		$newString = str_replace("&ndash;", " - ", $newString);
		$newString = strip_tags(str_replace('<', ' <', $newString));
		if($numberOfWords) {
			$textFieldObject = Text::create("Text", $newString);
			if($textFieldObject) {
				$newString = strip_tags($textFieldObject->LimitWordCountXML($numberOfWords));
			}
		}
		$newString = html_entity_decode($newString, ENT_QUOTES);
		$newString = html_entity_decode($newString, ENT_QUOTES);
		return $newString;
	}


}

class MetaTagAutomation_controller extends Extension {

	/* combined files */
	protected static $folder_for_combined_files = "assets";
		static function set_folder_for_combined_files($s) {self::$folder_for_combined_files = $s;}
	protected static $combine_css_files_into_one = false;
		public static function set_combine_css_files_into_one($s) {self::$combine_css_files_into_one = $s;}
	protected static $combine_js_files_into_one = false;
		public static function set_combine_js_files_into_one($s) {self::$combine_js_files_into_one = $s;}

	static $allowed_actions = array(
		"starttestforie",
		"stoptestforie",
		"updateallmetatitles"
	);

	/**
	 * add all the basic js and css files - call from Page::init()
	 */

	function addBasicMetatagRequirements($additionalJS = array(), $additionalCSS = array()) {
		$themeFolder = $this->getThemeFolder()."/";
		$cssArrayLocationOnly = array();
		$jsArray =
			array(
				THIRDPARTY_DIR."/jquery/jquery.js",
				$this->owner->project().'/javascript/j.js'
			);
		array_merge($jsArray, $additionalJS);
		$cssArray =
			array(
				array("media" => null, "location" => SS_METATAG_DIR.'/css/reset.css'),
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
		Requirements::themedCSS($this->owner->ClassName);
		if(self::$combine_css_files_into_one) {
			Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomation.css",$cssArrayLocationOnly);
		}
		if(self::$combine_js_files_into_one) {
			$prototypeArray =
				array(
					"sapphire/javascript/Validator.js",
					THIRDPARTY_DIR."/prototype/prototype.js",
					THIRDPARTY_DIR."/behaviour/behaviour.js",
					"sapphire/javascript/prototype_improvements.js"
				);
			Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomationPrototype.js", $prototypeArray);
			Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomation.js", $jsArray);
		}
		if(Session::get("testforie") > 0) {
			Requirements::insertHeadTags('<style type="text/css">@import url('.$themeFolder.'css/ie'.Session::get("testforie").'.css);</style>');
		}
		else {
			Requirements::insertHeadTags('<!--[if IE 6]><style type="text/css">@import url('.$themeFolder.'css/ie6.css);</style><![endif]-->');
			Requirements::insertHeadTags('<!--[if IE 7]><style type="text/css">@import url('.$themeFolder.'css/ie7.css);</style><![endif]-->');
			Requirements::insertHeadTags('<!--[if IE 8]><style type="text/css">@import url('.$themeFolder.'css/ie8.css);</style><![endif]-->');
		}
		$array = MetaTagAutomation::get_google_font_collection();
		if($array && count($array)) {
			foreach($array as $font) {
				Requirements::insertHeadTags('<link href="http://fonts.googleapis.com/css?family='.$font.'" rel="stylesheet" type="text/css" />');
			}
		}
	}

	/**
	 * this function will add more metatags to your template - make sure to add it at the start of your metatags
	 */

	function ExtendedMetatags($includeTitle = true, $addExtraSearchEngineData = true) {
		$tags = "";
		$page = $this->owner;
		$siteConfig = SiteConfig::current_site_config();
		$title = Convert::raw2xml(($page->MetaTitle) ? $page->MetaTitle : $page->Title );
		if(!MetaTagAutomation::get_hide_keywords_altogether()) {
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
		if(class_exists("SSDatetime")) {
			$lastEdited = new SSDatetime();
		}
		else {
			$lastEdited = new DateTime();
		}
		$lastEdited->value = $this->owner->LastEdited;

		//use base url rather than / so that sites that aren't a run from the root directory can have a favicon
		$faviconBase = Director::baseURL();
		if(MetaTagAutomation::get_use_themed_favicon()) {
			$faviconBase .= $this->getThemeFolder()."/";
		}
		if($includeTitle) {
			$titleTag = '
			<title>'.Convert::raw2att($siteConfig->PrependToMetaTitle.' '.$title.' '.$siteConfig->AppendToMetaTitle).'</title>';
		}
		else {
			$titleTag = '';
		}
		$tags .= '
			<meta http-equiv="Content-type" content="text/html; charset=utf-8" />'.
			$titleTag
			.'
			<link rel="icon" href="'.$faviconBase.'favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="'.$faviconBase.'favicon.ico" type="image/x-icon" />';
		if(!MetaTagAutomation::get_hide_keywords_altogether()) {
			$tags .= '
			<meta name="keywords" http-equiv="keywords" content="'.Convert::raw2att($keywords).'" />'.$description;
		}
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
			'.$page->ExtraMeta;
		}
		return $tags;
	}

	/**
	 * start internet explorer test
	 */

	function starttestforie() {
		Session::set("testforie", Director::urlParam("ID"));
		Requirements::customScript('alert("starting test for IE'.Session::get("testforie").' - to stop go to '.$this->owner->URLSegment.'/stoptestforie");');
		return array();
	}


	/**
	 * end internet explorer test
	 */

	function stoptestforie() {
		Requirements::customScript('alert("stopped test for IE'.Session::get("testforie").' - to start go to '.$this->owner->URLSegment.'/starttestforie");');
		Session::set("testforie", 0);
		return array();
	}

	/**
	 * need to work out how this action can be called without adding an action to the URL and without interfering with potential other actions
	 */

	function handleAction(HTTPRequest $request) {
		if(7 == Session::get("testforie")) {
			$request->addHeader('X-UA-Compatible', 'IE=EmulateIE7');
		}
		return parent::handleAction($request);
	}

	//maybe replaced with something more universal (e.g. SSViewer::get_theme_folder())
	private function getThemeFolder() {
		return SSViewer::current_theme() ? THEMES_DIR . "/" . SSViewer::current_theme() : $this->owner->project();
	}


}
