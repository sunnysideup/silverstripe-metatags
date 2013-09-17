<?php

class MetaTagCMSControlFiles extends Controller {

	private static $allowed_actions = array(
		"cleanupfolders" => "ADMIN",
		"childrenof" => "ADMIN",
		"lowercase" => "ADMIN",
		"titlecase" => "ADMIN",
		"upgradefilenames" => "ADMIN",
		"recyclefolder" => "ADMIN",
		"copyfromtitle" => "ADMIN",
		"update" => "ADMIN",
		"recycle" => "ADMIN"
	);

	private static $recycling_bin_name = 'ZzzeRecyclingBin';

	private static $url_segment = 'metatagmanagementfiles';

	private static $records_per_page = 10;

	protected $ParentID = 0;

	protected $updatableFields = array(
		"Title",
		"Content"
	);

	/**
	 * First table is main table - e.g. $this->tableArray[0] should work
	 *
	 **/
	protected $tableArray = array("File");

	function init(){
		parent::init();
		$member = Member::currentUser();
			// Default security check for LeftAndMain sub-class permissions
		if(!Permission::checkMember($member, "CMS_ACCESS_LeftAndMain")) {
			return Security::permissionFailure($this);
		}
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-form/jquery.form.js");
		Requirements::javascript("metatags/javascript/MetaTagCMSControl.js");
		Requirements::themedCSS("MetaTagCMSControl");
		if($parentID = intval($this->request->getVar("childrenof"))) {
			$this->ParentID = $parentID;
		}
	}

	/***************************************************
	 * ACTIONS                                         *
	 *                                                 *
	 ***************************************************/

	function index() {
		return $this->renderWith("MetaTagCMSControlFiles");
	}

	function cleanupfolders(){
		return $this->redirect(MetaTagCMSFixImageLocations::my_link());
	}

	function childrenof($request) {
		$id = intval($request->param("ID"));
		if($id) {
			$this->ParentID = $id;
		}
		return array();
	}

	function lowercase($request) {
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				foreach($this->tableArray as $table) {
					$items = $table::get()->where("BINARY \"$fieldName\" <> LOWER(\"$fieldName\")")->sort("LastEdited", "ASC")->limit(100);
					if($items && $items->count()) {
						$item->$fieldName = strtolower($item->$fieldName);
						$item->write();
					}
				}
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.UPDATEDTOLOWERCASE", $items->count()." records updated to <i>lower case</i>, please repeat if you have more than 100 records."));
				return $this->returnAjaxOrRedirectBack();
			}
		}
		Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.NOTUPDATEDTOLOWERCASE", "Records could not be updated <i>to lower case</i>."));
		return $this->returnAjaxOrRedirectBack();
	}

	function titlecase($request){
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				foreach($this->tableArray as $table) {
					$items = $table::get()->sort("LastEdited", "ASC")->limit(100);
					if($items && $items->count()) {
						$newValue = Convert::raw2sql($this->convert2TitleCase($item->$fieldName));
						$item->$fieldName = $newValue;
						$item->write();
					}
				}
				Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.UPDATEDTOTITLECASE", $items->count()." records updated to <i>title case</i>, please repeat if you have more than 100 records."));
				return $this->returnAjaxOrRedirectBack();
			}
		}
		Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.NOTUPDATEDTOTITLECASE", "Records could not be updated to <i>title case</i>."));
		return $this->returnAjaxOrRedirectBack();
	}


	function upgradefilenames($request) {
		if($folderID = intval($request->param("ID"))) {
			$verbose = Director::is_ajax() ? false : true;
			if($count = MetaTagCMSControlFileUse::upgrade_file_names($folderID, $verbose)) {
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NAMESUPDATED", "Updated $count file names."));
				return $this->returnAjaxOrRedirectBack($verbose);
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NAMESNOTUPDATED", "ERROR: Did not update any file names"));
		return $this->returnAjaxOrRedirectBack();
	}

	function recyclefolder($request) {
		if($folderID = intval($request->param("ID"))) {
			$verbose = Director::is_ajax() ? false : true;
			if($count = MetaTagCMSControlFileUse::recycle_folder($folderID, $verbose)) {
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.RECYCLED_FILES", "Recycled unused files in this folder."));
				return $this->returnAjaxOrRedirectBack($verbose);
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.DID_NOT_RECYCLE_FILES", "ERROR: Could not recycle all files"));
		return $this->returnAjaxOrRedirectBack();
	}


	function copyfromtitle($request){
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				foreach($this->tableArray as $table) {
					$items = $table::get()->where("\"$fieldName\" <> \"Title\"")->sort("LastEdited", "ASC", null, 100);
					if($items && $items->count()) {
						$item->$fieldName = $item->Title;
						$item->write();
					}
				}
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.COPIEDFROMTITLE", $items->count()." records <i>copied from title</i>, please repeat if you have more than 100 records."));
				return $this->returnAjaxOrRedirectBack();
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NOTCOPIEDFROMTITLE", "Copy not successful"));
		return $this->returnAjaxOrRedirectBack();
	}

	function update(){
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
					$record = $table::get()->byID($recordID);
					$extension = '';
					if(!($record instanceOf Folder)) {
						$extension = ".".$record->getExtension();
					}
					if($record) {
						$record->$fieldName = $value;
						//echo $fieldName.$record->ID.$value;
						if($fieldName == "Title") {
							$record->setName($value.$extension);
						}
						$record->write();
					}
					else {
						return  _t("MetaTagCMSControl.COULDNOTFINDRECORD", "Could not find record");
					}
				}
				return  _t("MetaTagCMSControl.UPDATE", "Updated <i>".$record->Title."</i>");
			}
			else {
				return  _t("MetaTagCMSControl.NOTALLOWEDTOUPDATEFIELD", "You are not allowed to update this field.");
			}
		}
		return _t("MetaTagCMSControl.NOTUPDATE", "Record could not be updated.");
	}

	function recycle($request) {
		$id = intval($request->param("ID"));
		if($id) {
			$file = File::get()->byID($id);
			if($file) {
				if(MetaTagCMSControlFileUse_RecyclingRecord::recycle($file)) {
					Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.FILERECYCLED", "File &quot;".$file->Title."&quot; has been recycled."));
					return $this->returnAjaxOrRedirectBack();
				}
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.FILENOTRECYCLED", "ERROR: File &quot;".$file->Title."&quot; could NOT be recycled."));
		return $this->returnAjaxOrRedirectBack();
	}

	protected function returnAjaxOrRedirectBack($verbose = false){
		if(Director::is_ajax()) {
			return $this->renderWith("MetaTagCMSControlFilesAjax");
		}
		else {
			if(!$verbose) {
				$this->redirect($this->Link());
			}
			return array();
		}
	}

	/***************************************************
	 * CONTROLS                                        *
	 *                                                 *
	 ***************************************************/


	function MyRecords() {
		//Filesystem::sync($this->ParentID);
		$className = $this->tableArray[0];
		$files = $className::get()
			->filter("ParentID", $this->ParentID)
			->sort("IF(\"ClassName\" = 'Folder', 0, 1) ASC, \"Name\" ASC ")
			->limit($this->myRecordsLimit());
		$dos = null;
		if($files && $files->count()) {
			foreach($files as $file) {
				$file->ChildrenLink = '';
				if(!$file->canView() ) {
					$file->Error = "YOU DO NOT HAVE PERMISSION TO VIEW THIS FILE.";
				}
				if(!file_exists($file->getFullPath())) {
					$file->Error = "FILE CAN NOT BE FOUND.";
				}
				if(
				$className::get()
					->filter("ParentID", $file->ID)
					->First()
				) {
					$file->ChildrenLink = $this->createLevelLink($file->ID);
				}
				$file->UsageCount = MetaTagCMSControlFileUse::file_usage_count($file, false, $saveListOfPlaces = true);
				if($file instanceOf Folder) {
					$file->Type == "Folder";
					$file->Icon == "metatags/images/Folder.png";
				}
				elseif($file->UsageCount) {
					$file->ListOfPlaces = MetaTagCMSControlFileUse::retrieve_list_of_places($file->ID);
				}
				if(!$file->ListOfPlaces) {
					unset($file->ListOfPlaces);
				}
				$file->GoOneUpLink = $this->GoOneUpLink();
				$file->RecycleLink = $this->makeRecycleLink($file->ID);
				$dos[$file->ID] = new ArrayList();
				$segmentArray = array();
				$item = $file;
				$segmentArray[] = array("URLSegment" => $item->Name, "ID" => $item->ID, "ClassName" => $item->ClassName, "Title" => $item->Title, "Link" => "/".$item->Filename);
				$x = 0;
				while($item && $item->ParentID && $x < 10) {
					$x++;
					$item = $className::get()->byID($item->ParentID);
					if($item) {
						$segmentArray[] = array("URLSegment" => $item->Name, "ID" => $item->ID, "ClassName" => $item->ClassName, "Title" => $item->Title, "Link" => $this->createLevelLink(intval($item->ParentID)-0));
					}
				}
				$segmentArray = array_reverse($segmentArray);
				foreach($segmentArray as $segment) {
					$dos[$file->ID]->push(new ArrayData($segment));
				}
				$file->ParentSegments = $dos[$file->ID] ;
				$dos = null;
			}
		}
		return $files;
	}


	function FormAction() {
		return Director::absoluteBaseURL().$this->Link("update");
	}

	function Link($action = '') {
		if($action) {
			$action .= "/";
		}
		return  "/" . self::$url_segment . "/" . $action;
	}

	function GoOneUpLink() {
		$className = $this->tableArray[0];
		if( $this->ParentID ) {
			$oneUpPage = $className::get()->byID($this->ParentID);
			if($oneUpPage) {
				return $this->createLevelLink($oneUpPage->ParentID);
			}
		}
	}

	protected function makeRecycleLink($id) {
		if(!isset($_GET["start"])) {
			$start = 0;
		}
		else {
			$start = intval($_GET["start"]);
		}
		return $this->Link("recycle").$id."/?childrenof=".$this->ParentID."&amp;start=".$start;
	}

	function Message() {
		$msg = Session::get("MetaTagCMSControlMessage");
		Session::clear("MetaTagCMSControlMessage", "");
		return $msg;
	}


	/***************************************************
	 * PROTECTED                                       *
	 *                                                 *
	 ***************************************************/

	protected function createLevelLink ($id) {
		return $this->Link("childrenof") .  $id . "/";
	}

	protected function mySiteConfig() {
		return SiteConfig::current_site_config();
	}

	protected function myRecordsLimit(){
		$start = 0;
		if(isset($_GET["start"])) {
			$start = intval($_GET["start"]);
		}
		return "$start, ".self::get_records_per_page();
	}


}


