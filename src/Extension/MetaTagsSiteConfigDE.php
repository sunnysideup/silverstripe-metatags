<?php

namespace Sunnysideup\MetaTags\Extension;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * adding functionality to SiteConfig.
 *
 * @property SiteConfig|MetaTagsSiteConfigDE $owner
 * @property string $PrependToMetaTitle
 * @property string $AppendToMetaTitle
 * @property string $MetaDataCountry
 * @property string $MetaDataCopyright
 * @property string $MetaDataDesign
 * @property string $MetaDataCoding
 * @property bool $UpdateMenuTitle
 * @property bool $UpdateMetaDescription
 * @property string $ExtraMeta
 * @property string $TwitterHandle
 */
class MetaTagsSiteConfigDE extends Extension
{
    private static $db = [
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
        'ExtraMeta' => 'HTMLText',
        'TwitterHandle' => 'HTMLText',
    ];

    public function populateDefaults()
    {
        $owner = $this->getOwner();
        $owner->MetaDataCopyright = '';
        $owner->MetaDataDesign = '';
        $owner->MetaDataCoding = '';
    }

    public function updateCMSFields(FieldList $fields)
    {
        $tabs = [];
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_search_engine_instructions')) {
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
            TextField::create('PrependToMetaTitle', 'Prepend')->setDescription('add to the front of Meta Title'),
            TextField::create('AppendToMetaTitle', 'Append')->setDescription('add at the end of Meta Title')
        );

        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_automated_menu_title')) {
            //do nothing
        } else {
            $tabs[] =
                Tab::create(
                    'Menus',
                    LiteralField::create('MenuTitleExplanation', '<h3>Menu Title</h3><p>To improve consistency, you can set the menu title to automatically match the page title for any page on the site. </p>'),
                    CheckboxField::create('UpdateMenuTitle', 'Automatically')->setDescription('Automatically update the Menu Title / Navigation Label to match the Page Title?')
                );
        }

        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_automated_meta_description')) {
            //do nothing
        } else {
            $tabs[] =
                Tab::create(
                    'Meta Description',
                    LiteralField::create('MetaDescriptionExplanation', '<h3>&ldquo;Meta Description&rdquo;: Page Summary for Search Engines</h3><p>The Meta Description is not visible on the website itself. However, it is picked up by search engines like google.  They display it as the short blurb underneath the link to your pages. It will not get you much higher in the rankings, but it will entice people to click on your link.</p>'),
                    CheckboxField::create('UpdateMetaDescription', 'Automatically')->setDescription('Automatically fill every meta description on every Page (using the first ' . Config::inst()->get(MetaTagsContentControllerEXT::class, 'meta_desc_length') . ' words of the Page Content field).')
                );
        }

        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_additional_meta_settings')) {
            //do nothing ...
        } else {
            $tabs[] = Tab::create(
                'Other Meta Data',
                LiteralField::create('MetaOtherExplanation', '<h3>Other &ldquo;Meta Data&rdquo;: More hidden information about the page</h3><p>You can add some other <i>hidden</i> information to your pages - which can be picked up by Search Engines and other automated readers decyphering your website.</p>'),
                TextField::create('MetaDataCountry', 'Country'),
                TextField::create('MetaDataCopyright', 'Content Copyright'),
                TextField::create('MetaDataDesign', 'Design provided by'),
                TextField::create('MetaDataCoding', 'Website Coding provided by'),
                TextareaField::create('ExtraMeta', 'Custom Meta Tags')->setDescription('Careful - advanced users only')
            );
            $tabs[] = Tab::create(
                'Social',
                TextField::create('TwitterHandle', 'Twitter Handle')
                    ->setDescription('(e.g. BarackObama - how you address people on Twitter (x.com) but then without the @ sign.')
            );
        }
        $fields->addFieldToTab(
            'Root.SearchEngines',
            $tabSet = TabSet::create(
                'Options'
            )->setTitle('SEO (Google et al.)')
        );
        foreach ($tabs as $tab) {
            $tabSet->push($tab);
        }
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'no_additional_meta_settings')) {
            $this->getOwner()->MetaDataCountry = '';
            $this->getOwner()->MetaDataCopyright = '';
            $this->getOwner()->MetaDataDesign = '';
            $this->getOwner()->MetaDataCoding = '';
            $this->getOwner()->ExtraMeta = '';
        }

        $this->getOwner()->TwitterHandle = str_replace('@', '', (string) $this->getOwner()->TwitterHandle);
    }

    public function requireDefaultRecords()
    {
        $faviconQuery = 'SHOW COLUMNS FROM SiteConfig LIKE \'FaviconID\'';
        $webAppIconQuery = 'SHOW COLUMNS FROM SiteConfig LIKE \'WebAppManifestIconID\'';

        $faviconResult = DB::query($faviconQuery);
        $webAppIconResult = DB::query($webAppIconQuery);

        if ($faviconResult->numRecords() > 0 && $webAppIconResult->numRecords() > 0) {
            DB::query(
                '
                    UPDATE "SiteConfig"
                    SET "WebAppManifestIconID" = "FaviconID"
                    WHERE "WebAppManifestIconID" IS NULL OR "WebAppManifestIconID" = \'\' OR "WebAppManifestIconID" = 0
                '
            );
            DB::query('ALTER TABLE "SiteConfig" DROP COLUMN "FaviconID"');
            DB::alteration_message('Migration complete.');
        } else {
            DB::alteration_message('SiteConfig.FaviconID OR SiteConfig.WebAppManifestIconID does not exist.');
        }
    }
}
