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
	protected static $disable_update_popup = 0;
		static function set_disable_update_popup($var) {self::$disable_update_popup = $var;}

	/* default value for auto-update pages' metatags */
	protected static $default_state_auto_update_checkbox = 0;
		static function set_default_state_auto_update_checkbox($var) {self::$default_state_auto_update_checkbox = $var;}

	/* meta-title */
	protected static $update_meta_title = 0;
		static function set_update_meta_title($var) {self::$update_meta_title = $var;}
	protected static $prepend_to_meta_title = "";
		static function set_prepend_to_meta_title($var) {self::$prepend_to_meta_title = $var;}
		static function get_prepend_to_meta_title() {return self::$prepend_to_meta_title;}
	protected static $append_to_meta_title = "";
		static function set_append_to_meta_title($var) {self::$append_to_meta_title = $var;}
		static function get_append_to_meta_title() {self::$append_to_meta_title;}

	/* meta descriptions */
	protected static $update_meta_desc = 0;
		static function set_update_meta_desc($var) {self::$update_meta_desc = $var;}
	protected static $meta_desc_length = 12;
		static function set_meta_desc_length($var) {self::$meta_desc_length = $var;}

	/* meta keywords */
	protected static $update_meta_keys = 0;
		static function set_update_meta_keys($var) {self::$update_meta_keys = $var;}
	protected static $number_of_keywords = 15;
		static function set_number_of_keywords($var) {self::$number_of_keywords = $var;}
	protected static $min_word_char = 3;
		static function set_min_word_char($var) {self::$min_word_char = $var;}
	protected static $exclude_words = 'the,and,from';
		static function set_exclude_words($var) {self::$exclude_words = $var;}

	public function extraStatics() {
		return array (
			'db' => array(
				'AutomateMetatags' => 'Boolean'
			)
		);
	}

	function populateDefaults() {
		return array(
			"AutomateMetatags" => self::$default_state_auto_update_checkbox
		);
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

	public function updateCMSFields(FieldSet &$fields) {
		$automatedFields =  $this->updatedFieldsArray();
		if(count($automatedFields)) {
			$updated_field_string = "(".implode(", ", $automatedFields).")";
			$fields->addFieldToTab('Root.Content.Metadata', new CheckboxField('AutomateMetatags', _t('MetaManager.UPDATEMETA','Automatically Update Meta-data Fields:'). $updated_field_string), "URL");
			foreach($fields as $field) {
				if(in_array($field->Title, $automatedFields)) {
					$fields->removeFieldsFromTab('Root.Content.Metadata', $field->Title);
					$newField = $field->performDisabledTransformation();
					$fields->addFieldToTab('Root.Content.Metadata', $newField);
				}
			}
		}
		if(1 == self::$disable_update_popup){
			Requirements::clear('sapphire/javascript/UpdateURL.js');
			Requirements::javascript('metatags/javascript/UpdateURL.js');
		}
	}

	/**
	 * Update Metadata fields function
	 */
	public function onBeforeWrite () {
		// if UpdateMeta checkbox is checked, update metadata based on content and title
		// we only update this from the CMS to limit slow-downs in programatic updates
		if(isset($_REQUEST['AutomateMetatags']) && $_REQUEST['AutomateMetatags']){
			if(self::$update_meta_title == 1){
				// Empty MetaTitle
				$this->owner->MetaTitle = '';
				// Check for Content, to prevent errors
				if($this->owner->Title){
					$this->owner->MetaTitle = MetaTagAutomation::get_prepend_to_meta_title().strip_tags($this->owner->Title).MetaTagAutomation::get_append_to_meta_title();
				}
			}
			if(self::$update_meta_desc == 1){
				// Empty MetaDescription
				$this->owner->MetaDescription = '';
				// Check for Content, to prevent errors
				if($this->owner->Content){
					$this->owner->MetaDescription = strip_tags($this->owner->Content);
					if(self::$meta_desc_length > 0){
						$this->owner->MetaDescription = substr($this->owner->MetaDescription, 0, self::$meta_desc_length);
					}
				}
			}
			if(self::$update_meta_keys == 1){
				// Empty MetaKeywords
				$this->owner->MetaKeywords = '';
				// Check for Content, to prevent errors
				if($this->owner->Content){
					// calculateKeywords
					$keystring = self::calculateKeywords();
					if($keystring){
						$this->owner->MetaKeywords = $keystring;
					}
				}
			}
		}
		parent::onBeforeWrite();
 	}

	public function onAfterWrite(){
		// TODO : find a nicer way to reload the page and when exactly it needs reloading
		//LeftAndMain::ForceReload ();
		parent::onAfterWrite ();
	}

	private function updatedFieldsArray(){
		$updateDatedFieldArray = array();
		if(self::$disable_update_popup) { $updateDatedFieldArray["URLSegment"] = _t('SiteTree.URLSegment','URL Segment ');}
		if(self::$update_meta_title) 		{ $updateDatedFieldArray["Title"] = _t('SiteTree.METATITLE','Title '); }
		if(self::$update_meta_desc) 		{ $updateDatedFieldArray["Description"] = _t('SiteTree.METADESC','Description '); }
		if(self::$update_meta_keys) 		{ $updateDatedFieldArray["Keywords"] = _t('SiteTree.METAKEYWORDS','Keywords ');}
		return $updateDatedFieldArray;
	}

	private function calculateKeywords() {
		$content = $this->owner->Content;
		$exclude_words_array = explode(", ", self::$exclude_words);
		// get rid off the htmltags
		$string = strip_tags($string);
		// count all words
		$initial_words_array = str_word_count($string, 1);
		$total_words = sizeof($initial_words_array);
		$new_string = $content;
		// strip excluded words
		if(is_array($exclude_words_array) && count($exclude_words_array)) {
			foreach($exclude_words_array as $filter_word)	{
				$new_string = preg_replace("/\b".$filter_word."\b/i", "", $new_string);
			}
		}
		// calculate words again without the excluded words
		$words_array = str_word_count($new_string, 1);
		$words_array = array_filter($words_array, create_function('$var', 'return (strlen($var) >= '.self::$min_word_char.');'));
		$popularity = array();
		$unique_words_array = array_unique($words_array);
		// create density array
		foreach($unique_words_array as $key => $word)	{
			//should this be string or newstring
			preg_match_all('/\b'.$word.'\b/i', $content, $out);
			$count = count($out[0]);
			$popularity[$key]['count'] = $count;
			$popularity[$key]['word'] = $word;
		}
		usort($popularity, array($this,'cmp'));
		// sort array form higher to lower
		krsort($popularity);
		// create keyword array with only words
		$keywords = array();
		foreach($popularity as $value){
			$keywords[] = $value['word'];
		}
		// glue keywords to string seperated by comma, maximum 15 words
		$keystring = strtolower(implode(', ', array_slice($keywords, 0, self::$number_of_keywords)));
		// return the keywords
		return $keystring;
	}
	/**
	 * Sort array by count value
	 */
	private static function cmp($a, $b) {
		return ($a['count'] > $b['count']) ? +1 : -1;
	}


}

class MetaTagAutomation_controller extends Extension {

	/* additional metatag information */
	protected static $country = "New Zealand";
		static function set_country($var) {self::$country = $var;}
	protected static $copyright = 'owner';
		static function set_copyright($var) {self::$copyright = $var;}
	protected static $design = 'owner';
		static function set_design($var) {self::$design = $var;}
	protected static $coding = "owner";
		static function set_coding($var) {self::$coding = $var;}

	/* combined files */
	protected static $folder_for_combined_files = "assets";
		static function set_folder_for_combined_files($var) {self::$folder_for_combined_files = $var;}
	protected static $combine_css_files_into_one = false;
		public static function set_combine_css_files_into_one($var) {self::$combine_css_files_into_one = $var;}
	protected static $combine_js_files_into_one = false;
		public static function set_combine_js_files_into_one($var) {self::$combine_js_files_into_one = $var;}

	static $allowed_actions = array(
		"starttestforie",
		"stoptestforie"
	);

	/**
	 * add all the basic js and css files - call from Page::init()
	 */

	function addBasicMetatagRequirements($additionalJS = array(), $additionalCSS = array()) {
		$themeFolder = $this->getThemeFolder()."/";
		$jsArray =
			array(
				"jsparty/jquery/jquery.js",
				$this->owner->project().'/javascript/j.js'
			);
		array_merge($jsArray, $additionalJS);
		$cssArray =
			array(
				$themeFolder.'css/reset.css',
				$themeFolder.'css/layout.css',
				$themeFolder.'css/typography.css',
				$themeFolder.'css/form.css',
				$themeFolder.'css/menu.css',
				$themeFolder.'css/print.css'
			);
		array_merge($cssArray, $additionalCSS);
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
		if(self::$combine_css_files_into_one) {
			Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomation.css",$cssArray);
		}
		if(self::$combine_js_files_into_one) {
			Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomationPrototype.js", $prototypeArray);
			Requirements::combine_files(self::$folder_for_combined_files."/MetaTagAutomation.js", $jsArray);
		}
		if(Session::get("testforie") > 0) {
			Requirements::insertHeadTags('<style type="text/css">@import url('.MetaTagAutomation::$theme_folder.'css/ie'.Session::get("testforie").'.css);</style>');
		}
		else {
			Requirements::insertHeadTags('<!--[if IE 6]><style type="text/css">@import url('.$themeFolder.'css/ie6.css);</style><![endif]-->');
			Requirements::insertHeadTags('<!--[if IE 7]><style type="text/css">@import url('.$themeFolder.'css/ie7.css);</style><![endif]-->');
			Requirements::insertHeadTags('<!--[if IE 8]><style type="text/css">@import url('.$themeFolder.'css/ie8.css);</style><![endif]-->');
		}
	}

	/**
	 * this function will add more metatags to your template - make sure to add it at the start of your metatags
	 */

	function ExtendedMetatags($includeTitle = true, $addExtraSearchEngineData = true) {
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
		$lastEdited = new SSDatetime();
		$lastEdited->value = $this->owner->LastEdited;
		$tags .= '
			<meta http-equiv="Content-type" content="text/html; charset=utf-8" />'.
			($includeTitle ? '<title>'.$title.'</title>' : '')
			.'<link rel="icon" href="/favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
			<meta name="keywords" http-equiv="keywords" content="'.Convert::raw2att($keywords).'" />'.$description;
		if($addExtraSearchEngineData) {
			$tags .= '
			<meta name="robots" content="'.$noopd.'all, index, follow" />
			<meta name="googlebot" content="'.$noopd.'all, index, follow" />
			<meta name="copyright" content="'.self::$copyright.'" />
			<meta name="coding" content="'.self::$coding.'" />
			<meta name="design" content="'.self::$design.'" />
			<meta name="date-modified-yyyymmdd" content="'.$lastEdited->Format("Ymd").'" />
			<meta name="country" content="'.self::$country.'" />
			<meta http-equiv="imagetoolbar" content="no" />';
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
