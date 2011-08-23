<?php

class MetaTagCMSControlFiles extends Controller {

	protected static $url_segment = 'metatagmanagementfiles'; 
		static function set_url_segment($s){self::$url_segment = $s;}
		static function get_url_segment(){return self::$url_segment;}

	protected static $records_per_page = 10; 
		static function set_records_per_page($i){self::$records_per_page = $i;}
		static function get_records_per_page(){return self::$records_per_page;}

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

	/***************************************************
	 * ACTIONS                                         *
	 *                                                 *
	 ***************************************************/ 

	function index() {
		return $this->renderWith("MetaTagCMSControlFiles");
	}

	function childrenof($request) {
		$this->ParentID = intval($request->param("ID"));
		return array();
	}

	function lowercase($request) {
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				foreach($this->tableArray as $table) {
					DB::query("UPDATE \"$table\" SET \"$fieldName\" = LOWER(\"$fieldName\")");
					echo "UPDATE \"$table\" SET \"$fieldName\" = LOWER(\"$fieldName\")";
				}
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.UPDATEDTOLOWERCASE", "Records updated to <i>lower case</i>"));
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
					$rows = DB::query("SELECT \"ID\", \"$fieldName\" FROM \"$table\";");
					foreach($rows as $row) {
						$newValue = Convert::raw2sql($this->convert2TitleCase($row[$fieldName]));
						DB::query("UPDATE \"$table\" SET \"$fieldName\" = '$newValue' WHERE ID = ".$row["ID"]);
					}
				}
				Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.UPDATEDTOTITLECASE", "Records updated to <i>title case</i>"));
				return $this->returnAjaxOrRedirectBack();
			}
		}
		Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.NOTUPDATEDTOTITLECASE", "Records could not be updated to <i>title case</i>."));
		return $this->returnAjaxOrRedirectBack();
	}

	function copyfromtitle($request){
		if($fieldName = $request->param("ID")) {
			if(in_array($fieldName, $this->updatableFields)) {
				foreach($this->tableArray as $table) {
					DB::query("UPDATE \"$table\" SET \"$fieldName\" = \"Title\"");
				}
				Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.COPIEDFROMTITLE", "Copied from title."));
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
					$record = DataObject::get_by_id($table, $recordID);
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
			$folder = Folder::findOrMake("ZzzeRecyclingBin");
			if($folder) {
				$file = DataObject::get_by_id("File", $id);
				if($file) {
					$file->ParentID = $folder->ID;
					$file->write();
					Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.FILERECYCLED", "File &quot;".$file->Title."&quot; has been recycled."));
					return $this->returnAjaxOrRedirectBack();
				}
			}
		}
		Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.FILENOTRECYCLED", "ERROR: File &quot;".$file->Title."&quot; could NOT be recycled."));
		return $this->returnAjaxOrRedirectBack();
	}

	protected function returnAjaxOrRedirectBack(){
		if(Director::is_ajax()) {
			return $this->renderWith("MetaTagCMSControlFilesAjax");
		}
		else {
			Director::redirect($this->Link());
			return array();
		}
	}

	/***************************************************
	 * CONTROLS                                        *
	 *                                                 *
	 ***************************************************/ 


	function MyRecords() {
		//Filesystem::sync($this->ParentID);
		$files = DataObject::get($this->tableArray[0], "ParentID = ".$this->ParentID, '', '', $this->myRecordsLimit());
		$dos = null;
		if($files) {
			foreach($files as $file) {
				$file->ChildrenLink = '';
				if(DataObject::get_one($this->tableArray[0], "ParentID = ".$file->ID)) {
					$file->ChildrenLink = $this->createLevelLink($file->ID);
				}
				if(!$file->canView(new Member()) || !file_exists($file->getFullPath())) {
					$files->remove($file);
				}
				if($file instanceOf Image) {
					$file->Type == "Image";
				}
				elseif($file instanceOf Folder) {
					$file->Type == "Folder";
					$file->Icon == "metatags/images/Folder.png";
				}
				else {
					$files->remove($file);
				}
				$file->GoOneUpLink = $this->GoOneUpLink();
				$file->RecycleLink = $this->makeRecycleLink($file->ID);
				$file->UsageCount = MetaTagCMSControlFileUse::file_usage_count($file->ID);
				$dos[$file->ID] = new DataObjectSet();
				$segmentArray = array();
				$item = $file;
				$segmentArray[] = array("URLSegment" => $item->Name, "ID" => $item->ID, "ClassName" => $item->ClassName, "Title" => $item->Title, "Link" => "/".$item->Filename);
				while($item && $item->ParentID) {
					$item = DataObject::get_by_id($this->tableArray[0], $item->ParentID);
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
		if( $this->ParentID ) {
			$oneUpPage = DataObject::get_by_id($this->tableArray[0], $this->ParentID);
			if($oneUpPage) {
				return $this->createLevelLink($oneUpPage->ParentID);
			}
		}
	}

	protected function makeRecycleLink($id) {
		return $this->Link("recycle").$id."/";
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


