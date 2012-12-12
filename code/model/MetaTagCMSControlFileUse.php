<?php


class MetaTagCMSControlFileUse extends DataObject {

	protected static $file_usage_array = array();

	//database
	public static $db = array(
		"DataObjectClassName" => "Varchar(255)",
		"DataObjectFieldName" => "Varchar(255)",
		"FileClassName" => "Varchar(255)",
		"ConnectionType" => "Enum('HAS_ONE,HAS_MANY,MANY_MANY,BELONGS_MANY_MANY')"
	);

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		//start again
		DB::query("DELETE FROM \"MetaTagCMSControlFileUse\";");
		//get all classes
		$allClasses = ClassInfo::subclassesFor("DataObject");
		//get all file classes
		$fileClasses = ClassInfo::subclassesFor("File");
		// files can have files attached to them so we have commented out the line below
		//$allClassesExceptFiles = array_diff($allClasses, $fileClasses);
		//lets go through class
		foreach($allClasses as $class) {
			//HAS_ONE
			$hasOneArray = null;
			//get the has_one fields
			$newItems = (array) Object::uninherited_static($class, 'has_one');
			// Validate the data
			//do we need this?
			$hasOneArray = $newItems; //isset($hasOneArray) ? array_merge($newItems, (array)$hasOneArray) : $newItems;
			//lets inspect
			if($hasOneArray && count($hasOneArray)) {
				foreach($hasOneArray as $fieldName => $hasOneClass) {
					if(in_array($hasOneClass, $fileClasses)) {
						if(!DB::query("
							SELECT COUNT(*)
							FROM \"MetaTagCMSControlFileUse\"
							WHERE \"DataObjectClassName\" = '$class' AND  \"DataObjectFieldName\" = '$fieldName' AND \"FileClassName\" = '$hasOneClass'
						")->value()) {
							$obj = new MetaTagCMSControlFileUse();
							$obj->DataObjectClassName = $class;
							$obj->DataObjectFieldName = $fieldName;
							$obj->FileClassName = $hasOneClass;
							$obj->ConnectionType = "HAS_ONE";
							$obj->write();
							if(ClassInfo::is_subclass_of($class, "SiteTree")) {
								$obj = new MetaTagCMSControlFileUse();
								$obj->DataObjectClassName = $class."_Live";
								$obj->DataObjectFieldName = $fieldName;
								$obj->FileClassName = $hasOneClass;
								$obj->ConnectionType = "HAS_ONE";
								$obj->write();
							}
						}
					}
				}
			}
			$hasManyArray = null;
			$newItems = (array) Object::uninherited_static($class, 'has_many');
			// Validate the data
			$hasManyArray = $newItems; //isset($hasManyArray) ? array_merge($newItems, (array)$hasManyArray) : $newItems;
			if($hasManyArray && count($hasManyArray)) {
				foreach($hasManyArray as $fieldName => $hasManyClass) {
					if(in_array($hasManyClass, $fileClasses)) {
						if(!DB::query("
							SELECT COUNT(*)
							FROM \"MetaTagCMSControlFileUse\"
							WHERE \"DataObjectClassName\" = '$hasManyClass' AND  \"DataObjectFieldName\" = '$fieldName' AND \"FileClassName\" = '$class'
						")->value()) {
							$obj = new MetaTagCMSControlFileUse();
							$obj->DataObjectClassName = $hasManyClass;
							$obj->DataObjectFieldName = $fieldName;
							$obj->FileClassName = $class;
							$obj->ConnectionType = "HAS_MANY";
							$obj->write();
						}
					}
				}
			}
			$manyManyArray = null;
			$newItems = (array) Object::uninherited_static($class, 'many_many');
			$manyManyArray = $newItems;

			$newItems = (array) Object::uninherited_static($class, 'belongs_many_many');
			$manyManyArray = isset($manyManyArray) ? array_merge($newItems, $manyManyArray) : $newItems;
			if($manyManyArray && count($manyManyArray)) {
				foreach($manyManyArray as $fieldName => $manyManyClass) {
					if(in_array($manyManyClass, $fileClasses)) {
						if(!DB::query("
							SELECT COUNT(*)
							FROM \"MetaTagCMSControlFileUse\"
							WHERE \"DataObjectClassName\" = '$manyManyClass' AND  \"DataObjectFieldName\" = '$fieldName' AND \"FileClassName\" = '$fieldName'
						")->value()) {
							$obj = new MetaTagCMSControlFileUse();
							$obj->DataObjectClassName = $class;
							$obj->DataObjectFieldName = $fieldName;
							$obj->FileClassName = $manyManyClass;
							$obj->ConnectionType = "MANY_MANY";
							$obj->write();
						}
						if(!DB::query("
							SELECT COUNT(*)
							FROM \"MetaTagCMSControlFileUse\"
							WHERE \"DataObjectClassName\" = '$manyManyClass' AND  \"DataObjectFieldName\" = '$fieldName' AND \"FileClassName\" = '$class'
						")->value()) {
							$obj = new MetaTagCMSControlFileUse();
							$obj->DataObjectClassName = $manyManyClass;
							$obj->DataObjectFieldName = $fieldName;
							$obj->FileClassName = $class;
							$obj->ConnectionType = "BELONGS_MANY_MANY";
							$obj->write();
						}
					}
				}
			}
		}
	}


	public static function file_usage_count($fileID, $quickBooleanCheck = false) {
		if(!isset(self::$file_usage_array[$fileID])) {
			self::$file_usage_array[$fileID] = 0;
			$sql = "SELECT COUNT(ID) FROM \"File\" WHERE \"ParentID\" = {$fileID};";
			$result = DB::query($sql, false);
			$childCount = $result->value();
			if($childCount) {
				self::$file_usage_array[$fileID] = $childCount;
				return self::$file_usage_array[$fileID];
			}
			$checks = DataObject::get("MetaTagCMSControlFileUse");
			if($checks) {
				foreach($checks as $check) {
					$sql = "";
					switch ($check->ConnectionType) {
						case "HAS_ONE":
							$sql = "
								SELECT COUNT(\"{$check->DataObjectClassName}\".\"ID\")
								FROM \"{$check->DataObjectClassName}\"
								WHERE \"{$check->DataObjectFieldName}ID\" = {$fileID};
							";
							break;
						case "HAS_MANY":
							$sql = "
								SELECT COUNT(\"{$check->DataObjectClassName}\".\"ID\")
								FROM \"{$check->DataObjectClassName}\"
									INNER JOIN  {$check->FileClassName}
										ON \"{$check->DataObjectClassName}\".\"{$check->FileClassName}ID\" = \"{$check->FileClassName}\".\"ID\"
								WHERE \"{$check->DataObjectClassName}\".\"ID\" = {$fileID};
							";
							break;
						case "MANY_MANY":
							$sql = "
								SELECT COUNT(\"{$check->DataObjectClassName}_{$check->DataObjectFieldName}\".\"ID\")
								FROM \"{$check->DataObjectClassName}_{$check->DataObjectFieldName}\"
								WHERE \"{$check->FileClassName}ID\" = $fileID;
							";
							break;
					}
					$result = DB::query($sql, false);
					$count = $result->value();
					if($count) {
						if($quickBooleanCheck) {
							return true;
						}
						else {
							self::$file_usage_array[$fileID] += $count;
						}
					}
				}
			}
		}
		return self::$file_usage_array[$fileID];
	}

}



