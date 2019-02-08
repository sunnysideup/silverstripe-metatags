<?php

namespace Sunnysideup\MetaTags\Extension;

use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use Sunnysideup\MetaTags\Extension\MetaTagsContentControllerEXT;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\SSViewer;
use Sunnysideup\MetaTags\Extension\MetaTagsSTE;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DB;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Forms\OptionsetField;

/**
*
* @Author Nicolaas Francken
* adding meta tag functionality to the SiteTree Model Classes.
*
*
*
**/

class MetaTagsSTE extends SiteTreeExtension
{

    /**
     * standard SS method
     * @var Array
     **/
    private static $db = array(
        'AutomateMetatags' => 'Enum("Inherit,Custom,Automated", "Inherit")'
    );
    /**
     * standard SS method
     * @var Array
     **/
    private static $indexes = array(
        'AutomateMetatags' => true,
        'Sort' => true
    );

    /**
     * standard SS method
     * @var Array
     **/
    private static $has_one = array(
        'ShareOnFacebookImage' => Image::class
    );

    /**
     * @var string
     * set to empty string to stop it being copied
     * by default to the theme
     **/
    private static $default_editor_file = "metatags/client/css/editor.css";


    /**
     * @var string
     * set to empty string to stop it being copied
     * by default to the theme
     **/
    private static $default_reset_file = "metatags/client/css/reset.css";

    /**
     * because we use this function you can NOT
     * use any statics in the file!!!
     * @return Array | null
     */
    // public static function get_extra_config($class, $extension, $args)
    // {
    //     if (Config::inst()->get(MetaTagsContentControllerEXT::class, "use_separate_metatitle")) {
    //         $array = array(
    //             'db' => array("MetaTitle" => "Varchar(255)") + self::$db
    //         );
    //     } else {
    //         $array = array(
    //             'db' => self::$db
    //         );
    //     }
    //
    //     return ((array) parent::get_extra_config($class, $extension, $args)) + $array;
    // }

    /**
     * standard SS method
     * @var Array
     **/
    private static $defaults = array(
        'AutomateMetatags' => 'Inherit'
    );

    /**
     * standard SS method
     * @var Array
     **/
    public function updateSettingsFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            "Root.Facebook",
            new HeaderField(
                _t("MetaTagsSTE.FB_HOW_THIS_PAGE_IS_SHARED", "How is this page shared on Facebook?"),
                ""
            )
        );
        $fields->addFieldToTab("Root.Facebook", $fieldTitle = ReadonlyField::create("fb_title", _t("MetaTagsSTE.FB_TITLE", "Title"), $this->owner->Title));
        $fields->addFieldToTab("Root.Facebook", $fieldType = ReadonlyField::create("fb_type", _t("MetaTagsSTE.FB_TITLE", "Type"), "website"));
        $fields->addFieldToTab("Root.Facebook", $fieldSiteName = ReadonlyField::create("fb_type", _t("MetaTagsSTE.FB_SITE_NAME", "Site Name"), SiteConfig::current_site_config()->Title));
        $fields->addFieldToTab("Root.Facebook", $fieldDescription = ReadonlyField::create("fb_description", _t("MetaTagsSTE.FB_DESCRIPTION", "Description (from MetaDescription)"), $this->owner->MetaDescription));
        $fields->addFieldToTab(
            "Root.Facebook",
            $shareOnFacebookImageField = UploadField::create(
                "ShareOnFacebookImage",
                _t("MetaTagsSTE.FB_IMAGE", Image::class)
            )
        );
        $shareOnFacebookImageField->setFolderName("OpenGraphShareImages");
        $shareOnFacebookImageField->setDescription("Use images that are at least 1200 x 630 pixels for the best display on high resolution devices. At the minimum, you should use images that are 600 x 315 pixels.");
        $fields->addFieldToTab(
            "Root.Facebook",
            $shareOnFacebookImageField = LiteralField::create(
                "fb_try_it_out",
                '<h3><a href="https://www.facebook.com/sharer/sharer.php?u='.urlencode($this->owner->AbsoluteLink()).'">'._t("MetaTagsSTE.FB_TRY_IT_OUT", "Share on Facebook Now") .'</a></h3>',
                $this->owner->ShareOnFacebookImage()
            )
        );
        $fields->addFieldToTab(
            "Root.Facebook",
            $debugFacebookSharing = LiteralField::create(
                "fb_debug_link",
                '<h3><a href="https://developers.facebook.com/tools/debug/sharing/?q='.urlencode($this->owner->AbsoluteLink()).'" target="_blank">'._t("MetaTagsSTE.FB_DEBUGGER", "Facebook Sharing Debugger") .'</a></h3>'
            )
        );
        //right titles
        $fieldTitle->setDescription(
            _t(
                "MetaTagsSTE.FB_TITLE_RIGHT",
                "Uses the Page Title"
            )
        );
        $fieldType->setDescription(
            _t(
                "MetaTagsSTE.FB_TYPE_RIGHT",
                "Can not be changed"
            )
        );
        $fieldSiteName->setDescription(
            _t(
                "MetaTagsSTE.FB_SITE_NAME_RIGHT",
                "Can be set in the site settings"
            )
        );
        $fieldDescription->setDescription(
            _t(
                "MetaTagsSTE.FB_DESCRIPTION",
                "Description is set in the Metadata section of each page."
            )
        );
        $shareOnFacebookImageField->setDescription(
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
    public function updateCMSFields(FieldList $fields)
    {
        if($fields->fieldByName('Root.Main')) {
            //separate MetaTitle?
            if (Config::inst()->get(MetaTagsContentControllerEXT::class, "use_separate_metatitle")) {
                $fields->addFieldToTab(
                    'Root.Main.Metadata',
                    $allowField0 = TextField::create(
                        'MetaTitle',
                        _t('SiteTree.METATITLE', 'Meta Title')
                    ),
                    "MetaDescription"
                );
                $allowField0->setDescription(
                    _t("SiteTree.METATITLE_EXPLANATION", "Leave this empty to use the page title")
                );
            }

            //choose automation for page
            $fields->addFieldToTab(
                'Root.Main.Metadata',
                $allowField1 = OptionsetField::create(
                    'AutomateMetatags',
                    _t('MetaManager.UPDATEMETA', 'Automation for this page ...'),
                    $this->AutomateMetatagsOptions()
                )->setDescription(
                    _t('MetatagSTE.BY_DEFAULT', '<strong><a href="/admin/settings/">Default Settings</a></strong>:').
                    $this->defaultSettingDescription()
                ),
                'MetaDescription'
            );

            $automatedFields =  $this->updatedFieldsArray();
            $updatedFieldString = "";
            if (count($automatedFields)) {
                $updatedFieldString = ""
                    ._t("MetaManager.UPDATED_EXTERNALLY", "Based on your current settings, the following fields will be automatically updated:")
                    .": <em>"
                    .implode("</em>, <em>", $automatedFields)
                    ."</em>.";
                foreach ($automatedFields as $fieldName => $fieldTitle) {
                    $oldField = $fields->dataFieldByName($fieldName);
                    if ($oldField) {
                        $newField = $oldField->performReadonlyTransformation();
                        //$newField->setTitle($newField->Title());
                        $newField->setDescription(_t("MetaTags.AUTOMATICALLY_UPDATED", "Automatically updated when you save this page."));
                        $fields->replaceField($fieldName, $newField);
                    }
                }
            }
            $fields->removeByName('ExtraMeta');
        }
        if ($this->owner->URLSegment == Config::inst()->get('RootURLController', 'default_homepage_link')) {
            $fields->dataFieldByName('URLSegment')
                ->setDescription("
                    Careful! changing the URL from 'home'
                    to anything else means that this page will no longer be your home page.
                ");
        }
        return $fields;
    }

    /**
     * Update Metadata fields function
     */
    public function onBeforeWrite()
    {
        $fields = $this->updatedFieldsArray();
        if (count($fields)) {
            $siteConfig = SiteConfig::current_site_config();
            // if UpdateMeta checkbox is checked, update metadata based on content and title
            // we only update this from the CMS to limit slow-downs in programatic updates
            if (isset($fields['MenuTitle'])) {
                // Empty MenuTitle
                $this->owner->MenuTitle = '';
                // Check for Content, to prevent errors
                if ($this->owner->Title) {
                    $this->owner->MenuTitle = $this->cleanInput($this->owner->Title, 0);
                }
            }
            if (isset($fields['MetaDescription'])) {
                $length = Config::inst()->get(MetaTagsContentControllerEXT::class, "meta_desc_length");
                // Empty MetaDescription
                // Check for Content, to prevent errors
                if ($length > 0) {
                    if ($this->owner->Content) {
                        //added a few hacks here
                        $contentField = DBField::create_field("Text", strip_tags($this->owner->Content), "MetaDescription");
                        $summary = $contentField->Summary($length);
                        $summary = str_replace("<br>", " ", $summary);
                        $summary = str_replace("<br />", " ", $summary);
                        $summary = str_replace(".", ". ", $summary);
                        $this->owner->MetaDescription = $summary;
                    } else {
                        $this->owner->MetaDescription = '';
                    }
                }
            }
        }
    }

    /**
     * what fields are updated automatically for this page ...
     * @return Array
     */
    private function updatedFieldsArray()
    {
        $fields = [];
        if ($this->owner->AutomateMetatags == 'Custom') {
            return $fields;
        }
        $config = SiteConfig::current_site_config();
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_automated_menu_title")) {
            // do nothing
        } else {
            if ($config->UpdateMenuTitle || $this->owner->AutomateMetatags == 'Automated') {
                $fields['MenuTitle'] = _t('SiteTree.MENUTITLE', 'Navigation Label');
            }
        }
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_automated_meta_description")) {
            //do nothing
        } else {
            if ($config->UpdateMetaDescription || $this->owner->AutomateMetatags == 'Automated') {
                $fields['MetaDescription'] = _t('SiteTree.METADESCRIPTION', 'Meta Description');
            }
        }

        return $fields;
    }

    public function populateDefaults()
    {
        $this->owner->AutomateMetatags = 'Inherit';
    }

    private function cleanInput($string, $numberOfWords = 0)
    {
        $newString = str_replace("&nbsp;", "", $string);
        $newString = str_replace("&amp;", " and ", $newString);
        $newString = str_replace("&ndash;", " - ", $newString);
        $newString = strip_tags(str_replace('<', ' <', $newString));
        if ($numberOfWords) {
            $textFieldObject = DBField::create_field("Text", $newString);
            if ($textFieldObject) {
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
    public function requireDefaultRecords()
    {
        $folder = Config::inst()->get(SSViewer::class, 'theme');
        if ($folder) {
            if ($file = Config::inst()->get(MetaTagsSTE::class, "default_editor_file")) {
                $baseFile = Director::baseFolder(). $file;
                $destinationFile = Director::baseFolder()."/themes/".$folder."/css/editor.css";
                if (!file_exists($destinationFile) && file_exists($baseFile)) {
                    copy($baseFile, $destinationFile);
                }
            }
            if ($file = Config::inst()->get(MetaTagsSTE::class, "default_reset_file")) {
                $baseFile = Director::baseFolder(). $file;
                $destinationFile = Director::baseFolder()."/themes/".$folder."/css/reset.css";
                if (!file_exists($destinationFile) && file_exists($baseFile)) {
                    copy($baseFile, $destinationFile);
                }
            }
        }
        DB::query('
            UPDATE "SiteTree" SET "AutomateMetatags" = \'Inherit\'
            WHERE "AutomateMetatags" NOT IN (\''.implode(array_keys($this->AutomateMetatagsOptions())).'\')
        ');
        DB::query('
            UPDATE "SiteTree_Live" SET "AutomateMetatags" = \'Inherit\'
            WHERE "AutomateMetatags" NOT IN (\''.implode(array_keys($this->AutomateMetatagsOptions())).'\')
        ');
    }

    private function AutomateMetatagsOptions()
    {
        return array(
            'Inherit' => _t('MetaTagsSTE.INHERIT', 'Default Setting'),
            'Custom' => _t('MetaTagsSTE.CUSTOM', 'Manually'),
            'Automated' => _t('MetaTagsSTE.AUTOMATE', 'Automated')
        );
    }


    private function defaultSettingDescription()
    {
        $v = [];
        $siteConfig = SiteConfig::current_site_config();
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_automated_menu_title")) {
            //do nothing
        } else {
            if ($siteConfig->UpdateMenuTitle) {
                $v[] = _t('MetaTagsSTE.UPDATE_MENU_TITLE_ON', 'The Navigation Labels (Menu Titles) are automatically updated');
            } else {
                $v[] = _t('MetaTagsSTE.UPDATE_MENU_TITLE_OFF', 'The Navigation Labels (Menu Titles) can be customised for individual pages');
            }
        }
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_automated_meta_description")) {
            //do nothing
        } else {
            if ($siteConfig->UpdateMetaDescription) {
                $v[] = _t('MetaTagsSTE.UPDATE_META_DESC_ON', 'The Meta Descriptions are automatically updated');
            } else {
                $v[] = _t('MetaTagsSTE.UPDATE_META_DESC_OFF', 'The Meta Descriptions can be customised for individual pages');
            }
        }
        return '<br />- '.implode('<br />- ', $v);
    }
}
