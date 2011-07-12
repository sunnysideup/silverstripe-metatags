<?php

class MetaTagCMSControlPages extends MetaTagCMSControlFiles {


	/***************************************************
	 * CONFIG                                          *
	 *                                                 *
	 ***************************************************/ 

	protected static $url_segment = 'metatagmanagementpages'; 
		static function set_url_segment($s){self::$url_segment = $s;}
		static function get_url_segment(){return self::$url_segment;}

	protected static $small_words_array = array('of','a','the','and','an','or','nor','but','is','if','then','else','when','at','from','by','on','off','for','in','out','over','to','into','with');
		static function set_small_words_array($a){self::$small_words_array = $a;}
		static function get_small_words_array(){return self::$small_words_array;}
		
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

	/**
	 * First table is main table - e.g. $this->tableArray[0] should work
	 *
	 **/ 
	protected $tableArray = array("SiteTree", "SiteTree_Live");


	/***************************************************
	 * ACTIONS                                         *
	 *                                                 *
	 ***************************************************/ 


	function index() {
		return $this->renderWith("MetaTagCMSControlPages");
	}


	function copyfromcontent($request) {
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				foreach($this->tableArray as $table) {
					$rows = DB::query("SELECT \"$table\".\"ID\", \"$table\".\"Content\" FROM \"$table\" WHERE \"$table\".\"$fieldName\" = '' OR \"$table\".\"$fieldName\" IS NULL;");
					foreach($rows as $row) {
						$newValue = Convert::raw2sql(DBField::create("HTMLText", $row["Content"])->Summary(MetaTagAutomation::get_meta_desc_length(), 15, ""));
						DB::query("UPDATE \"$table\" SET \"$fieldName\" = '$newValue' WHERE ID = ".$row["ID"]);
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
				foreach($this->tableArray as $table) {
					DB::query("UPDATE \"$table\" SET \"$fieldName\" = $value");
				}
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
				foreach($this->tableArray as $table) {
					$record = DataObject::get_by_id($table, $recordID);
					if($record) {
						if(method_exists($record, "canPublish") && !$record->canPublish()) {
							return Security::permissionFailure($this);
						}
						DB::query("UPDATE \"$table\" SET \"$fieldName\" = '".$value."' WHERE \"$table\".\"ID\" = ".$recordID);
						$urlSegmentValue = '';
						if($fieldName == "Title") {
							$urlSegmentValue = $record->generateURLSegment($value);
						}
						if($urlSegmentValue) {
							DB::query("UPDATE \"$table\" SET \"URLSegment\" = '".$urlSegmentValue."' WHERE \"$table\".\"ID\" = ".$recordID);
						}
					}
				}
				return  _t("MetaTagCMSControl.UPDATE", "Updated <i>".$record->Title."</i>");
			}
		}
		return _t("MetaTagCMSControl.NOTUPDATE", "Record could not be updated.");
	}



	/***************************************************
	 * CONTROLS                                        *
	 *                                                 *
	 ***************************************************/ 

	
	function MyRecords() {
		$excludeWhere = "AND \"ShowInSearch\" = 1 AND \"ClassName\" <> 'ErrorPage'";
		$pages = DataObject::get($this->tableArray[0], "ParentID = ".$this->ParentID. " ".$excludeWhere, '', '', $this->myRecordsLimit());
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
				if(DataObject::get_one($this->tableArray[0], "ParentID = ".$page->ID. " ".$excludeWhere)) {
					$page->ChildrenLink = $this->createLevelLink($page->ID);
				}
				
				$dos[$page->ID] = new DataObjectSet();
				$segmentArray = array();
				$item = $page;
				$segmentArray[] = array("URLSegment" => $item->URLSegment, "ID" => $item->ID, "ClassName" => $item->ClassName, "Title" => $item->Title);
				while($item && $item->ParentID) {
					$item = DataObject::get_by_id($this->tableArray[0], $item->ParentID);
					if($item) {
						$segmentArray[] = array("URLSegment" => $item->URLSegment, "ID" => $item->ID, "ClassName" => $item->ClassName, "Title" => $item->Title, "Link" => $this->createLevelLink(intval($item->ParentID)-0));
					}
				}
				$segmentArray = array_reverse($segmentArray);
				foreach($segmentArray as $segment) {
					$dos[$page->ID]->push(new ArrayData($segment));
				}
				$page->ParentSegments = $dos[$page->ID] ;
				$page->GoOneUpLink = $this->GoOneUpLink();
				$dos = null;
			}
		}
		return $pages;
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


	function Link($action = '') {
		if($action) {
			$action .= "/";
		}
		return  "/" . self::$url_segment . "/" . $action;
	}


	/***************************************************
	 * PROTECTED                                       *
	 *                                                 *
	 ***************************************************/ 

	

	/* admin only functions */
	protected function updateAllMetaTitles() {
		if($this->mySiteConfig()->UpdateMetaTitle) {
			foreach($this->tableArray as $table) {
				DB::query("UPDATE \"$table\" SET \"MetaTitle\" = \"Title\" WHERE AutomateMetatags = 1");
			}
		}
	}

	protected function convert2TitleCase($title) {
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


