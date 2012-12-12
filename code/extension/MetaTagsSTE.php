<?php
/**
*
* @Author Martijn van Nieuwenhoven & Nicolaas Francken
*
* @Silverstripe version 2.3.2
* @package metatags
*
**/

class MetaTagsSTE extends SiteTreeExtension {

	/**
	 * pop-ups and form interaction
	 * @var Boolean
	 */
	static $disable_update_popup = false;

	/**
	 * length of auto-generated meta descriptions in header
	 * @var Int
	 */
	static $meta_desc_length = 24;

	/**
	 * exclude meta keywords from header altogether
	 * @var Boolean
	 **/
	static $hide_keywords_altogether = true;

	/**
	 * google fonts to be used
	 * @var Array
	 **/
	protected static $google_font_collection = array();
		static function add_google_font($s) {self::$google_font_collection[$s] = $s;}
		static function remove_google_font($s) {unset(self::$google_font_collection[$s]);}
		static function get_google_font_collection() {return self::$google_font_collection;}

	/**
	 * @var Boolean
	 **/
	static $use_themed_favicon = false;

	/**
	 * standard SS method
	 * @var Array
	 **/
	static $db = array(
		'AutomateMetatags' => 'Boolean'
	);

	/**
	 * standard SS method
	 * @var Array
	 **/
	static $defaults = array(
		'AutomateMetatags' => true
	);

	/**
	 * standard SS method
	 * @var Array
	 **/
	public function updateCMSFields(FieldList $fields) {
		if(self::$hide_keywords_altogether) {
			$fields->removeByName('MetaKeywords');
		}
		$automatedFields =  $this->updatedFieldsArray();
		if(count($automatedFields)) {
			$updated_field_string = " (the following fields will be automatically updated: <i>".implode("</i>, <i>", $automatedFields)."</i>).";
			$fields->addFieldToTab('Root.Metadata', new CheckboxField('AutomateMetatags', _t('MetaManager.UPDATEMETA','Allow Meta (Search Engine) Fields to be updated automatically? '). $updated_field_string));
			if($this->owner->AutomateMetatags) {
				foreach($automatedFields as $fieldName => $fieldTitle) {
					$newField = $fields->dataFieldByName($fieldName)->performReadonlyTransformation();
					$newField->setTitle($newField->Title()." (automatically updated when you save this page)");
					$fields->replaceField($fieldName, $newField);
				}
			}
		}
		$fields->removeByName('ExtraMeta');
		if(self::$disable_update_popup) {
			Requirements::clear('sapphire/javascript/UpdateURL.js');
			Requirements::javascript(SS_METATAGS_DIR.'/javascript/UpdateURL.js');
		}
		$linkToManager = '/' . MetaTagCMSControlPages::get_url_segment() . '/';
		$fields->addFieldToTab('Root.Metadata', new LiteralField("LinkToManagerHeader", "<p>Open the Meta Tag Manager to <a href=\"$linkToManager\" target=\"_blank\">Review and Edit</a> the Meta Data for all pages on this site. Also make sure to review the general <a href=\"/admin/show/root/\">settings for Search Engines</a>.</p>"));
		if($this->owner->URLSegment == RootURLController::get_default_homepage_link()) {
			$newField = $fields->dataFieldByName('URLSegment');
			$newField->setRightTitle("Careful: changing the URL from 'home' to anything else means that this page will no longer be the home page");
			$fields->replaceField('URLSegment', $newField);
		}
		return $fields;
	}

	/**
	 * Update Metadata fields function
	 */
	public function onBeforeWrite() {
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

				if($this->owner->Content){
					//added a few hacks here
					$contentField = DBField::create("Text", $this->owner->Content, "MetaDescription");
					$flex = ceil(MetaTagsSTE::$meta_desc_length / 2) + 5;
					$summary = $contentField->Summary(MetaTagsSTE::$meta_desc_length, $flex);
					$summary = str_replace("<br />", " ", $summary);
					$this->owner->MetaDescription = strip_tags($summary);
				}
			}
		}

 	}

	public function onAfterWrite() {
		// TODO : find a nicer way to reload the page and when exactly it needs reloading
		//LeftAndMain::ForceReload ();
		parent::onAfterWrite();
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
		$config = SiteConfig::current_site_config();
		$fields = array();
		if(self::$disable_update_popup) {
			$fields['URLSegment'] = _t('SiteTree.URLSegment', 'URL Segment ');
		}
		if($config->UpdateMenuTitle) {
			$fields['MenuTitle'] = _t('SiteTree.MENUTITLE', 'Menu Title ');
		}
		if($config->UpdateMetaTitle) {
			$fields['MetaTitle'] = _t('SiteTree.METATITLE', 'Title ');
		}
		if($config->UpdateMetaDescription) {
			$fields['MetaDescription'] = _t('SiteTree.METADESCRIPTION', 'Description ');
		}
		return $fields;
	}

	function populateDefaults() {
		$this->owner->AutomateMetatags = true;
	}

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

	function requireDefaultRecords(){
		$folder = SSViewer::current_theme();
		if($folder) {
			$destinationFile = Director::baseFolder()."/themes/".$folder."/css/editor.css";
			$baseFile = Director::baseFolder(). "/".SS_METATAGS_DIR."/css/editor.css";
			if(!file_exists($destinationFile) && file_exists($baseFile)) {
				copy($baseFile, $destinationFile);
			}
		}
		$folder = SSViewer::current_theme();
		if($folder) {
			$destinationFile = Director::baseFolder()."/themes/".$folder."/css/reset.css";
			$baseFile = Director::baseFolder(). "/".SS_METATAGS_DIR."/css/reset.css";
			if(!file_exists($destinationFile) && file_exists($baseFile)) {
				copy($baseFile, $destinationFile);
			}
		}
	}
}
