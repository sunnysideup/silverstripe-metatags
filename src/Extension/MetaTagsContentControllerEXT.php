<?php

namespace Sunnysideup\MetaTags\Extension;
use Sunnysideup\MetaTags\Api\MetatagsApi;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

use SilverStripe\Versioned\Versioned;

/**
 * adds meta tag functionality to the Page_Controller.
 */
class MetaTagsContentControllerEXT extends Extension implements Flushable
{

    private static $metatag_builder_class = MetatagsApi::class;

    /**
     * Puts together all the requirements.
     *
     * @param bool  $force         - run it again
     */
    protected function addBasicMetatagRequirements($force = false)
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) && (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))) {
            header('X-UA-Compatible: IE=edge,chrome=1');
        }
    }

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
    public function ExtendedMetatags(?bool $includeTitle = true, ?bool $addExtraSearchEngineData = true)
    {
        $builder = Injector::inst()->get($this->Config()->get(self::class, 'metatag_builder_class'));
        $tags = $builder->getMetatags($this->owner->dataRecord);

        return DBField::create_field('HTMLText', $this->renderWith('asdf'));
    }
}
