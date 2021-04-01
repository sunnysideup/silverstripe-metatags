<?php

namespace Sunnysideup\MetaTags\Extension;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

/**
 * adds meta tag functionality to the Page_Controller
 */
class MetaTagsContentControllerEXT extends Extension implements Flushable
{
    /**
     * @var string
     */
    private static $favicon_sizes = [
        '16',
        '32',
        //"57",
        //"72",
        //"76",
        //"96",
        //"114",
        //"120",
        '128',
        '144',
        //"152",
        //"180",
        //"192",
        '310',
    ];

    /**
     * the twitter handle used by the site
     * do not include @ sign.
     * @var string
     */
    private static $twitter_handle = '';

    /**
     * dont show users basic instructions for SEO
     * @var bool
     */
    private static $no_search_engine_instructions = false;

    /**
     * allow user to enter a separate meta title?
     * @var bool
     */
    private static $use_separate_metatitle = false;

    /**
     * stop users from automating the menu title
     * @var bool
     */
    private static $no_automated_menu_title = false;

    /**
     * stop users from automating meta descriptions
     * @var bool
     */
    private static $no_automated_meta_description = false;

    /**
     * stop users from automating custom meta settings (custom tags, country, date, etc...)
     * @var bool
     */
    private static $no_additional_meta_settings = false;

    /**
     * length of auto-generated meta descriptions in header
     * @var int
     */
    private static $meta_desc_length = 24;

    /**
     * what should be included on every page?
     * @var array
     */
    private static $default_css = [
        'reset' => null,
        'typography' => null,
        'layout' => null,
        'form' => null,
        'menu' => null,
        'individualPages' => null,
        'responsive' => null,
        'print' => 'print',
    ];

    /**
     * specify location for jquery CDN location
     * @var array
     */
    private static $jquery_cdn_location = '';

    /**
     * what should be included on every page?
     * @var array
     */
    private static $default_js = [
        'framework/thirdparty/jquery/jquery.js',
    ];

    /**
     * @var string
     * folder where the combined css / js files will be stored
     * if they are combined.
     */
    private static $folder_for_combined_files = 'assets';

    /**
     * @var string
     * viewport setting
     */
    private static $viewport_setting = 'width=device-width,initial-scale=1';

    /**
     * map Page types and methods for use in the
     * facebook open graph.
     * e.g.MyProductPage: ProductImage
     *
     * @var array
     **/
    private static $og_image_method_map = [];

    /**
     * google fonts to be used
     * @var array
     **/
    private static $google_font_collection = [];

    /**
     * combine css files into one?
     * @var boolean
     */
    private static $combine_css_files_into_one = false;

    /**
     * combine js files into one?
     * @var boolean
     */
    private static $combine_js_files_into_one = false;

    /**
     * add all the basic js and css files - call from Page::init()
     * @var array
     */
    private static $_metatags_building_completed = [];

    private $_shareImage = [];

    /**
     * add Jquery
     */
    public function onBeforeInit()
    {
        $jQueryCDNLocation = Config::inst()->get(MetaTagsContentControllerEXT::class, 'jquery_cdn_location');
        if ($jQueryCDNLocation) {
            Requirements::block('silverstripe/admin: thirdparty/jquery/jquery.js');
            Requirements::javascript($jQueryCDNLocation);
        } else {
            Requirements::javascript('silverstripe/admin: thirdparty/jquery/jquery.js');
        }
    }

    /**
     * Puts together all the requirements.
     *
     * @param array $additionalJS (foo.js, bar.js)
     * @param array $additionalCSS (name => media type)
     * @param bool $force - run it again
     */
    public function addBasicMetatagRequirements($additionalJS = [], $additionalCSS = [], $force = false)
    {
        if (! isset(self::$_metatags_building_completed[$this->owner->dataRecord->ID]) || $force) {
            $jsFile = '';
            $cssFile = '';
            $folderForCombinedFilesWithBase = '';
            $combineJS = Config::inst()->get(MetaTagsContentControllerEXT::class, 'combine_js_files_into_one');
            $combineCSS = Config::inst()->get(MetaTagsContentControllerEXT::class, 'combine_css_files_into_one');
            if ($combineJS || $combineCSS) {
                $folderForCombinedFiles = Config::inst()->get(MetaTagsContentControllerEXT::class, 'folder_for_combined_files');
                $folderForCombinedFilesWithBase = Director::baseFolder() . '/' . $folderForCombinedFiles;
                if ($combineJS) {
                    $jsFile = $folderForCombinedFiles . '/MetaTagAutomation.js';
                }
                if ($combineCSS) {
                    $cssFile = $folderForCombinedFiles . '/MetaTagAutomation.css';
                }
            }
            $jQueryCDNLocation = Config::inst()->get(MetaTagsContentControllerEXT::class, 'jquery_cdn_location');
            $cssArray = Config::inst()->get(MetaTagsContentControllerEXT::class, 'default_css');
            $jsArray = Config::inst()->get(MetaTagsContentControllerEXT::class, 'default_js');
            // if(Director::isLive() && 1 == 2) {
            //     foreach($cssArray as $tempKey => $tempValue) {
            //         $newArray = [];
            //         if(strpos('.css', $tempKey)) {
            //             $newKey = str_replace('.css', '.min.css', $tempKey);
            //         } else {
            //             $newKey = $tempKey.'.min';
            //         }
            //         $newArray[$newKey] = $tempValue;
            //     }
            //     $cssArray = $newArray;
            //     foreach($jsArray as $tempKey => $tempValue) {
            //         $jsArray[$tempKey] = str_replace('.js', '.min.js', $tempValue);
            //     }
            // }
            $jsArray = array_unique(array_merge($jsArray, $additionalJS));

            //javascript
            if ($combineJS && file_exists($folderForCombinedFilesWithBase . $jsFile)) {
                Requirements::javascript($jsFile);
            } else {
                foreach ($jsArray as $key => $js) {
                    if (strpos($js, 'framework/thirdparty/jquery/jquery.js') !== false) {
                        //remove, as already included
                        unset($jsArray[$key]);
                    } elseif (! isset($alreadyDone[$js])) {
                        Requirements::javascript($js);
                        $alreadyDone[$js] = 1;
                    }
                }
            }
            //put jQuery back in, if needed.
            if (! $jQueryCDNLocation) {
                array_unshift($jsArray, 'framework/thirdparty/jquery/jquery.js');
            }

            if ($combineJS) {
                Requirements::combine_files($jsFile, $jsArray);
            }

            //css
            if ($combineCSS && file_exists($folderForCombinedFilesWithBase . $cssFile)) {
                Requirements::css($cssFile);
            } else {
                $expendedCSSArray = [];
                foreach ($cssArray as $name => $media) {
                    if (strpos($name, '.css')) {
                        $expendedCSSArray[] = [
                            'location' => $name,
                            'media' => $media,
                        ];
                    } else {
                        $expendedCSSArray[] = [
                            'location' => ThemeResourceLoader::inst()->findThemedResource('css/' . $name . '.css'),
                            'media' => $media,
                        ];
                    }
                }
                $cssArrayLocationOnly = [];
                $expendedCSSArray = array_merge($expendedCSSArray, $additionalCSS);
                foreach ($expendedCSSArray as $cssArraySub) {
                    if ($cssArraySub['location']) {
                        Requirements::css($cssArraySub['location'], $cssArraySub['media']);
                        $cssArrayLocationOnly[] = $cssArraySub['location'];
                    }
                }
                if ($combineCSS) {
                    Requirements::combine_files($cssFile, $cssArrayLocationOnly);
                }
            }

            //google font
            $googleFontArray = Config::inst()->get(MetaTagsContentControllerEXT::class, 'google_font_collection');
            if (is_array($googleFontArray) && count($googleFontArray)) {
                $protocol = Director::protocol();
                $fonts = implode('|', $googleFontArray);
                $fonts = str_replace(' ', '+', $fonts);
                Requirements::insertHeadTags('
                <link href="' . $protocol . 'fonts.googleapis.com/css?family=' . $fonts . '" rel="stylesheet" type="text/css" />');
            }

            //ie header...
            if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
                header('X-UA-Compatible: IE=edge,chrome=1');
            }
            self::$_metatags_building_completed[$this->owner->dataRecord->ID] = true;
        }
    }

    /**
     * this function will add more metatags to your template -
     * make sure to add it at the start of your metatags
     * We leave the / closing tags here, but they are not needed
     * yet not invalid in html5
     * @param bool $includeTitle - include the title tag
     * @param bool $addExtraSearchEngineData - add extra tags describing the page
     * @return string (HTML)
     */
    public function ExtendedMetatags($includeTitle = true, $addExtraSearchEngineData = true)
    {
        $siteConfig = SiteConfig::current_site_config();
        $this->addBasicMetatagRequirements();
        $cacheKey = null;
        $add = '';
        $tags = '';
        $cache = null;
        $base = Director::absoluteBaseURL();
        if (! isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '';
        }
        if ($this->owner->hasMethod('metatagsCacheKey')) {
            $add = $this->owner->metatagsCacheKey();
        }
        if ($add !== false) {
            $cacheKey =
                'ExtendedMetaTags_'
                . abs($this->owner->ID) . '_'
                . strtotime($this->owner->LastEdited) . '_'
                . strtotime($siteConfig->LastEdited) . '_'
                . $base . '_'
                . $_SERVER['REQUEST_URI'];
            $cacheKey = preg_replace(
                '#[^a-z0-9]#i',
                '_',
                $cacheKey
            );
            if ($add) {
                $cacheKey .= $add;
            }
        }
        if ($cacheKey) {
            $cache = self::get_meta_tag_cache();
            $tags = $cache->get($cacheKey);
        }
        if (! $tags) {
            $tags = '';
            $page = $this->owner;
            $siteConfig = SiteConfig::current_site_config();
            $title = $this->MetaTagsMetaTitle();
            //base tag
            $base = Director::absoluteBaseURL();
            $tags .= "<base href=\"{$base}\" />";
            if ($this->owner->hasMethod('CanonicalLink')) {
                $canonicalLink = $this->owner->CanonicalLink();
                if ($canonicalLink) {
                    $tags .= '
            <link rel="canonical" href="' . $canonicalLink . '" />';
                }
            }
            //these go first - for some reason ...
            if ($addExtraSearchEngineData) {
                $tags .= '
            <meta http-equiv="X-UA-Compatible" content="IE=edge" />
            <meta name="viewport" content="' . Config::inst()->get(MetaTagsContentControllerEXT::class, 'viewport_setting') . '" />';
            }

            if ($page->MetaDescription) {
                $description = '
            <meta name="description" content="' . Convert::raw2att($page->MetaDescription) . '" />';
                $noopd = '';
            } else {
                $noopd = 'NOODP, ';
                $description = '';
            }
            $lastEdited = new DBDatetime();
            $lastEdited->value = $page->LastEdited;

            //use base url rather than / so that sites that aren't a run from the root directory can have a favicon
            $faviconBase = $base;
            $faviconFileBase = '';
            if ($includeTitle) {
                $titleTag = '
            <title>' . trim(Convert::raw2att($siteConfig->PrependToMetaTitle . ' ' . $title . ' ' . $siteConfig->AppendToMetaTitle)) . '</title>';
            } else {
                $titleTag = '';
            }
            $tags .= '
            <meta charset="utf-8" />' .
                $titleTag;
            $hasBaseFolderFavicon = false;
            $baseFolderFavicon = '/' . $faviconFileBase . 'favicon.ico';
            if (file_exists(Director::baseFolder() . $baseFolderFavicon)) {
                $hasBaseFolderFavicon = true;
                //ie only...
                $tags .= '
            <link rel="SHORTCUT ICON" href="' . $baseFolderFavicon . '" />';
            }
            if (! $page->ExtraMeta && $siteConfig->ExtraMeta) {
                $page->ExtraMeta = $siteConfig->ExtraMeta;
            }
            if (! $siteConfig->MetaDataCopyright) {
                $siteConfig->MetaDataCopyright = $siteConfig->Title;
            }
            if ($addExtraSearchEngineData) {
                if ($page->ExcludeFromSearchEngines) {
                    $tags .= '
            <meta name="robots" content="' . $noopd . 'none, noindex, nofollow" />
            <meta name="googlebot" content="' . $noopd . 'none, noindex, nofollow" />';
                } else {
                    $tags .= '
            <meta name="robots" content="' . $noopd . 'all, index, follow" />
            <meta name="googlebot" content="' . $noopd . 'all, index, follow" />';
                }

                $tags .= '
            <meta name="rights" content="' . Convert::raw2att($siteConfig->MetaDataCopyright) . '" />
            <meta name="created" content="' . $lastEdited->Format('Ymd') . '" />
            <!--[if lt IE 9]>
                <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
            <![endif]-->
                ' . $page->ExtraMeta .
                $description;
            }
            $tags .= $this->OGTags();
            $tags .= $this->TwitterTags();
            $tags .= $this->iconTags($faviconBase, $hasBaseFolderFavicon);
            if ($cacheKey && $cache) {
                $cache->set($cacheKey, $tags);
            }
        }

        return DBField::create_field('HTMLText', $tags);
    }

    public static function flush()
    {
        self::get_meta_tag_cache()->clear();
    }

    protected static function get_meta_tag_cache()
    {
        return Injector::inst()->get(CacheInterface::class . '.metatags');
    }

    /**
     * open graph protocol
     * @see: http://ogp.me/
     * @return string (HTML)
     */
    protected function OGTags()
    {
        $title = $this->MetaTagsMetaTitle();
        $array = [
            'title' => Convert::raw2att($title),
            'type' => 'website',
            'url' => Convert::raw2att($this->owner->AbsoluteLink()),
            'site_name' => Convert::raw2att($this->owner->SiteConfig()->Title),
            'description' => Convert::raw2att($this->owner->MetaDescription),
        ];
        $html = '';
        $shareImage = $this->shareImage();
        if ($shareImage && $shareImage->exists()) {
            $array['image'] = Convert::raw2att($shareImage->getAbsoluteURL());
        }
        foreach ($array as $key => $value) {
            $html .= "
            <meta property=\"og:{$key}\" content=\"{$value}\" />";
        }
        return $html;
    }

    /**
     * twitter version of open graph protocol
     * twitter is only added if you set a handle in the configs:
     *
     *     MetaTagsContentControllerEXT:
     *       twitter_handle: "relevant_twitter_handle"
     *
     * @return string (HTML)
     */
    protected function TwitterTags(): string
    {
        $handle = $this->owner->getSiteConfig()->TwitterHandle;
        if (! $handle) {
            $handle = Config::inst()->get(MetaTagsContentControllerEXT::class, 'twitter_handle');
        }
        if ($handle) {
            $html = '';
            $array = [
                'title' => Convert::raw2att($this->owner->Title),
                'description' => Convert::raw2att($this->owner->MetaDescription),
                'url' => Convert::raw2att($this->owner->AbsoluteLink()),
                'site' => '@' . $handle,
            ];
            $shareImage = $this->shareImage();
            if ($shareImage && $shareImage->exists()) {
                $array['card'] = Convert::raw2att('summary_large_image');
                $array['image'] = Convert::raw2att($shareImage->getAbsoluteURL());
            } else {
                $array['card'] = Convert::raw2att('summary');
            }
            foreach ($array as $key => $value) {
                $html .= "
                <meta name=\"twitter:{$key}\" content=\"{$value}\" />";
            }
            return $html;
        }

        return '';
    }

    protected function iconTags($baseURL = '', $hasBaseFolderFavicon = false)
    {
        $favicon = null;
        if (! $baseURL) {
            $baseURL = Director::absoluteBaseURL();
        }
        $cacheKey =
            'iconTags_'
            . preg_replace(
                '#[^a-z0-9]#i',
                '_',
                $baseURL
            );
        $baseURL = rtrim($baseURL, '/');
        $cache = Injector::inst()->get(CacheInterface::class . '.metatags');

        $html = $cache->get($cacheKey);
        if ($html) {
            //do nothing
        } else {
            $sizes = Config::inst()->get(MetaTagsContentControllerEXT::class, 'favicon_sizes');
            if ($hasBaseFolderFavicon) {
                if (is_array($sizes)) {
                    $sizes = array_diff($sizes, [16]);
                }
            }
            $html = '';
            foreach ($sizes as $size) {
                $fileName = 'icons/' . 'icon-' . $size . 'x' . $size . '.png';
                $file = ThemeResourceLoader::inst()->findThemedResource(
                    $fileName,
                    SSViewer::get_themes()
                );
                if ($file && file_exists(Director::baseFolder() . $file)) {
                    $html .= '
<link rel="icon" type="image/png" sizes="' . $size . 'x' . $size . '"  href="' . $baseURL . $file . '" />
<link rel="apple-touch-icon" type="image/png" sizes="' . $size . 'x' . $size . '"  href="' . $baseURL . $file . '" />';
                } elseif ($this->owner->getSiteConfig()->FaviconID) {
                    if ($favicon = $this->owner->getSiteConfig()->Favicon()) {
                        if ($favicon->exists() && $favicon instanceof Image) {
                            $generatedImage = $favicon->ScaleWidth($size);
                            if ($generatedImage && $generatedImage->exists()) {
                                $html .= '
<link rel="icon" type="image/png" sizes="' . $size . 'x' . $size . '"  href="' . $baseURL . $generatedImage->Link() . '" />
<link rel="apple-touch-icon" type="image/png" sizes="' . $size . 'x' . $size . '"  href="' . $baseURL . $generatedImage->Link() . '" />';
                            } else {
                                $favicon = null;
                            }
                        } else {
                            $favicon = null;
                        }
                    }
                }
            }
            if ($hasBaseFolderFavicon) {
                //do nothing
            } else {
                $faviconLink = '';
                $faviconLocation = ThemeResourceLoader::inst()->findThemedResource('icons/favicon.ico');
                if ($faviconLocation && file_exists(Director::baseFolder() . $faviconLocation)) {
                    $faviconLink = $baseURL . $faviconLocation;
                } elseif ($favicon) {
                    $generatedImage = $favicon->ScaleWidth(16);
                    $faviconLink = $baseURL . $generatedImage->Link();
                }
                if ($faviconLink !== '') {
                    $html .= '
<link rel="SHORTCUT ICON" href="' . $faviconLink . '" />';
                }
            }
            $cache->set($cacheKey, $html);
        }

        return $html;
    }

    protected function MetaTagsMetaTitle()
    {
        $title = '';
        $page = $this->owner;
        if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'use_separate_metatitle')) {
            if (! empty($page->MetaTitle)) {
                $title = $page->MetaTitle;
            }
        }
        if (! $title) {
            $title = $page->Title;
            if (! $title) {
                $title = $page->MenuTitle;
            }
        }
        return $title;
    }

    /**
     * @return image|null
     */
    private function shareImage()
    {
        if (! isset($this->_shareImage[$this->owner->ID])) {
            $this->_shareImage[$this->owner->ID] = null;
        }
        if ($this->_shareImage[$this->owner->ID] === null) {
            $this->_shareImage[$this->owner->ID] = false;
            if ($this->owner->ShareOnFacebookImageID) {
                $this->_shareImage[$this->owner->ID] = $this->owner->ShareOnFacebookImage();
            } else {
                $og_image_method_map = Config::inst()->get(MetaTagsContentControllerEXT::class, 'og_image_method_map');
                if (is_array($og_image_method_map)) {
                    foreach ($og_image_method_map as $className => $method) {
                        if ($this->owner->dataRecord instanceof $className) {
                            $variable = $method . 'ID';
                            if (! empty($this->owner->dataRecord->{$variable})) {
                                if ($this->owner->hasMethod($method)) {
                                    $this->_shareImage[$this->owner->ID] = $this->owner->{$method}();
                                }
                            }
                        }
                    }
                }
            }
            //clean up any stuff...
            if ($this->_shareImage[$this->owner->ID] && $this->_shareImage[$this->owner->ID]->exists()) {
            } else {
                $hasOnes = $this->owner->hasOne();
                foreach ($hasOnes as $hasOneName => $hasOneType) {
                    if ($hasOneName !== 'ShareOnFacebookImage') {
                        if ($hasOneType === Image::class || is_subclass_of($hasOneType, Image::class)) {
                            $field = $hasOneName . 'ID';
                            if ($this->owner->{$field}) {
                                $this->_shareImage[$this->owner->ID] = $this->owner->{$hasOneName}();
                                break;
                            }
                        }
                    }
                }
            }
            if ($this->_shareImage[$this->owner->ID] && $this->_shareImage[$this->owner->ID]->exists()) {
            } else {
                $this->_shareImage[$this->owner->ID] = false;
            }
        }
        return $this->_shareImage[$this->owner->ID];
    }
}
