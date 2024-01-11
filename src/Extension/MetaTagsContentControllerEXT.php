<?php

namespace Sunnysideup\MetaTags\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * adds meta tag functionality to the Page_Controller.
 *
 * @property ContentController|MetaTagsContentControllerEXT $owner
 */
class MetaTagsContentControllerEXT extends Extension
{
    public function ExtendedMetaTags(?bool $includeTitle = true): DBHTMLText
    {
        $this->addBasicMetatagRequirements();

        return DBHTMLText::create_field('HTMLText', $this->getOwner()->MetaTags($includeTitle));
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
