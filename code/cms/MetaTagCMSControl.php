<?php

class MetaTagCMSControl extends Controller {

	protected static $url_segment = 'metatagmanagement'; 
		static function set_url_segment($s){self::$url_segment = $s;}
		static function get_url_segment(){return self::$url_segment;}

	protected static $small_words_array = array('of','a','the','and','an','or','nor','but','is','if','then',	'else','when','at','from','by','on','off','for','in','out','over','to','into','with');
		static function set_small_words_array($a){self::$small_words_array = $a;}
		static function get_small_words_array(){return self::$small_words_array;}
		
	protected $ParentID = 0;

	protected $updatableFields = array(
		"Title",
		"MetaTitle",
		"MenuTitle",
		"MetaDescription",
		"UpdateMenuTitle",
		"UpdateMetaTitle",
		"UpdateMetaDescription",
		"AutomateMetatags"
	);

	function init(){
		parent::init();
		$member = Member::currentMember();
			// Default security check for LeftAndMain sub-class permissions
		if(!Permission::checkMember($member, "CMS_ACCESS_LeftAndMain")) {
			return Security::permissionFailure($this);
		}
		Requirements::javascript("sapphire/thirdparty/jquery/jquery.js");
		Requirements::javascript("sapphire/thirdparty/jquery-form/jquery.form.js");
		Requirements::javascript("metatags/javascript/MetaTagCMSControl.js");
		Requirements::themedCSS("MetaTagCMSControl");
	}

	function index() {
		return $this->renderWith("MetaTagCMSControl");
	}

	function childrenof($request) {
		$this->ParentID = intval($request->param("ID"));
		return array();
	}

	function lowercase($request) {
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				DB::query("UPDATE \"SiteTree\" SET \"$fieldName\" = LOWER(\"$fieldName\")");
				DB::query("UPDATE \"SiteTree_Live\" SET \"$fieldName\" = LOWER(\"$fieldName\")");
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.UPDATEDTOLOWERCASE", "Records updated to <i>lower case</i>"));
				Director::redirect($this->Link());return array();
			}
		}
		Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.NOTUPDATEDTOLOWERCASE", "Records could not be updated <i>to lower case</i>."));
		Director::redirect($this->Link());return array();
	}

	function titlecase($request){
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				$tableArray = array("SiteTree", "SiteTree_Live");
				foreach($tableArray as $table) {
					$rows = DB::query("SELECT \"ID\", \"$fieldName\" FROM \"$table\";");
					foreach($rows as $row) {
						$newValue = Convert::raw2sql($this->convert2TitleCase($row[$fieldName]));
						DB::query("UPDATE \"SiteTree\" SET \"$fieldName\" = '$newValue' WHERE ID = ".$row["ID"]);
						DB::query("UPDATE \"SiteTree_Live\" SET \"$fieldName\" = '$newValue' WHERE ID = ".$row["ID"]);
					}
				}
				Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.UPDATEDTOTITLECASE", "Records updated to <i>title case</i>"));
				Director::redirect($this->Link());return array();
			}
		}
		Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.NOTUPDATEDTOTITLECASE", "Records could not be updated to <i>title case</i>."));
		Director::redirect($this->Link());return array();
	}

	function copyfromtitle($request){
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {		
				DB::query("UPDATE \"SiteTree\" SET \"$fieldName\" = \"Title\"");
				DB::query("UPDATE \"SiteTree_Live\" SET \"$fieldName\" = \"Title\"");
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.COPIEDFROMTITLE", "Copied from title."));
				Director::redirect($this->Link());return array();
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NOTCOPIEDFROMTITLE", "Copy not successful"));
		Director::redirect($this->Link());return array();
	}

	function copyfromcontent($request) {
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				$tableArray = array("SiteTree", "SiteTree_Live");
				foreach($tableArray as $table) {
					$rows = DB::query("SELECT \"$table\".\"ID\", \"$table\".\"Content\" FROM \"$table\" WHERE \"$table\".\"$fieldName\" = '' OR \"$table\".\"$fieldName\" IS NULL;");
					foreach($rows as $row) {
						$newValue = Convert::raw2sql(DBField::create("HTMLText", $row["Content"])->Summary(MetaTagAutomation::get_meta_desc_length(), 15, ""));
						DB::query("UPDATE \"SiteTree\" SET \"$fieldName\" = '$newValue' WHERE ID = ".$row["ID"]);
						DB::query("UPDATE \"SiteTree_Live\" SET \"$fieldName\" = '$newValue' WHERE ID = ".$row["ID"]);
					}
				}
				Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.UPDATEDTOTITLECASE", "Updated empty records with first "));
				Director::redirect($this->Link());return array();
			}
		}
		Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.NOTUPDATEDTOTITLECASE", "Records could not be updated to <i>title case</i>."));
		Director::redirect($this->Link());return array();
	}

	function togglecopyfromtitle($request) {
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {		
				DB::query("UPDATE \"SiteConfig\" SET \"$fieldName\" = IF(\"$fieldName\" = 1, 0, 1)");
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.UPDATEDCONFIG", "Updated configuration."));
				Director::redirect($this->Link());return array();
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NOTUPDATEDCONFIG", "Could not update configuration."));
		Director::redirect($this->Link());return array();
	} 

	function setpageflag($request){
		if($fieldName = $request->param("ID")) {
			$value = $request->param("OtherID") ?  1 : 0;
			if(in_array($fieldName, $this->updatableFields)) {		
				DB::query("UPDATE \"SiteTree\" SET \"$fieldName\" = $value");
				DB::query("UPDATE \"SiteTree_Live\" SET \"$fieldName\" = $value");
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.UPDATEDALLPAGES", "Updated all pages."));
				Director::redirect($this->Link());return array();
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NOTUPDATEDALLPAGES", "Could not update pages."));
		Director::redirect($this->Link());return array();
	}


	function update($request) {
		if(isset($_GET["fieldName"])) {
			$fieldNameString = $_GET["fieldName"];
			$fieldNameArray = explode("_", $fieldNameString);
			$fieldName = $fieldNameArray[0];
			if(in_array($fieldName, $this->updatableFields)) {
				if(!isset($_GET[$fieldNameString])) {
					$value = 0;
				}
				else {
					$value = Convert::raw2sql($_GET[$fieldNameString]);
				}
				$recordID = intval($fieldNameArray[1]);
				$record = DataObject::get_by_id("SiteTree", $recordID);
				if($record) {
					if(!$record->canPublish()) {
						return Security::permissionFailure($this);
					}
					DB::query("UPDATE \"SiteTree\" SET \"$fieldName\" = '".$value."' WHERE \"SiteTree\".\"ID\" = ".$recordID);
					DB::query("UPDATE \"SiteTree_Live\" SET \"$fieldName\" = '".$value."' WHERE \"SiteTree_Live\".\"ID\" = ".$recordID);
					$urlSegmentValue = '';
					if($fieldName == "Title") {
						$urlSegmentValue = $record->generateURLSegment($value);
					}
					if($urlSegmentValue) {
						DB::query("UPDATE \"SiteTree\" SET \"URLSegment\" = '".$urlSegmentValue."' WHERE \"SiteTree\".\"ID\" = ".$recordID);
						DB::query("UPDATE \"SiteTree_Live\" SET \"URLSegment\" = '".$urlSegmentValue."' WHERE \"SiteTree_Live\".\"ID\" = ".$recordID);
					}
					return  _t("MetaTagCMSControl.UPDATE", "Updated <i>".$record->Title."</i>");
				}
			}
		}
		return _t("MetaTagCMSControl.NOTUPDATE", "Record could not be updated.");
	}

	
	function MyPages() {
		$pages = DataObject::get("SiteTree", "ParentID = ".$this->ParentID. " AND \"ShowInSearch\" = 1");
		$dos = null;
		if($pages) {
			foreach($pages as $page) {
				if($page instanceOf ErrorPage || !$page->canView(new Member())) {
					$pages->remove($page);
				}
				$page->ChildrenLink = '';
				$page->MetaTitleIdentical = $page->MenuTitleIdentical = false;
				$page->MetaTitleAutoUpdate = $page->MenuTitleAutoUpdate = false;
				if(strtolower($page->MetaTitle) == strtolower($page->Title)) {
					$page->MetaTitleIdentical = true;
				}
				if(strtolower($page->MenuTitle) == strtolower($page->Title)) {
					$page->MenuTitleIdentical = true;
				}
				if($this->mySiteConfig()->UpdateMetaTitle && $page->AutomateMetatags) {
					$page->MetaTitleAutoUpdate = true;
				}
				if($this->mySiteConfig()->UpdateMenuTitle && $page->AutomateMetatags) {
					$page->MenuTitleAutoUpdate = true;
				}
				if(DataObject::get_one("SiteTree", "ParentID = ".$page->ID)) {
					$page->ChildrenLink = $this->createLevelLink($page->ID);
				}
				$linkArray = explode("/", $page->Link());
				$dos[$page->ID] = new DataObjectSet();
				if(count($linkArray)) {
					foreach($linkArray as $segment) {
						if($segment) {
							$dos[$page->ID]->push(new ArrayData(array("Segment" => $segment)));
						}
					}
				}
				$page->SegmentedLink = $dos[$page->ID] ;
				$page->GoOneUpLink = $this->GoOneUpLink();
				$dos = null;
			}
		}
		return $pages;
	}

	function FormAction() {
		return $this->Link("update");
	}

	function Link($action = '') {
		if($action) {
			$action .= "/";
		}
		return  "/" . self::$url_segment . "/" . $action;
	}

	function GoOneUpLink() {
		if( $this->ParentID ) {
			$oneUpPage = DataObject::get_by_id("SiteTree", $this->ParentID);
			if($oneUpPage) {
				return $this->createLevelLink($oneUpPage->ParentID);
			}
		}
	}

	function Message() {
		$msg = Session::get("MetaTagCMSControlMessage");
		Session::clear("MetaTagCMSControlMessage", "");
		return $msg;
	}

	function AlwaysUpdateMenuTitle() {
		return $this->mySiteConfig()->UpdateMenuTitle;
	}

	function AlwaysUpdateMetaTitle() {
		return $this->mySiteConfig()->UpdateMetaTitle;
	}

	function AlwaysUpdateMetaDescription() {
		return $this->mySiteConfig()->UpdateMetaDescription;
	}

	/* admin only functions */
	protected function updateAllMetaTitles() {
		if($this->mySiteConfig()->UpdateMetaTitle) { 
			DB::query("UPDATE \"SiteTree\" SET \"MetaTitle\" = \"Title\" WHERE AutomateMetatags = 1");
			DB::query("UPDATE \"SiteTree_Live\" SET \"MetaTitle\" = \"Title\" WHERE AutomateMetatags = 1");
		}
	}

	protected function createLevelLink ($id) {
		return $this->Link("childrenof") .  $id . "/";
	}

	protected function mySiteConfig() {
		return SiteConfig::current_site_config();
	}

	private function convert2TitleCase($title) {
		$title = trim($title);
		$words = explode(' ', $title);
		foreach ($words as $key => $word) {
			if ($key == 0 or !in_array($word, self::get_small_words_array())) {
				$words[$key] = ucwords(strtolower($word));
			}
		}
		$newtitle = implode(' ', $words);
		return $newtitle;
	} 
	
}


