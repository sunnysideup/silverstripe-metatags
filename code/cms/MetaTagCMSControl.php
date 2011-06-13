<?php

class MetaTagCMSControl extends Controller {

	protected static $url_segment = 'metatagmanagement'; 

	protected $ParentID = 0;

	function init(){
		parent::init();
		Requirements::javascript("sapphire/thirdparty/jquery/jquery.js");
		Requirements::javascript("sapphire/thirdparty/jquery-form/jquery.form.js");
		Requirements::javascript("metatags/javascript/MetaTagCMSControl.js");
		Requirements::themedCSS("MetaTagCMSControl");
		$this->updateAllMetaTitles();
	}

	function index() {
		return $this->renderWith("MetaTagCMSControl");
	}

	function childrenof($request) {
		$this->ParentID = intval($request->param("ID"));
		return array();
	}

	function update($request) {
		print_r($_GET);
		die("END");
	}
	
	function MyPages() {
		$pages = DataObject::get("SiteTree", "ParentID = ".$this->ParentID);
		foreach($pages as $page) {
			$page->ChildrenLink = '';
			$page->MetaTitleIdentical = $page->MenuTitleIdentical = false;
			if(strtolower($page->MetaTitle) == strtolower($page->Title)) {
				$page->MetaTitleIdentical = true;
			}
			if(strtolower($page->MenuTitle) == strtolower($page->Title)) {
				$page->MenuTitleIdentical = true;
			}
			if(DataObject::get_one("SiteTree", "ParentID = ".$page->ID)) {
				$page->ChildrenLink = $this->createLevelLink($page->ID);
			}
			
		}
		return $pages;
	}

	function FormAction() {
		return "/".self::$url_segment . "/update/";
	}

	function GoOneUpLink() {
		if( $this->ParentID ) {
			$oneUpPage = DataObject::get_by_id("SiteTree", $this->ParentID);
			if($oneUpPage) {
				return $this->createLevelLink($oneUpPage->ParentID);
			}
		}
	}

	/* admin only functions */
	protected function updateAllMetaTitles() {
		if($m = Member::CurrentMember()) {
			if($m->IsAdmin()) {
				if($this->mySiteConfig()->UpdateMetaTitle) { 
					DB::query("UPDATE \"SiteTree\" SET \"MetaTitle\" = \"Title\" WHERE AutomateMetatags = 1");
					DB::query("UPDATE \"SiteTree_Live\" SET \"MetaTitle\" = \"Title\" WHERE AutomateMetatags = 1");
				}
			}
		}
	}

	protected function createLevelLink ($id) {
		return "/" . self::$url_segment . "/childrenof/" . $id . "/";
	}

	protected function mySiteConfig() {
		return DataObject::get_one("SiteConfig");
	}


	
}


