<?php

/**
 *
 * SITUATIONS
 *
 * 0. A NonFileObject.HasOne File
 *    example: SiteTree HasOne Image
 *    type = HAS_ONE
 *    DataObjectIsFile = false
 *    FileIsFile = true
 *
 * 1. A NonFileObject.HasMany Files = see (4)
 *
 * 2. A NonFileObject.ManyMany Files
 *    example: SiteTree ManyMany Images
 *    type = MANY_MANY
 *    DataObjectIsFile = false
 *    FileIsFile = true
 *
 * 3. A NonFileObject.BelongsManyMany Files
 *    example: SiteTree BELONGS_MANY_MANY Images
 *    type = BELONGS_MANY_MANY
 *    DataObjectIsFile = false
 *    FileIsFile = true
 *
 * 4. A File.HasOne NonFileObject
 *    example: Image HasOne SiteTree
 *    type = HAS_ONE
 *    DataObjectIsFile = false
 *    FileIsFile = true
 *
 * 5. A File.HasMany NonFileObjects = see 1
 *
 * 6. A File.HasOne AnotherFile
 *    example: Image HAS_ONE Images
 *    type = BELONGS_MANY_MANY
 *    DataObjectIsFile = true
 *    FileIsFile = true
 *
 * 7. A File.HasMany Files = see (6)
 *
 * 8. A File.ManyMany Files
 *    example: Image MANY_MANY Images
 *    type = MANY_MANY
 *    DataObjectIsFile = true
 *    FileIsFile = true
 *
 * 9. A File.BelongsManyMany Files
 *    example: Image MANY_MANY Images
 *    type = BELONGS_MANY_MANY
 *    DataObjectIsFile = true
 *    FileIsFile = true
 */

class MetaTagCMSControlFileUse extends DataObject
{

    /**
     * debug data
     * @var Boolean
     */
    private static $debug = false;

    /**
     * keep data stored to reduce overhead
     * @var Array
     */
    private static $file_usage_array = array();

    /**
     * keep data stored to reduce overhead
     * @var Array
     */
    private static $list_of_places_dos = array();

    /**
     * classes to exclude
     * @var Array
     */
    private static $excluded_classes = array();

    /**
     * list of classes that are files
     * @var Array
     */
    private static $file_classes = array();

    /**
     * standard SS variable
     * @var Array
     */
    private static $db = array(
        "DataObjectClassName" => "Varchar(255)",
        "DataObjectFieldName" => "Varchar(255)",
        "FileClassName" => "Varchar(255)",
        "DataObjectIsFile" => "Boolean",
        "FileIsFile" => "Boolean",
        "BothAreFiles" => "Boolean",
        "IsLiveVersion" => "Boolean",
        "ConnectionType" => "Enum('DB,HAS_ONE,MANY_MANY,BELONGS_MANY_MANY')"
    );

    /**
     * create a list of tables and fields that need to be checked
     * see class comments
     */
    public function requireDefaultRecords()
    {
        self::$file_classes = ClassInfo::subclassesFor("File");
        parent::requireDefaultRecords();
        //start again
        DB::query("DELETE FROM \"MetaTagCMSControlFileUse\";");
        //get all classes
        $allClasses = ClassInfo::subclassesFor("DataObject");
        //classes from sitetree are linked through image tracker
        $siteTreeSubclasses = ClassInfo::subclassesFor("SiteTree");
        // files can have files attached to them so we have commented out the line below
        //$allClassesExceptFiles = array_diff($allClasses, $fileClasses);
        //lets go through class
        foreach ($allClasses as $class) {
            if (!in_array($class, $siteTreeSubclasses)) {
                //DB
                $dbArray = null;
                //get the has_one fields
                $newItems = (array) Config::inst()->get($class, 'db');

                // Validate the data
                //do we need this?
                $dbArray = $newItems; //isset($hasOneArray) ? array_merge($newItems, (array)$hasOneArray) : $newItems;
                //lets inspect
                if ($dbArray && count($dbArray)) {
                    foreach ($dbArray as $fieldName => $fieldType) {
                        if ($fieldType == "HTMLText") {
                            $this->createNewRecord($class, $fieldName, "", "DB");
                        }
                    }
                }
            }

            //HAS_ONE
            $hasOneArray = null;
            //get the has_one fields
            $newItems = (array) Config::inst()->get($class, 'has_one');
            // Validate the data
            //do we need this?
            $hasOneArray = $newItems; //isset($hasOneArray) ? array_merge($newItems, (array)$hasOneArray) : $newItems;
            //lets inspect
            if ($hasOneArray && count($hasOneArray)) {
                foreach ($hasOneArray as $fieldName => $hasOneClass) {
                    $this->createNewRecord($class, $fieldName, $hasOneClass, "HAS_ONE");
                }
            }

            //HAS_MANY
            $hasManyArray = null;
            $newItems = (array) Config::inst()->get($class, 'has_many');
            // Validate the data
            $hasManyArray = $newItems; //isset($hasManyArray) ? array_merge($newItems, (array)$hasManyArray) : $newItems;
            if ($hasManyArray && count($hasManyArray)) {
                foreach ($hasManyArray as $fieldName => $hasManyClass) {
                    //NOTE - We are referencing HAS_ONE here on purpose!!!!
                    $hasManyCheckItems = (array) Config::inst()->get($class, 'has_one');
                    $hasManyFound = false;
                    foreach ($hasManyCheckItems as $hasManyfieldName => $hasManyForeignClass) {
                        if ($hasManyForeignClass == $class) {
                            $this->createNewRecord($hasManyClass, $hasManyfieldName, $hasManyForeignClass, "HAS_ONE");
                            $hasManyFound = true;
                        }
                    }
                    //now we have to guess!
                    if (!$hasManyFound) {
                        $this->createNewRecord($hasManyClass, $class, $class, "HAS_ONE");
                        $hasManyFound = true;
                    }
                }
            }
            //many many
            $manyManyArray = null;
            $newItems = (array) Config::inst()->get($class, 'many_many');
            $manyManyArray = $newItems;
            //belongs many many
            $newItems = (array) Config::inst()->get($class, 'belongs_many_many');
            $manyManyArray = isset($manyManyArray) ? array_merge($newItems, $manyManyArray) : $newItems;
            //do both
            if ($manyManyArray && count($manyManyArray)) {
                foreach ($manyManyArray as $fieldName => $manyManyClass) {
                    $this->createNewRecord($class, $fieldName, $manyManyClass, "MANY_MANY");
                    $this->createNewRecord($manyManyClass, $fieldName, $class, "BELONGS_MANY_MANY");
                }
            }
        }
    }

    private function createNewRecord($dataObjectClassName, $dataObjectFieldName, $fileClassName, $connectionType)
    {
        //exceptions....
        if (in_array($dataObjectClassName, $this->Config()->get("excluded_classes"))  || in_array($fileClassName, $this->Config()->get("excluded_classes"))) {
            return;
        }
        if ($dataObjectFieldName == "ImageTracking" || $dataObjectFieldName == "BackLinkTracking") {
            return;
        }

        //at least one of them is a file...
        if (in_array($dataObjectClassName, $this->Config()->get("file_classes")) || in_array($fileClassName, $this->Config()->get("file_classes"))) {
            if (! DB::query("
				SELECT COUNT(*)
				FROM \"MetaTagCMSControlFileUse\"
				WHERE \"DataObjectClassName\" = '$dataObjectClassName' AND  \"DataObjectFieldName\" = '$dataObjectFieldName' AND \"FileClassName\" = '$fileClassName'
			")->value()) {
                $dataObjectIsFile =  in_array($dataObjectClassName, $this->Config()->get("file_classes")) ? 1 : 0;
                $fileIsFile =  in_array($fileClassName, $this->Config()->get("file_classes")) ? 1 : 0;
                for ($i = 0; $i < ($dataObjectIsFile + $fileIsFile); $i++) {
                    $computedDataObjectIsFile = false;
                    $computedFileIsFile = false;
                    if ($i == 0 && $dataObjectIsFile) {
                        $computedDataObjectIsFile = true;
                    }
                    if (($i == 1 && $fileIsFile) || !$dataObjectIsFile) {
                        $computedFileIsFile = true;
                    }
                    $bothAreFiles = false;
                    if ($dataObjectIsFile && $fileIsFile) {
                        $bothAreFiles = true;
                    }
                    $obj = new MetaTagCMSControlFileUse();
                    $obj->DataObjectClassName = $dataObjectClassName;
                    $obj->DataObjectFieldName = $dataObjectFieldName;
                    $obj->FileClassName = $fileClassName;
                    $obj->ConnectionType = $connectionType;
                    $obj->DataObjectIsFile = $computedDataObjectIsFile;
                    $obj->FileIsFile =  $computedFileIsFile;
                    $obj->BothAreFiles =  $bothAreFiles;
                    $obj->IsLiveVersion = 0;
                    $obj->write();
                    if (is_subclass_of($dataObjectClassName, "SiteTree")) {
                        $obj = new MetaTagCMSControlFileUse();
                        $obj->DataObjectClassName = $dataObjectClassName."_Live";
                        $obj->DataObjectFieldName = $dataObjectFieldName;
                        $obj->FileClassName = $fileClassName;
                        $obj->ConnectionType = $connectionType;
                        $obj->DataObjectIsFile = $computedDataObjectIsFile;
                        $obj->FileIsFile =  $computedFileIsFile;
                        $obj->BothAreFiles =  $bothAreFiles;
                        $obj->IsLiveVersion = 1;
                        $obj->write();
                    }
                }
                DB::alteration_message("creating new MetaTagCMSControlFileUse: $dataObjectClassName, $dataObjectFieldName, $fileClassName, $connectionType");
            }
        }
    }

    /**
     *
     * @param File $file
     * @param Boolean #quickBooleanCheck - if true just returns if the file is used YES or NO in a more efficient manner
     * @param Boolean $saveListOfPlaces -
     * @return Int
     */
    public static function file_usage_count($file, $quickBooleanCheck = false, $saveListOfPlaces = false)
    {
        $fileID = $file->ID;
        if (!isset(self::$file_usage_array[$fileID])) {
            self::$file_usage_array[$fileID] = 0;
            //check for self-referencing (folders)
            $sql = "SELECT COUNT(ID) FROM \"File\" WHERE \"ParentID\" = {$fileID};";
            $result = DB::query($sql, false);
            $childCount = $result->value();
            if ($childCount) {
                self::$file_usage_array[$fileID] = $childCount;
                return self::$file_usage_array[$fileID];
            }


            //check for SiteTree_ImageTracking
            $sql = "SELECT COUNT(ID) FROM \"SiteTree_ImageTracking\" WHERE \"FileID\" = {$fileID};";
            $result = DB::query($sql, false);
            $childCount = $result->value();
            if ($childCount) {
                if ($quickBooleanCheck) {
                    return true;
                }
                self::$file_usage_array[$fileID] = $childCount;
                if ($saveListOfPlaces) {
                    self::list_of_places_adder($fileID, "SELECT SiteTreeID as MyID FROM \"SiteTree_ImageTracking\" WHERE \"FileID\" = {$fileID};", "SiteTree");
                }
            }
            $checks = MetaTagCMSControlFileUse::get();
            if ($checks && $checks->count()) {
                foreach ($checks as $check) {
                    for ($i = 0; $i < 2; $i++) {
                        $sql = "";
                        switch ($check->ConnectionType) {
                            case "DB":
                                $fileName = $file->Name;
                                $sql = "
									SELECT COUNT(\"{$check->DataObjectClassName}\".\"ID\")
									FROM \"{$check->DataObjectClassName}\"
									WHERE LOCATE('$fileName', \"{$check->DataObjectClassName}\".\"{$check->DataObjectFieldName}\") > 0
								";
                                if ($saveListOfPlaces) {
                                    $sqlListOfPlaces = "
										SELECT \"{$check->DataObjectClassName}\".\"ID\" AS MyID
										FROM \"{$check->DataObjectClassName}\"
										WHERE LOCATE('$fileName', \"{$check->DataObjectClassName}\".\"{$check->DataObjectFieldName}\") > 0
									";
                                    $objectNameListOfPlaces = $check->DataObjectClassName;
                                }
                                break;

                            case "HAS_ONE":
                                $countSelect = "SELECT COUNT(\"{$check->DataObjectClassName}\".\"ID\")";
                                $listSelect = "SELECT \"{$check->DataObjectClassName}\".\"ID\" AS MyID";
                                $from = "FROM \"{$check->DataObjectClassName}\"";
                                if ($check->FileIsFile) {
                                    $where = "WHERE \"{$check->DataObjectFieldName}ID\" = {$fileID}";
                                    $sql = "
										$countSelect
										$from
										$where;
									";
                                    if ($saveListOfPlaces) {
                                        $sqlListOfPlaces = "
											$listSelect
											$from
											$where;
										";
                                    }
                                } elseif ($check->DataObjectIsFile) {
                                    $where = "WHERE \"{$check->DataObjectFieldName}ID\" > 0 AND \"{$check->DataObjectClassName}\".\"ID\" = {$fileID}";
                                    $sql = "
										$countSelect
										$from
										$where;
									";
                                    if ($saveListOfPlaces) {
                                        $sqlListOfPlaces = "
											$listSelect
											$from
											$where;
										";
                                    }
                                    $objectNameListOfPlaces = $check->DataObjectClassName;
                                }
                                break;
                            case "MANY_MANY":
                            case "BELONGS_MANY_MANY":
                                $countSelect = "SELECT COUNT(\"{$check->DataObjectClassName}_{$check->DataObjectFieldName}\".\"ID\")";
                                $listSelect = "SELECT \"{$check->DataObjectClassName}_{$check->DataObjectFieldName}\".\"{$check->DataObjectClassName}ID\" AS MyID";
                                $from = "FROM \"{$check->DataObjectClassName}_{$check->DataObjectFieldName}\"";
                                if ($check->FileIsFile) {
                                    $where = "WHERE \"{$check->FileClassName}ID\" = $fileID;";
                                    $sql = "
										$countSelect
										$from
										$where;
									";
                                    if ($saveListOfPlaces) {
                                        $sqlListOfPlaces = "
											$listSelect
											$from
											$where
										";
                                        $objectNameListOfPlaces = $check->FileClassName;
                                    }
                                } elseif ($check->DataObjectIsFile) {
                                    $where = "WHERE \"{$check->DataObjectClassName}ID\" = $fileID;";
                                    $sql = "
										$countSelect
										$from
										$where;
									";
                                    if ($saveListOfPlaces) {
                                        $sqlListOfPlaces = "
											$listSelect
											$from
											$where
										";
                                        $objectNameListOfPlaces = $check->FileClassName;
                                    }
                                }
                                break;
                        }
                        $result = DB::query($sql, false);
                        $count = $result->value();
                        if ($count) {
                            if ($quickBooleanCheck) {
                                return true;
                            } else {
                                if ($saveListOfPlaces) {
                                    if (! $check->IsLiveVersion) {
                                        self::list_of_places_adder($fileID, $sqlListOfPlaces, $objectNameListOfPlaces);
                                        $sqlListOfPlaces = "";
                                        $objectNameListOfPlaces = "";
                                    }
                                }
                                self::$file_usage_array[$fileID] += $count;
                            }
                        }
                    }
                }
            }
        }
        return self::$file_usage_array[$fileID];
    }

    /**
     * @param Int $fileID
     * @return DataObjectSet
     */
    public static function retrieve_list_of_places($fileID)
    {
        if (isset(self::$list_of_places_dos[$fileID])) {
            if (is_array(self::$list_of_places_dos[$fileID])) {
                if (count(self::$list_of_places_dos[$fileID])) {
                    $dos = new ArrayList();
                    foreach (self::$list_of_places_dos[$fileID] as $item) {
                        if ($item->hasMethod("Link")) {
                            $item->MyLink = $item->Link();
                        } else {
                            $item->MyLink = null;
                        }
                        if ($item->hasMethod("getTitle")) {
                            $item->MyTitle = $item->getTitle();
                        } elseif (isset($item->Title)) {
                            $item->MyTitle = $item->Title;
                        } else {
                            $item->MyTitle = "";
                        }
                        $arrayData = new ArrayData(
                            array(
                                "ClassName" => $item->ClassName,
                                "ID" => $item->ID,
                                "Title" => $item->MyTitle,
                                "Link" => $item->MyLink
                            )
                        );
                        $dos->push($arrayData);
                    }
                    return $dos;
                }
            }
        }
    }

    /**
     *
     * @param Int $fileID
     * @param String $sqlListOfPlaces
     * @param String $objectNameListOfPlaces
     * @return void
     */
    private static function list_of_places_adder($fileID, $sqlListOfPlaces, $objectNameListOfPlaces)
    {
        $rows = DB::query($sqlListOfPlaces, false);
        if ($rows) {
            $IDarray = array();
            foreach ($rows as $row) {
                $IDarray[] = $row["MyID"];
            }
            $items = $objectNameListOfPlaces::get()
                ->filter(array("ID" => $IDarray));
            if ($items && $items->count()) {
                foreach ($items as $item) {
                    if (!isset(self::$list_of_places_dos[$fileID])) {
                        self::$list_of_places_dos[$fileID] = array();
                    }
                    self::$list_of_places_dos[$fileID][$item->ID.$item->ClassName] = $item;
                }
            }
        }
    }

    private static $file_sub_string = array(
        ".jpg",
        ".png",
        ".jpeg",
        ".gif",
        ".JPG",
        ".PNG",
        ".JPEG",
        ".GIF"
    );

    public static function recycle_folder($folderID = 0, $verbose = true)
    {
        $count = 0;
        set_time_limit(60*10); // 10 minutes
        $recyclefolder = Folder::find_or_make(Config::inst()->get("MetaTagCMSControlFiles", "recycling_bin_name"));
        if ($recyclefolder) {
            $files = File::get()
                ->filter(array("ParentID" => $folderID))
                ->exclude(array("ParentID" => $recyclefolder->ID));
            if ($files && $files->count()) {
                foreach ($files as $file) {
                    if (self::file_usage_count($file, true)) {
                        if ($verbose) {
                            DB::alteration_message($file->Title." is in use. No action taken.", "created");
                        }
                    } else {
                        if (MetaTagCMSControlFileUse_RecyclingRecord::recycle($file, $verbose)) {
                            if ($verbose) {
                                DB::alteration_message($file->Title." recycled", "edited");
                            }
                            $count++;
                        } else {
                            if ($verbose) {
                                DB::alteration_message("Could not recycle file: ".$file->ID.'-'.$file->Title, "deleted");
                            }
                        }
                    }
                }
            } else {
                if ($verbose) {
                    DB::alteration_message("There are no files to recycle", "created");
                }
            }
        } else {
            if ($verbose) {
                DB::alteration_message("Could not create recycling folder", "deleted");
            }
        }
        return $count;
    }


    public static function upgrade_file_names($folderID, $verbose = true)
    {
        set_time_limit(60*10); // 10 minutes
        $whereArray = array();
        $whereArray[] = "\"Title\" = \"Name\"";
        foreach ($this->Config()->get("file_sub_string") as $subString) {
            $whereArray[] = "LOCATE('$subString', \"Title\") > 0";
        }
        $whereString =  "\"ClassName\" <> 'Folder' AND \"ParentID\" = $folderID AND ( ".implode(" OR ", $whereArray)." )";
        $recyclefolder = Folder::find_or_make(Config::inst()->get("MetaTagCMSControlFiles", "recycling_bin_name"));
        if ($recyclefolder) {
            $whereString .= " AND ParentID <> ".$recyclefolder->ID;
        }
        $files = File::get()->where($whereString);
        if ($files && $files->count()) {
            foreach ($files as $file) {
                if ($verbose) {
                    DB::alteration_message("Examining ".$file->Title);
                }
                self::upgrade_file_name($file, $verbose);
            }
        } else {
            if ($verbose) {
                DB::alteration_message("All files have proper names", "created");
            }
        }
    }

    private static function upgrade_file_name(File $file, $verbose = true)
    {
        $fileID = $file->ID;
        if (self::file_usage_count($file, true)) {
            $checks = MetaTagCMSControlFileUse::get()->filter(array("BothAreFiles", 0));
            if ($checks && $checks->count()) {
                foreach ($checks as $check) {
                    if (!$check->IsLiveVersion) {
                        $objName = "";
                        $where = "";
                        $innerJoinTable = "";
                        $innerJoinJoin = "";
                        switch ($check->ConnectionType) {
                            case "HAS_ONE":
                                $innerJoinTable = "";
                                $innerJoinJoin = "";
                                if ($check->FileIsFile) {
                                    $where = "\"{$check->DataObjectFieldName}ID\" = {$fileID}";
                                    $objName = $check->DataObjectClassName;
                                } elseif ($check->DataObjectIsFile) {
                                    $where = "\"{$check->DataObjectFieldName}ID\" > 0 AND \"{$check->DataObjectClassName}\".\"ID\" = {$fileID}";
                                    $objName = $check->DataObjectClassName;
                                    $innerJoinTable = $check->FileClassName;
                                    $innerJoinJoin = "\"{$check->DataObjectClassName}\".\"ID\" = \"".$check->FileClassName."\".\"ID\" ";
                                }
                                break;
                            case "BELONGS_MANY_MANY":
                            case "MANY_MANY":
                                if ($check->ConnectionType == "BELONGS_MANY_MANY") {
                                    $innerJoinTable = "\"{$check->FileClassName}_{$check->DataObjectFieldName}\"";
                                } else {
                                    $innerJoinTable = "\"{$check->DataObjectClassName}_{$check->DataObjectFieldName}\"";
                                }
                                if ($check->FileIsFile) {
                                    $where = "$innerJoinTable.\"{$check->FileClassName}ID\" = {$fileID}";
                                    $objName = $check->DataObjectClassName;
                                    $innerJoinJoin = "\"{$check->DataObjectClassName}\".\"ID\" = $innerJoinTable.\"{$check->DataObjectClassName}ID\"";
                                } elseif ($check->DataObjectIsFile) {
                                    $where = "$innerJoinTable.\"{$check->DataObjectClassName}ID\" = {$fileID}";
                                    $objName = $check->FileClassName;
                                    $innerJoinJoin = "\"{$check->FileClassName}\".\"ID\" = $innerJoinTable.\"{$check->FileClassName}ID\"";
                                }
                                break;
                        }
                        $join = "";
                        if ($objName) {
                            $sort = null;
                            $limit = 1;
                            if (Config::inst()->get("MetaTagCMSControlFileUse", "debug")) {
                                echo "<hr />";
                                echo "TYPE: ".$check->ConnectionType."<br />";
                                echo "CLASS: ".$objName."<br />";
                                echo "WHERE: ".$where."<br />";
                                echo "SORT: ".$sort."<br />";
                                echo "JOIN: ".$join."<br />";
                                echo "LIMIT: ".$limit."<br />";
                                echo "<hr />";
                            }
                            $objects = $objName::get()->where($where)->sort($sort)->innerJoin($innerJoinTable, $innerJoinJoin)->limit($limit);
                            if ($objects && $objects->count()) {
                                $obj = $objects->First();
                                $oldTitle = $file->Title;
                                $newTitle =  $obj->getTitle();
                                if ((substr($newTitle, 0, 1) != "#") || (intval($newTitle) == $newTitle)) {
                                    $file->Title = $newTitle;
                                    $file->write();
                                    if ($verbose) {
                                        DB::alteration_message("Updating ".$file->Name." title from ".$oldTitle." to ".$newTitle, "created");
                                    }
                                } else {
                                    if ($verbose) {
                                        DB::alteration_message("There is no real title for ".$obj->ClassName.": ".$newTitle);
                                    }
                                }
                            } else {
                                if ($verbose) {
                                    echo ".";
                                }
                            }
                        } else {
                            if ($verbose) {
                                echo ";";
                            }
                        }
                    } else {
                        if ($verbose) {
                            echo "-";
                        }
                    }
                }
            } else {
                if ($verbose) {
                    DB::alteration_message("There are no checks", "deleted");
                }
            }
        } else {
            if ($verbose) {
                DB::alteration_message("File <i>".$file->Title."</i> is not being used");
            }
        }
        return self::$file_usage_array[$fileID];
    }
}


class MetaTagCMSControlFileUse_RecyclingRecord extends DataObject
{
    private static $db = array(
        "FileID" => "Int",
        "FromFolderID" => "Int"
    );

    public static function recycle(File $file, $verbose = true)
    {
        $recylcingFolder = Folder::find_or_make(Config::inst()->get("MetaTagCMSControlFiles", "recycling_bin_name"));
        if ($recylcingFolder) {
            if ($file) {
                if ($file->exists()) {
                    if (file_exists($file->getFullPath())) {
                        $valid = $file->validate();
                        if ($valid->valid()) {
                            $record = new MetaTagCMSControlFileUse_RecyclingRecord();
                            $record->FileID = $file->ID;
                            $record->FromFolderID = $file->ParentID;
                            $record->write();
                            //doing it.....
                            $file->ParentID = $recylcingFolder->ID;
                            $file->write();
                            //IMPORTANT!
                            return true;
                        }
                    }
                    $record = new MetaTagCMSControlFileUse_RecyclingRecord();
                    $record->FileID = $file->ID;
                    $record->FromFolderID = $file->ParentID;
                    $record->write();
                    DB::query("UPDATE \"File\" SET \"ParentID\" = ".$recylcingFolder->ID." WHERE \"File\".\"ID\" = ".$file->ID);
                    return true;
                }
            }
        }
        return false;
    }
}
