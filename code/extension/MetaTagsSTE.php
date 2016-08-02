<?php
/**
*
* @Author Nicolaas Francken
* adding meta tag functionality to the SiteTree Model Classes.
*
*
*
**/

class MetaTagsSTE extends SiteTreeExtension {

	/**
	 * standard SS method
	 * @var Array
	 **/
	private static $db = array(
		'AutomateMetatags' => 'Boolean(1)'
	);

	/**
	 * standard SS method
	 * @var Array
	 **/
	private static $has_one = array(
		'ShareOnFacebookImage' => 'Image'
	);

	/**
	 * @var string
	 * set to empty string to stop it being copied
	 * by default to the theme
	 **/
	private static $default_editor_file = "metatags/css/editor.css";


	/**
	 * @var string
	 * set to empty string to stop it being copied
	 * by default to the theme
	 **/
	private static $default_reset_file = "metatags/css/reset.css";

	/**
	 * because we use this function you can NOT
	 * use any statics in the file!!!
	 * @return Array | null
	 */
	public static function get_extra_config($class, $extension, $args) {
		if(Config::inst()->get("MetaTagsContentControllerEXT", "use_separate_metatitle") == 1)  {
			$array = array(
				'db' => array("MetaTitle" => "Varchar(255)") + self::$db
			);
		}
		else {
			$array = array(
				'db' => self::$db
			);
		}

		return ((array) parent::get_extra_config($class, $extension, $args)) + $array;
	}

	/**
	 * standard SS method
	 * @var Array
	 **/
	private static $defaults = array(
		'AutomateMetatags' => true
	);

	/**
	 * standard SS method
	 * @var Array
	 **/
	public function updateSettingsFields(FieldList $fields) {
		$fields->addFieldToTab("Root.Facebook",
			new HeaderField(
				_t("MetaTagsSTE.FB_HOW_THIS_PAGE_IS_SHARED", "How is this page shared on Facebook?")
			)
		);
		$fields->addFieldToTab("Root.Facebook", $fieldTitle = new ReadonlyField("fb_title", _t("MetaTagsSTE.FB_TITLE", "Title"), $this->owner->Title));
		$fields->addFieldToTab("Root.Facebook", $fieldType = new ReadonlyField("fb_type", _t("MetaTagsSTE.FB_TITLE", "Type"), "website"));
		$fields->addFieldToTab("Root.Facebook", $fieldSiteName = new ReadonlyField("fb_type", _t("MetaTagsSTE.FB_SITE_NAME", "Site Name"), SiteConfig::current_site_config()->Title));
		$fields->addFieldToTab("Root.Facebook", $fieldDescription = new ReadonlyField("fb_description", _t("MetaTagsSTE.FB_DESCRIPTION", "Description (from MetaDescription)"), $this->owner->MetaDescription));
		$fields->addFieldToTab("Root.Facebook",
			$shareOnFacebookImageField = new UploadField(
				"ShareOnFacebookImage",
				_t("MetaTagsSTE.FB_IMAGE", "Image")
			)
		);
		$shareOnFacebookImageField->setFolderName("OpenGraphShareImages");
		$shareOnFacebookImageField->setRightTitle("Use images that are at least 1200 x 630 pixels for the best display on high resolution devices. At the minimum, you should use images that are 600 x 315 pixels to display link page posts with larger images.");
		$fields->addFieldToTab("Root.Facebook",
			$shareOnFacebookImageField = new LiteralField(
				"fb_try_it_out",
				'<h3><a href="https://www.facebook.com/sharer/sharer.php?u='.urlencode($this->owner->AbsoluteLink()).'">'._t("MetaTagsSTE.FB_TRY_IT_OUT", "Share on Facebook Now") .'</a></h3>',
				$this->owner->ShareOnFacebookImage()
			)
		);
		//right titles
		$fieldTitle->setRightTitle(
			_t(
				"MetaTagsSTE.FB_TITLE_RIGHT",
				"Uses the Page Title"
			)
		);
		$fieldType->setRightTitle(
			_t(
				"MetaTagsSTE.FB_TYPE_RIGHT",
				"Can not be changed"
			)
		);
		$fieldSiteName->setRightTitle(
			_t(
				"MetaTagsSTE.FB_SITE_NAME_RIGHT",
				"Can be set in the site settings"
			)
		);
		$fieldDescription->setRightTitle(
			_t(
				"MetaTagsSTE.FB_DESCRIPTION",
				"Description is set in the Metadata section of each page."
			)
		);
		$shareOnFacebookImageField->setRightTitle(
			_t(
				"MetaTagsSTE.FB_HOW_TO_CHOOSE_IMAGE",
				"If no image is set then the Facebook user can choose an image from the page - with options retrieved by Facebook."
			)
		);
	}
	/**
	 * standard SS method
	 * @var Array
	 **/
	public function updateCMSFields(FieldList $fields) {
		//separate MetaTitle
		if(Config::inst()->get("MetaTagsContentControllerEXT", "use_separate_metatitle") == 1) {
			$fields->addFieldToTab(
				'Root.Main.Metadata',
				$allowField0 = new TextField(
					'MetaTitle',
					_t('SiteTree.METATITLE', 'Meta Title')
				),
				"MetaDescription"
			);
			$allowField0->setRightTitle(
				_t("SiteTree.METATITLE_EXPLANATION", "Leave this empty to use the page title")
			);
		}

		//info about automation
		$fields->addFieldToTab(
			'Root.Main.Metadata',
			$allowField1 = new CheckboxField(
				'AutomateMetatags',
				_t('MetaManager.UPDATEMETA','Automatically update Meta Description and Navigation Label? ')
			)
		);
		$automatedFields =  $this->updatedFieldsArray();
		$updatedFieldString = "";
		if(count($automatedFields)) {
			$updatedFieldString = ""
				._t("MetaManager.UPDATED_EXTERNALLY", "Based on your current settings, the following fields will be automatically updated at all times")
				.": <em>"
				.implode("</em>, <em>", $automatedFields)
				."</em>.";
			foreach($automatedFields as $fieldName => $fieldTitle) {
				$oldField = $fields->dataFieldByName($fieldName);
				if($oldField) {
					$newField = $oldField->performReadonlyTransformation();
					//$newField->setTitle($newField->Title());
					$newField->setRightTitle(_t("MetaTags.AUTOMATICALLY_UPDATED", "Automatically updated when you save this page (see metadata settings)."));
					$fields->replaceField($fieldName, $newField);
				}
			}
		}
		$fields->removeByName('ExtraMeta');
		$linkToManager = Config::inst()->get("MetaTagCMSControlPages", "url_segment") . '/';
		$fields->addFieldToTab(
			'Root.Main.Metadata',
			new LiteralField(
				"LinkToManagerHeader",
				"<blockquote style='padding-left: 12px;'>
					<p>
						Open the Meta Tag Manager to
						<a href=\"$linkToManager\" target=\"_blank\">Review and Edit</a>
						the Meta Data for all pages on this site.
						Also make sure to review the general settings for
						<a href=\"/admin/settings/\">Search Engines</a>. $updatedFieldString
					</p>
				</blockquote>"
			)
		);
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
		if($this->owner->AutomateMetatags == 1 || $siteConfig->UpdateMenuTitle){
			// Empty MenuTitle
			$this->owner->MenuTitle = '';
			// Check for Content, to prevent errors
			if($this->owner->Title){
				$this->owner->MenuTitle = $this->cleanInput($this->owner->Title, 0);
			}
		}
		$length = Config::inst()->get("MetaTagsContentControllerEXT", "meta_desc_length");
		if(($this->owner->AutomateMetatags || $siteConfig->UpdateMetaDescription) && $length > 0){
			// Empty MetaDescription
			// Check for Content, to prevent errors

			if($this->owner->Content){
				//added a few hacks here
				$contentField = DBField::create_field("Text", strip_tags($this->owner->Content), "MetaDescription");
				$flex = ceil(Config::inst()->get("MetaTagsContentControllerEXT", "meta_desc_length") / 2) + 5;
				$summary = $contentField->Summary($length, $flex);
				$summary = str_replace("<br />", " ", $summary);
				$this->owner->MetaDescription = $summary;
			}
		}
 	}

	/**
	 * what fields are updated automatically?
	 * @return Array
	 */
	private function updatedFieldsArray(){
		$config = SiteConfig::current_site_config();
		$fields = array();
		if($config->UpdateMenuTitle || $this->owner->AutomateMetatags) {
			$fields['MenuTitle'] = _t('SiteTree.MENUTITLE', 'Menu Title ');
		}
		if($config->UpdateMetaDescription || $this->owner->AutomateMetatags) {
			$fields['MetaDescription'] = _t('SiteTree.METADESCRIPTION', 'Meta Description');
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

	/**
	 * add default css files
	 *
	 */
	function requireDefaultRecords(){
		$folder = SSViewer::current_theme();
		if($folder) {
			if($file = Config::inst()->get("MetaTagsSTE", "default_editor_file")) {
				$baseFile = Director::baseFolder(). $file;
				$destinationFile = Director::baseFolder()."/themes/".$folder."/css/editor.css";
				if(!file_exists($destinationFile) && file_exists($baseFile)) {
					copy($baseFile, $destinationFile);
				}
			}
			if($file = Config::inst()->get("MetaTagsSTE", "default_reset_file")) {
				$baseFile = Director::baseFolder(). $file;
				$destinationFile = Director::baseFolder()."/themes/".$folder."/css/reset.css";
				if(!file_exists($destinationFile) && file_exists($baseFile)) {
					copy($baseFile, $destinationFile);
				}
			}
		}
	}
}
