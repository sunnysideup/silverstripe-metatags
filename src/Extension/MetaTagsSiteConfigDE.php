<?php

namespace Sunnysideup\MetaTags\Extension;

use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use Sunnysideup\MetaTags\Extension\MetaTagsContentControllerEXT;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TabSet;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\ORM\DataExtension;

/**
 * adding functionality to SiteConfig
 *
 *
 */
class MetaTagsSiteConfigDE extends DataExtension /*
### @@@@ START UPGRADE REQUIRED @@@@ ###
FIND:  extends DataExtension
NOTE: Check for use of $this->anyVar and replace with $this->anyVar[$this->owner->ID] or consider turning the class into a trait
### @@@@ END UPGRADE REQUIRED @@@@ ###
*/
{
    private static $db = array(
        //meta title embelishments
        'PrependToMetaTitle' => 'Varchar(60)',
        'AppendToMetaTitle' => 'Varchar(60)',
        //other meta data
        'MetaDataCountry' => 'Varchar(60)',
        'MetaDataCopyright' => 'Varchar(60)',
        'MetaDataDesign' => 'Varchar(60)',
        'MetaDataCoding' => 'Varchar(60)',
        // flags
        'UpdateMenuTitle' => 'Boolean',
        'UpdateMetaDescription' => 'Boolean',
        // extra meta
        'ExtraMeta' => 'HTMLText'
    );

    private static $has_one = array(
        "Favicon" => Image::class
    );

    public function populateDefaults()
    {
        $this->MetaDataCountry = "New Zealand";
        $this->MetaDataCopyright = "site owner";
        $this->MetaDataDesign = "site owner";
        $this->MetaDataCoding = "site owner";
    }

    public function updateCMSFields(FieldList $fields)
    {
        $tabs = [];
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_search_engine_instructions")) {
            //do nothing
        } else {
            $tabs[] =
                Tab::create(
                    'Intro',
                    LiteralField::create(
                        'HelpExplanation',
                        '
                        <h3>Search Engine Optimisation (SEO)</h3>
                        <p>
                            To improve your visibility with search engines, we provide a number of tools here.
                            Here are some general suggestions for improving your page rankings:
                        </p>
                        <ul>
                            <li> - decide on a few keywords for each page - basically the words that people would search for on Google (e.g. <i>feed elderly cat</i>)</li>
                            <li> - ensure that these words are seen in strategic places on that page</li>
                            <li> - create links to the page from <i>third-party</i> websites, and pages within your site, using those keywords.</li>
                        </ul>
                        '
                    )
                );
        }
        $tabs[] = Tab::create(
            'Meta Title',
            LiteralField::create('MetaTitleExplanation', '<h3>&ldquo;Meta Titles&rdquo;: Bookmark and Browser Titles</h3><p>These are found at the top of your browser bar and these titles are also used when you bookmark a page.</p>'),
            TextField::create('PrependToMetaTitle', 'Prepend')->setRightTitle('add to the front of Meta Title'),
            TextField::create('AppendToMetaTitle', 'Append')->setRightTitle('add at the end of Meta Title')
        );

        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_automated_menu_title")) {
            //do nothing
        } else {
            $tabs[] =
                Tab::create(
                    'Menus',
                    LiteralField::create('MenuTitleExplanation', '<h3>Menu Title</h3><p>To improve consistency, you can set the menu title to automatically match the page title for any page on the site. </p>'),
                    CheckboxField::create('UpdateMenuTitle', 'Automatically')->setDescription('Automatically update the Menu Title / Navigation Label to match the Page Title?')
                );
        }

        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_automated_meta_description")) {
            //do nothing
        } else {
            $tabs[] =
                Tab::create(
                    'Meta Description',
                    LiteralField::create('MetaDescriptionExplanation', '<h3>&ldquo;Meta Description&rdquo;: Page Summary for Search Engines</h3><p>The Meta Description is not visible on the website itself. However, it is picked up by search engines like google.  They display it as the short blurb underneath the link to your pages. It will not get you much higher in the rankings, but it will entice people to click on your link.</p>'),
                    CheckboxField::create('UpdateMetaDescription', 'Automatically')->setDescription('Automatically fill every meta description on every Page (using the first '.Config::inst()->get(MetaTagsContentControllerEXT::class, "meta_desc_length").' words of the Page Content field).')
                );
        }
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_additional_meta_settings")) {
            //do nothing ...
        } else {
            $tabs[] = Tab::create(
                'Other Meta Data',
                LiteralField::create('MetaOtherExplanation', '<h3>Other &ldquo;Meta Data&rdquo;: More hidden information about the page</h3><p>You can add some other <i>hidden</i> information to your pages - which can be picked up by Search Engines and other automated readers decyphering your website.</p>'),
                TextField::create('MetaDataCountry', 'Country'),
                TextField::create('MetaDataCopyright', 'Content Copyright'),
                TextField::create('MetaDataDesign', 'Design provided by'),
                TextField::create('MetaDataCoding', 'Website Coding provided by'),
                TextareaField::create('ExtraMeta', 'Custom Meta Tags')->setRightTitle('Careful - advanced users only')
            );
        }
        if (count($tabs)) {
            $fields->addFieldToTab(
                'Root.SearchEngines',
                $tabSet = TabSet::create(
                    'Options'
                )
            );
            foreach ($tabs as $tab) {
                $tabSet->push($tab);
            }
        }
        $fields->addFieldToTab("Root.Icons", $uploadField = UploadField::create('Favicon', 'Icon'));
        $uploadField->setAllowedExtensions(array("png"));
        $uploadField->setRightTitle(
            "
            Upload a 480px wide x 480px high, non-transparent PNG file.
            Ask your developer for help if unsure.
            Note for advanced users:
                icons can also be loaded onto the server directly into the /themes/mytheme/icons/ folder
                and as a favicon.ico in the root directory."
        );
        return $fields;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, "no_additional_meta_settings")) {
            $this->owner->MetaDataCountry = '';
            $this->owner->MetaDataCopyright = '';
            $this->owner->MetaDataDesign = '';
            $this->owner->MetaDataCoding = '';
            $this->owner->ExtraMeta = '';
        }
    }
}
