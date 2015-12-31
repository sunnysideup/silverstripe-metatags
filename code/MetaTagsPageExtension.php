<?php

class MetaTagsPageExtension extends DataExtension
{

    private static $db = array(
        'MetaTitle' => 'Varchar(70)',
    );

    public function updateCMSFields(FieldList $fields)
    {
        $metaData = $fields->fieldByName('Root.Main.Metadata');

        $metaFieldTitle = new TextField("MetaTitle", $this->owner->fieldLabel('MetaTitle'));
        $metaFieldTitle->setRightTitle(_t(
            'MetaTags.METATITLEHELP',
            'Shown at the top of the browser window and used as the "linked text" by search engines.'
        ))->addExtraClass('help');

        $metaData->insertBefore($metaFieldTitle, 'MetaDescription');

        return $fields;
    }

    public function updateFieldLabels(&$labels)
    {
        $labels['MetaTitle'] = _t('MetaTags.METATITLE', "Title");
    }

    public function MetaTags(&$tags)
    {
        $page = $this->getOwner();
        if ($page->has_extension('MetaTagsPageExtension')) {
            $page = $this->getOwner();
            if (strlen($page->MetaTitle)) {
                $tags = preg_replace('/<title>.*<\/title>/', '<title>' . $page->MetaTitle . '</title>', $tags);
            }
        }

        $config = SiteConfig::current_site_config();
        if (!$config->MetaGenerator) {
            $tags = preg_replace('/<meta.*name="generator".*\/>/', '', $tags);
        }


        return $tags;
    }
}
