<?php

namespace Sunnysideup\MetaTags\Extension;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\SSViewer;

/**
 * @Author Nicolaas Francken
 * adding meta tag functionality to the SiteTree Model Classes.
 */
class MetaTagsSTE extends SiteTreeExtension
{
    /**
     * standard SS method.
     *
     * @var array
     */
    private static $db = [
        'MetaTitle' => 'Varchar(100)',
        'AutomateMetatags' => 'Enum("Inherit,Custom,Automated", "Inherit")',
        'ExcludeFromSearchEngines' => 'Boolean',
    ];

    /**
     * standard SS method.
     *
     * @var array
     */
    private static $indexes = [
        'AutomateMetatags' => true,
        'ExcludeFromSearchEngines' => true,
        'Sort' => true,
    ];

    /**
     * standard SS method.
     *
     * @var array
     */
    private static $has_one = [
        'ShareOnFacebookImage' => Image::class,
    ];

    private static $owns = [
        'ShareOnFacebookImage',
    ];

    /**
     * @var string
     *             set to empty string to stop it being copied
     *             by default to the theme
     */
    private static $default_editor_file = 'metatags/client/css/editor.css';

    /**
     * @var string
     *             set to empty string to stop it being copied
     *             by default to the theme
     */
    private static $default_reset_file = 'metatags/client/css/reset.css';

    /**
     * standard SS method.
     *
     * @var array
     */
    private static $defaults = [
        'AutomateMetatags' => 'Inherit',
    ];

    /**
     * standard SS method.
     */
    public function updateSettingsFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Facebook',
            new HeaderField(
                _t('MetaTagsSTE.FB_HOW_THIS_PAGE_IS_SHARED', 'How is this page shared on Facebook?'),
                ''
            )
        );
        $fields->addFieldToTab('Root.Facebook', $fieldTitle = ReadonlyField::create('fb_title', _t('MetaTagsSTE.FB_TITLE', 'Title'), $this->owner->Title));
        $fields->addFieldToTab('Root.Facebook', $fieldType = ReadonlyField::create('fb_type', _t('MetaTagsSTE.FB_TITLE', 'Type'), 'website'));
        $fields->addFieldToTab('Root.Facebook', $fieldSiteName = ReadonlyField::create('fb_type', _t('MetaTagsSTE.FB_SITE_NAME', 'Site Name'), SiteConfig::current_site_config()->Title));
        $fields->addFieldToTab('Root.Facebook', $fieldDescription = ReadonlyField::create('fb_description', _t('MetaTagsSTE.FB_DESCRIPTION', 'Description (from MetaDescription)'), $this->owner->MetaDescription));
        $fields->addFieldToTab(
            'Root.Facebook',
            $shareOnFacebookImageField = UploadField::create(
                'ShareOnFacebookImage',
                _t('MetaTagsSTE.FB_IMAGE', 'Image')
            )
        );
        $shareOnFacebookImageField->setFolderName('OpenGraphShareImages');
        $shareOnFacebookImageField->setDescription('Use images that are at least 1200 x 630 pixels for the best display on high resolution devices. At the minimum, you should use images that are 600 x 315 pixels.');
        $fields->addFieldToTab(
            'Root.Facebook',
            $shareOnFacebookImageField = LiteralField::create(
                'fb_try_it_out',
                '<h3><a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($this->owner->AbsoluteLink()) . '">' . _t('MetaTagsSTE.FB_TRY_IT_OUT', 'Share on Facebook Now') . '</a></h3>',
                $this->owner->ShareOnFacebookImage()
            )
        );
        $fields->addFieldToTab(
            'Root.Facebook',
            $debugFacebookSharing = LiteralField::create(
                'fb_debug_link',
                '<h3><a href="https://developers.facebook.com/tools/debug/sharing/?q=' . urlencode($this->owner->AbsoluteLink()) . '" target="_blank">' . _t('MetaTagsSTE.FB_DEBUGGER', 'Facebook Sharing Debugger') . '</a></h3>'
            )
        );
        //right titles
        $fieldTitle->setDescription(
            _t(
                'MetaTagsSTE.FB_TITLE_RIGHT',
                'Uses the Page Title'
            )
        );
        $fieldType->setDescription(
            _t(
                'MetaTagsSTE.FB_TYPE_RIGHT',
                'Can not be changed'
            )
        );
        $fieldSiteName->setDescription(
            _t(
                'MetaTagsSTE.FB_SITE_NAME_RIGHT',
                'Can be set in the site settings'
            )
        );
        $fieldDescription->setDescription(
            _t(
                'MetaTagsSTE.FB_DESCRIPTION',
                'Description is set in the Metadata section of each page.'
            )
        );
        $shareOnFacebookImageField->setDescription(
            _t(
                'MetaTagsSTE.FB_HOW_TO_CHOOSE_IMAGE',
                'If no image is set then the Facebook user can choose an image from the page - with options retrieved by Facebook.'
            )
        );

        $fields->addFieldToTab(
            'Root.Settings',
            CheckboxField::create(
                'ExcludeFromSearchEngines',
                'Hide from Google et al.'
            ),
            'ShowInSearch'
        );
    }

    /**
     * standard SS method.
     */
    public function updateCMSFields(FieldList $fields)
    {
        if ($fields->fieldByName('Root.Main.Metadata')) {
            //separate MetaTitle?
            if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'use_separate_metatitle')) {
                $fields->addFieldToTab(
                    'Root.Main.Metadata',
                    $allowField0 = TextField::create(
                        'MetaTitle',
                        _t('SiteTree.METATITLE', 'Meta Title')
                    ),
                    'MetaDescription'
                );
                $allowField0->setDescription(
                    _t('SiteTree.METATITLE_EXPLANATION', 'Leave this empty to use the page title')
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
                    _t('MetatagSTE.BY_DEFAULT', '<strong><a href="/admin/settings/">Default Settings</a></strong>:') .
                    $this->defaultSettingDescription()
                ),
                'MetaDescription'
            );

            $automatedFields = $this->updatedFieldsArray();
            if (count($automatedFields)) {
                foreach (array_keys($automatedFields) as $fieldName) {
                    $oldField = $fields->dataFieldByName($fieldName);
                    if ($oldField) {
                        $newField = $oldField->performReadonlyTransformation();
                        //$newField->setTitle($newField->Title());
                        $newField->setDescription(_t('MetaTags.AUTOMATICALLY_UPDATED', 'Automatically updated when you save this page.'));
                        $fields->replaceField($fieldName, $newField);
                    }
                }
            }
            $fields->removeByName('ExtraMeta');
        }
        if ($this->owner->URLSegment === Config::inst()->get(RootURLController::class, 'default_homepage_link')) {
            $fields->dataFieldByName('URLSegment')
                ->setDescription("
                    Careful! changing the URL from 'home'
                    to anything else means that this page will no longer be your home page.
                ")
            ;
        }
    }

    /**
     * Update Metadata fields function.
     */
    public function onBeforeWrite()
    {
        $fields = $this->updatedFieldsArray();
        if (count($fields)) {
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
                $length = Config::inst()->get(MetaTagsContentControllerEXT::class, 'meta_desc_length');
                // Empty MetaDescription
                // Check for Content, to prevent errors
                if ($length > 0) {
                    if ($this->owner->Content) {
                        //added a few hacks here
                        $contentField = DBField::create_field('Text', strip_tags($this->owner->Content), 'MetaDescription');
                        $summary = $contentField->Summary($length);
                        $summary = str_replace('<br>', ' ', $summary);
                        $summary = str_replace('<br />', ' ', $summary);
                        $summary = str_replace('.', '. ', $summary);
                        $this->owner->MetaDescription = $summary;
                    } else {
                        $this->owner->MetaDescription = '';
                    }
                }
            }
        }
    }

    public function populateDefaults()
    {
        $this->owner->AutomateMetatags = 'Inherit';
    }

    /**
     * add default css files.
     */
    public function requireDefaultRecords()
    {
        $folder = Config::inst()->get(SSViewer::class, 'theme');
        if ($folder) {
            if ($file = Config::inst()->get(MetaTagsSTE::class, 'default_editor_file')) {
                $baseFile = Director::baseFolder() . $file;
                $destinationFile = Director::baseFolder() . '/themes/' . $folder . '/css/editor.css';
                if (! file_exists($destinationFile) && file_exists($baseFile)) {
                    copy($baseFile, $destinationFile);
                }
            }
            if ($file = Config::inst()->get(MetaTagsSTE::class, 'default_reset_file')) {
                $baseFile = Director::baseFolder() . $file;
                $destinationFile = Director::baseFolder() . '/themes/' . $folder . '/css/reset.css';
                if (! file_exists($destinationFile) && file_exists($baseFile)) {
                    copy($baseFile, $destinationFile);
                }
            }
        }
        DB::query('
            UPDATE "SiteTree" SET "AutomateMetatags" = \'Inherit\'
            WHERE "AutomateMetatags" NOT IN (\'' . implode(array_keys($this->AutomateMetatagsOptions())) . '\')
        ');
        DB::query('
            UPDATE "SiteTree_Live" SET "AutomateMetatags" = \'Inherit\'
            WHERE "AutomateMetatags" NOT IN (\'' . implode(array_keys($this->AutomateMetatagsOptions())) . '\')
        ');
    }

    /**
     * what fields are updated automatically for this page ...
     *
     * @return array
     */
    private function updatedFieldsArray()
    {
        $fields = [];
        if ('Custom' === $this->owner->AutomateMetatags) {
            return $fields;
        }
        $config = SiteConfig::current_site_config();
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_automated_menu_title')) {
            // do nothing
        } else {
            if ($config->UpdateMenuTitle || 'Automated' === $this->owner->AutomateMetatags) {
                $fields['MenuTitle'] = _t('SiteTree.MENUTITLE', 'Navigation Label');
            }
        }
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_automated_meta_description')) {
            //do nothing
        } else {
            if ($config->UpdateMetaDescription || 'Automated' === $this->owner->AutomateMetatags) {
                $fields['MetaDescription'] = _t('SiteTree.METADESCRIPTION', 'Meta Description');
            }
        }

        return $fields;
    }

    private function cleanInput($string, $numberOfWords = 0)
    {
        $newString = str_replace('&nbsp;', '', $string);
        $newString = str_replace('&amp;', ' and ', $newString);
        $newString = str_replace('&ndash;', ' - ', $newString);
        $newString = strip_tags(str_replace('<', ' <', $newString));
        if ($numberOfWords) {
            $textFieldObject = DBField::create_field('Text', $newString);
            if ($textFieldObject) {
                $newString = strip_tags($textFieldObject->LimitWordCountXML($numberOfWords));
            }
        }
        $newString = html_entity_decode($newString, ENT_QUOTES);

        return html_entity_decode($newString, ENT_QUOTES);
    }

    private function AutomateMetatagsOptions()
    {
        return [
            'Inherit' => _t('MetaTagsSTE.INHERIT', 'Default Setting'),
            'Custom' => _t('MetaTagsSTE.CUSTOM', 'Manually'),
            'Automated' => _t('MetaTagsSTE.AUTOMATE', 'Automated'),
        ];
    }

    private function defaultSettingDescription()
    {
        $v = [];
        $siteConfig = SiteConfig::current_site_config();
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_automated_menu_title')) {
            //do nothing
        } else {
            if ($siteConfig->UpdateMenuTitle) {
                $v[] = _t('MetaTagsSTE.UPDATE_MENU_TITLE_ON', 'The Navigation Labels (Menu Titles) are automatically updated');
            } else {
                $v[] = _t('MetaTagsSTE.UPDATE_MENU_TITLE_OFF', 'The Navigation Labels (Menu Titles) can be customised for individual pages');
            }
        }
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_automated_meta_description')) {
            //do nothing
        } else {
            if ($siteConfig->UpdateMetaDescription) {
                $v[] = _t('MetaTagsSTE.UPDATE_META_DESC_ON', 'The Meta Descriptions are automatically updated');
            } else {
                $v[] = _t('MetaTagsSTE.UPDATE_META_DESC_OFF', 'The Meta Descriptions can be customised for individual pages');
            }
        }

        return '<br />- ' . implode('<br />- ', $v);
    }
}
