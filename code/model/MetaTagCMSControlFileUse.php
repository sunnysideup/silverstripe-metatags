<?php


class MetaTagCMSControlFileUse extends DataObject {

	protected static $file_usage_array = array();

	//database
	public static $db = array(
		"DataObjectClassName" => "Varchar(255)",
		"DataObjectFieldName" => "Varchar(255)",
		"FileClassName" => "Varchar(255)"
	);

	public static function file_usage_count($fileID, $checkChildren = true, $quickBooleanCheck = false) {
		if(!isset(self::$file_usage_array[$fileID])) {
			self::$file_usage_array[$fileID] = 0;
			$checks = DataObject::get("MetaTagCMSControlFileUse");
			if($checks) {
				foreach($checks as $check) {
					$sql = "
						SHOW COLUMNS
						FROM \"{$check->DataObjectClassName}\"
						LIKE '{$check->DataObjectFieldName}ID'
					";
					$fieldExists = DB::query($sql);
					print_r($fieldExists);
					if($fieldExists && count($fieldExists) && mysql_num_rows($fieldExists) == 1) {
						if($check->DataObjectClassName == $check->FileClassName) {
							$where = " \"{$check->DataObjectFieldName}ID\" <> 0 AND ID = $fileID";
						}
						else {
							$where = "\"{$check->DataObjectFieldName}ID\" = $fileID";
						}
						$sql = "
							SELECT COUNT(*)
							FROM \"$check->DataObjectClassName\"
							WHERE $where
						";
						$result = DB::query($sql);
						if($result) {
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
			}
			$additionalUse = DB::query("
				SELECT COUNT(*)
				FROM \"SiteTree_ImageTracking\"
				WHERE \"FileID\" = $fileID
			")->value();
			if($quickBooleanCheck) {
				if($additionalUse) {
					return true;
				}
			}
			else {
				self::$file_usage_array[$fileID] = self::$file_usage_array[$fileID] + $additionalUse;
			}
		}
		if($checkChildren) {
			$children = DataObject::get("File", "ParentID =".$fileID);
			if($children) {
				foreach($children as $child) {
					if($quickBooleanCheck) {
						if(self::file_usage_count($child->ID, $quickBooleanCheck, $checkChildren)) {
							return true;
						}
					}
					else {
						self::$file_usage_array[$fileID] = self::$file_usage_array[$fileID] + self::file_usage_count($child->ID, $checkChildren, $quickBooleanCheck);
					}
				}
			}
		}
		return self::$file_usage_array[$fileID];
	}

	function requireDefaultRecords() {
		/**
		 *
		 * TO DO: ADD MANY MANY RELATIONSHIPS
		 *
		 **/
		parent::requireDefaultRecords();
		DB::query("DELETE FROM \"MetaTagCMSControlFileUse\";");
		$allClasses = ClassInfo::subclassesFor("DataObject");
		$fileClasses = ClassInfo::subclassesFor("File");
		//$allClassesExceptFiles = array_diff($allClasses, $fileClasses);
		foreach($allClasses as $class) {
			$hasOneArray = null;
			$newItems = (array) Object::uninherited_static($class, 'has_one');
			// Validate the data
			$hasOneArray = isset($hasOneArray) ? array_merge($newItems, (array)$hasOneArray) : $newItems;
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
							$obj->write();
							if(ClassInfo::is_subclass_of($class, "SiteTree")) {
								$obj = new MetaTagCMSControlFileUse();
								$obj->DataObjectClassName = $class."_Live";
								$obj->DataObjectFieldName = $fieldName;
								$obj->FileClassName = $hasOneClass;
								$obj->write();
							}
						}
					}
				}
			}
		}
		foreach($allClasses as $class) {
			$hasManyArray = null;
			$newItems = (array) Object::uninherited_static($class, 'has_many');
			// Validate the data
			$hasManyArray = isset($hasManyArray) ? array_merge($newItems, (array)$hasManyArray) : $newItems;
			if($hasManyArray && count($hasManyArray)) {
				foreach($hasManyArray as $fieldName => $hasManyClass) {
					if(in_array($hasManyClass, $fileClasses)) {
						if(!DB::query("
							SELECT COUNT(*)
							FROM \"MetaTagCMSControlFileUse\"
							WHERE \"DataObjectClassName\" = '$hasManyClass' AND  \"DataObjectFieldName\" = '$class' AND \"FileClassName\" = '$hasManyClass'
						")->value()) {
							$obj = new MetaTagCMSControlFileUse();
							$obj->DataObjectClassName = $hasManyClass;
							$obj->DataObjectFieldName = $class;
							$obj->FileClassName = $hasManyClass;
							$obj->write();
						}
					}
				}
			}
		}
		/*
		foreach($allClasses as $class) {
			$manyManyArray = null;
			$newItems = (array) Object::uninherited_static($class, 'many_many');
			$manyManyArray = isset($manyManyArray) ? array_merge($newItems, $manyManyArray) : $newItems;

			$newItems = (array) Object::uninherited_static($class, 'belongs_many_many');
			$manyManyArray = isset($manyManyArray) ? array_merge($newItems, $manyManyArray) : $newItems;
			if($manyManyArray && count($manyManyArray)) {
				foreach($manyManyArray as $table1 => $table2) {
					if(in_array($table1, $fileClasses)) {
						if(!DB::query("
							SELECT COUNT(*)
							FROM \"MetaTagCMSControlFileUse\"
							WHERE \"DataObjectClassName\" = '$hasManyClass' AND  \"DataObjectFieldName\" = '$class' AND \"FileClassName\" = '$hasManyClass'
						")->value()) {
							$obj = new MetaTagCMSControlFileUse();
							$obj->DataObjectClassName = $hasManyClass;
							$obj->DataObjectFieldName = $class;
							$obj->FileClassName = $hasManyClass;
							$obj->write();
						}
					}
				}
			}
		}
		*/
	}
}



