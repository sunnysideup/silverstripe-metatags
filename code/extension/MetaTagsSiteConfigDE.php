<?php

/**
 * adding functionality to SiteConfig
 *
 *
 */
class MetaTagsSiteConfigDE extends DataExtension
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
        "Favicon" => "Image"
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
        $fields->addFieldToTab('Root.SearchEngines',
            new TabSet('Options',
                new Tab('Help',
                    new LiteralField('HelpExplanation', '
                        <h3>Search Engine - How to use ...</h3>
                        <p>
                            To improve your visibility with search engines, we provide a number of tools here.
                            Improving your rankings with Search Engines can work as follows:
                        </p>
                        <ul>
                            <li> - decide on a few keywords for each page - basically the words that people would search for on Google (e.g. <i>feed elderly cat</i>))</li>
                            <li> - ensure that these words are seen in strategic places on this page</li>
                            <li> - create links to the page from <i>third-party</i> websites</li>
                        </ul>
                        <p>
                            <br />The tools provided here help you to achieve these goals by ensuring:
                        </p>
                        <ul>
                            <li> - easy addition of keywords to key field (navigation label, meta description)</li>
                            <li> - you can adjust the file image names and descriptions to match the keywords</li>
                        </ul>
                        '
                    )
                ),
                new Tab('Menus',
                    new LiteralField('MenuTitleExplanation', '<h3>Menu Title</h3><p>To improve consistency, you can set the menu title to automatically match the page title for any page on the site. </p>'),
                    new CheckboxField('UpdateMenuTitle', 'Automatically update the Menu Title / Navigation Label to match the Page Title?')
                ),
                new Tab('Meta Title',
                    new TextField('PrependToMetaTitle', 'Prepend (add in front) of Meta Title'),
                    new TextField('AppendToMetaTitle', 'Append (add at the end) of Meta Title')
                ),
                new Tab('Meta Description',
                    new LiteralField('MetaDescriptionExplanation', '<h3>&ldquo;Meta Description&rdquo;: Summary for Search Engines</h3><p>The Meta Description is not visible on the website itself. However, it is picked up by search engines like google.  They display it as the short blurb underneath the link to your pages. It will not get you much higher in the rankings, but it will entice people to click on your link.</p>'),
                    new CheckboxField('UpdateMetaDescription', 'Automatically fill every meta description on every Page (using the first '.Config::inst()->get("MetaTagsContentControllerEXT", "meta_desc_length").' words of the Page Content field).')
                ),
                new Tab('Other Meta Data',
                    new LiteralField('MetaOtherExplanation', '<h3>Other &ldquo;Meta Data&rdquo;: More hidden information about the page</h3><p>You can add some other <i>hidden</i> information to your pages - which can be picked up by Search Engines and other automated readers decyphering your website.</p>'),
                    new TextField('MetaDataCountry', 'Country'),
                    new TextField('MetaDataCopyright', 'Content Copyright'),
                    new TextField('MetaDataDesign', 'Design provided by ...'),
                    new TextField('MetaDataCoding', 'Website Coding carried out by ...'),
                    new TextareaField('ExtraMeta', 'Custom Meta Tags (advanced users only)')
                )
            )
        );
        $fields->addFieldToTab("Root.Icons", $uploadField = new UploadField('Favicon', 'Icon'));
        $uploadField->setAllowedExtensions(array("png"));
        $uploadField->setRightTitle("Upload a 480px wide x 480px high non-transparent PNG file. Ask your developer for help if unsure. Icons can also be loaded onto the server directly into the /themes/mytheme/icons/ folder and as a favicon.ico in the root directory.");
        return $fields;
    }
}
