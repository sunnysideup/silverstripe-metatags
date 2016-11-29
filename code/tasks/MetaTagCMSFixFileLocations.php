<?php

class MetaTagCMSFixImageLocations extends BuildTask
{
    public static function my_link()
    {
        return "/dev/tasks/MetaTagCMSFixImageLocations/";
    }

    protected $title = "Fix File Locations";

    protected $description = "This method is useful when most of your files end up in the 'Upload' folder.  This task will put all the HAS_ONE and HAS_MANY files into the following folders {CLASSNAME}_{FIELDNAME}.  You can run this task safely, as it will only execute with a special GET parameter (i.e. it defaults to run in test-mode only).";

    /**
     * Names of folders to ignore
     * @var Array
     */
    private static $folders_to_ignore = array();

    /**
     * automatically includes any child folders
     * @var array
     */
    private $listOfIgnoreFoldersArray = array();

    /**
     * is this task running 'for real' or as test only?
     * @var Boolean
     */
    private $forReal = false;

    /**
     * do one attachment type for real?
     * @var Boolean
     */
    private $doOne = false;

    /**
     * clean up folder?
     * This deletes the empty folders
     * @var Boolean
     */
    private $cleanupFolder = 0;

    /**
     * only show the summary OR the full details
     * summaries only is not available for non-test tasks
     * @var Boolean
     */
    private $summaryOnly = false;

    public function run($request)
    {
        if (isset($_GET["forreal"])) {
            $this->forReal = $_GET["forreal"];
        }
        if (isset($_GET["summaryonly"])) {
            $this->summaryOnly = $_GET["summaryonly"];
            DB::alteration_message("Prefer <a href=\"".$this->linkWithGetParameter("all", 1)."\">all details</a>?<hr />", "repaired");
        }
        if (isset($_GET["doone"])) {
            $this->forReal = 1;
            $this->doOne = urldecode($_GET["doone"]);
        }
        if (isset($_GET["cleanupfolder"])) {
            $this->cleanupFolder = intval($_GET["cleanupfolder"]);
        }

        //work out the folders to ignore...
        foreach ($this->Config()->get("folders_to_ignore") as $folderToIgnoreName) {
            $folderToIgnore = Folder::find_or_make($folderToIgnoreName);
            $this->addListOfIgnoreFoldersArray($folderToIgnore);
        }
        if (count($this->listOfIgnoreFoldersArray)) {
            DB::alteration_message("Files in the following Folders will be ignored: <br />&nbsp; &nbsp; &nbsp; - ".implode("<br />&nbsp; &nbsp; &nbsp; - ", $this->listOfIgnoreFoldersArray)."<hr />", "repaired");
        }
        if (!$this->cleanupFolder) {
            if (!$this->forReal) {
                DB::alteration_message("Apply <a href=\"".$this->linkWithGetParameter("forreal", 1)."\">all suggested changes</a>? CAREFUL!<hr />", "deleted");
            }
            if (!$this->summaryOnly) {
                DB::alteration_message("Prefer a <a href=\"".$this->linkWithGetParameter("summaryonly", 1)."\">summary only</a>?<hr />", "repaired");
            }
            $checks = MetaTagCMSControlFileUse::get()-where("\"ConnectionType\" IN ('HAS_ONE') AND \"IsLiveVersion\" = 0 AND \"DataObjectClassName\" <> 'File'");
            if ($checks && $checks->count()) {
                foreach ($checks as $check) {
                    $folderName = $check->DataObjectClassName."_".$check->DataObjectFieldName;
                    if (!$this->doOne || $this->doOne == $folderName) {
                        $objectName = $check->DataObjectClassName;
                        $fieldName = $check->DataObjectFieldName."ID";
                        $fileClassName = $check->FileClassName;
                        $folder = null;
                        DB::alteration_message(
                            "<hr /><h3>All files attached to $objectName . $fieldName <a href=\"".$this->linkWithGetParameter("doone", $folderName)."\">can be moved to</a> <span style=\"color: green;\">$folderName</span></h3>"
                        );
                        if ($this->summaryOnly) {
                            //do nothing
                        } else {
                            $objects = null;
                            if ($check->FileIsFile) {
                                $objects = $objectName::get()->where("\"".$fieldName."\" > 0");
                            } elseif ($check->DataObjectIsFile) {
                                $objects = $objectName::get()->where("\"".$fieldName."\" > 0");
                            }
                            if ($objects && $objects->count()) {
                                foreach ($objects as $object) {
                                    if ($object instanceof File) {
                                        $file = $object;//do nothing
                                    } else {
                                        $file = File::get()->byID($object->$fieldName);
                                    }
                                    if ($file) {
                                        if ($file instanceof Folder) {
                                            //do nothing
                                        } else {
                                            if (!$folder) {
                                                $folder = Folder::find_or_make($folderName);
                                            }
                                            if ($file->ParentID == $folder->ID) {
                                                DB::alteration_message(
                                                    "OK ... ". $file->FileName,
                                                    "created"
                                                );
                                            } else {
                                                if (isset($this->listOfIgnoreFoldersArray[$file->ParentID])) {
                                                    DB::alteration_message(
                                                        "NOT MOVING (folder to be ignored): <br />/".$file->FileName." to <br />/assets/".$folderName."/".$file->Name."",
                                                        "repaired"
                                                    );
                                                } else {
                                                    DB::alteration_message(
                                                        "MOVING: <br />/".$file->FileName." to <br />/assets/".$folderName."/".$file->Name."",
                                                        "created"
                                                    );
                                                    if ($this->forReal) {
                                                        if ($file->exists()) {
                                                            if (file_exists($file->getFullPath())) {
                                                                $file->ParentID = $folder->ID;
                                                                $file->write();
                                                            } else {
                                                                DB::alteration_message(
                                                                    "ERROR: phyiscal file could not be found: ".$file->getFullPath()." ",
                                                                    "deleted"
                                                                );
                                                            }
                                                        } else {
                                                            DB::alteration_message(
                                                                "ERROR: file not found in database: /".$file->FileName." ",
                                                                "deleted"
                                                            );
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        DB::alteration_message(
                                            "Could not find file referenced by ".$object->getTitle()." (".$object->class.", ".$object->ID.")",
                                            "deleted"
                                        );
                                    }
                                }
                            } else {
                                DB::alteration_message("No objects in $objectName $fieldName.", "deleted");
                            }
                        }
                    }
                }
            } else {
                DB::alteration_message("Could not find any checks, please run /dev/build/", "deleted");
            }
        } else {
            DB::alteration_message("We are now showing folders only; <a href=\"".$this->linkWithGetParameter("all", 1)."\">view all</a><hr />", "restored");
        }
        DB::alteration_message("---------------------------------------");
        DB::alteration_message("---------------------------------------");
        DB::alteration_message("CLEANING FOLDERS");
        DB::alteration_message("---------------------------------------");
        DB::alteration_message("---------------------------------------");
        $folders = Folder::get();
        $hasEmptyFolders = false;
        if ($folders && $folders->count()) {
            foreach ($folders as $folder) {
                if (!MetaTagCMSControlFileUse::file_usage_count($folder, true)) {
                    $hasEmptyFolders = true;
                    if (file_exists($folder->getFullPath())) {
                        if (($this->cleanupFolder != $folder->ID) && ($this->cleanupFolder != -1)) {
                            DB::alteration_message("found an empty folder: <strong>".$folder->FileName."</strong>; <a href=\"".$this->linkWithGetParameter("cleanupfolder", $folder->ID)."\">delete now</a>?", "restored");
                        }
                        if (($this->cleanupFolder == $folder->ID) || $this->cleanupFolder == -1) {
                            DB::alteration_message("
								Deleting empty folder: <strong>".$folder->FileName."</strong>",
                                "deleted"
                            );
                            $folder->delete();
                        }
                    } else {
                        DB::alteration_message("Could not find this phyiscal folder - it is empty can be deleted: ".$folder->getFullPath(), "deleted");
                    }
                }
            }
        } else {
            DB::alteration_message("Could not find any folders. There might be something wrong!", "deleted");
        }
        if (!$hasEmptyFolders) {
            DB::alteration_message("There are no empty folders!", "created");
        } else {
            DB::alteration_message("Delete <a href=\"".$this->linkWithGetParameter("cleanupfolder", -1)."\">all empty folders</a>?", "deleted");
        }
    }

    private function addListOfIgnoreFoldersArray(Folder $folderToIgnore)
    {
        $this->listOfIgnoreFoldersArray[$folderToIgnore->ID] = $folderToIgnore->FileName;
        $childFolders = Folder::get()->filter(array("ParentID" => $folderToIgnore->ID));
        if ($childFolders && $childFolders->count()) {
            foreach ($childFolders as $childFolder) {
                $this->addListOfIgnoreFoldersArray($childFolder);
            }
        }
    }

    private function linkWithGetParameter($var, $value)
    {
        return "/dev/tasks/MetaTagCMSFixImageLocations?$var=".urlencode($value);
    }
}
