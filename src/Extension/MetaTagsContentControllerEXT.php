<?php

namespace Sunnysideup\MetaTags\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\Requirements;

/**
 * adds meta tag functionality to the Page_Controller.
 */
class MetaTagsContentControllerEXT extends Extension
{
    /**
     * this function will add more metatags to your template -
     * make sure to add it at the start of your metatags
     * We leave the / closing tags here, but they are not needed
     * yet not invalid in html5.
     *
     * @param bool $includeTitle             - include the title tag
     * @param bool $addExtraSearchEngineData - add extra tags describing the page
     *
     * @return string (HTML)
     */
    public function ExtendedMetatags(?bool $includeTitle = true)
    {
        $this->addBasicMetatagRequirements();

        return DBField::create_field('HTMLText', $this->owner->Metatags($includeTitle));
    }

    /**
     * Puts together all the requirements.
     *
     * @param bool $force - run it again
     */
    protected function addBasicMetatagRequirements($force = false)
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) && (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))) {
            header('X-UA-Compatible: IE=edge,chrome=1');
        }
    }
}
