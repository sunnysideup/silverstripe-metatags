<?php
namespace Sunnysideup\MetaTags\Api;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;

use SilverStripe\Control\Controller;
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
class MetatagsApie implements Flushable
{
    use Extensible;
    use Injectable;
    use Configurable;

    protected $page = null;
    protected $baseUrl = '';
    protected $siteConfig = null;

    public function __construct($page)
    {
        $this->page = $page;
        $this->baseUrl = Director::absoluteBaseURL();
        $this->siteConfig = Director::absoluteBaseURL();
    }

    /**
     * @var array
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
     *
     * @var string
     */
    private static $twitter_handle = '';

    /**
     * allow user to enter a separate meta title?
     *
     * @var bool
     */
    private static $use_separate_metatitle = false;

    /**
     * @var string
     *             viewport setting
     */
    private static $viewport_setting = 'width=device-width,initial-scale=1';

    /**
     * map Page types and methods for use in the
     * facebook open graph.
     * e.g.MyProductPage: ProductImage.
     *
     * @var array
     */
    private static $og_image_method_map = [];

    protected $metatags = [];

    protected $shareImageCache = [];

    protected $metatagMetaTitle = [];

    public function getMetatagsArray() : array
    {
        if(empty($this->metatags)) {
            $cacheKey = $this->getCacheKey();
            $cache = self::get_meta_tag_cache();
            $this->siteConfig = SiteConfig::current_site_config();
            //useful later on

            if ($cacheKey) {
                $this->metatags = unserialize($cache->get($cacheKey));
            }
            if (empty($this->metatags)) {
                $this->metatags = [];
                $title = $this->MetaTagsMetaTitle();
                //base tag
                $this->addToMetatags('base',['href' => $base,]);
                $this->addToMetatags('meta',['charset' => 'utf-8',]);
                $titleArray = [
                    $this->siteConfig->PrependToMetaTitle,
                    $title,
                    $this->siteConfig->AppendToMetaTitle,
                ];
                $content = trim(implode(' ', array_filter($titleArray)));
                $this->addToMetatags('title',[], false, Convert::raw2att($content));
                $this->addToMetatags('meta',['name' => 'title','content' => Convert::raw2att($content),]);

                if ($this->page->hasMethod('CanonicalLink')) {
                    $canonicalLink = $this->page->CanonicalLink();
                    if ($canonicalLink) {
                        $this->addToMetatags('link',['rel' => 'canonical','href' => $canonicalLink,]);
                    }
                }
                //these go first - for some reason ...
                $this->addToMetatags('meta',['http-equiv' => 'X-UA-Compatible','name' => 'IE=edge',]);
                $this->addToMetatags('meta',['name' => 'viewport','content' => Config::inst()->get(self::class, 'viewport_setting'),]);

                if ($this->page->MetaDescription) {
                    $this->addToMetatags('meta',['name' => 'description','content' => Convert::raw2att($this->page->MetaDescription),]);

                    $noopd = '';
                } else {
                    $noopd = 'NOODP, ';
                }

                //use base url rather than / so that sites that aren't a run from the root directory can have a favicon
                $hasBaseFolderFavicon = false;
                $publicDir = PUBLIC_DIR;
                $faviconFileName = 'favicon.ico';
                $faviconLocation = Controller::join_links($this->baseUrl , $publicDir , $faviconFileName);
                if (file_exists($faviconLocation)) {
                    $this->addToMetatags('link',['rel' => 'SHORTCUT ICON','href' => $faviconFileName,]);
                    $hasBaseFolderFavicon = true;
                    //ie only...
                }
                if (! $this->page->ExtraMeta && $this->siteConfig->ExtraMeta) {
                    $this->page->ExtraMeta = $this->siteConfig->ExtraMeta;
                }
                if (! $this->siteConfig->MetaDataCopyright) {
                    $this->siteConfig->MetaDataCopyright = $this->siteConfig->Title;
                }
                if ($this->page->ExcludeFromSearchEngines) {
                    $botsValue = $noopd . 'none, noindex, nofollow';
                } else {
                    $botsValue = $noopd . 'all, index, follow';
                }
                $this->addToMetatags('meta',['name' => 'robots','content' => $botsValue,]);
                $this->addToMetatags('meta',['name' => 'googlebot','content' => $botsValue,]);
                $this->addToMetatags('meta',['name' => 'rights','content' => Convert::raw2att($this->siteConfig->MetaDataCopyright),]);
                $this->addToMetatags('meta',['name' => 'created','content' => date('Ymd', strtotime($this->page->LastEdited)),]);
                $this->metatags[] = [
                    'RawHTML' => $this->page->ExtraMeta,
                ];
                $this->addOGTags();
                $this->addTwitterTags();
                $this->addIconTags($faviconBase, $hasBaseFolderFavicon);
                if ($cacheKey && $cache) {
                    $cache->set($cacheKey, serialize($this->metatags));
                }
            }
        }
        return $this->metatags;
    }

    protected function getCacheKey() : string
    {
        $this->siteConfig = SiteConfig::current_site_config();
        $add = null;
        $cacheKey = '';
        if ($this->page->hasMethod('metatagsCacheKey')) {
            $add = $this->page->metatagsCacheKey();
        }
        if (! isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '';
        }
        if (false !== $add) {
            $cacheKey =
                'ExtendedMetaTags_'
                . abs($this->page->ID) . '_'
                . strtotime($this->page->LastEdited) . '_'
                . strtotime($this->siteConfig->LastEdited) . '_'
                . $this->this->baseUrl . '_'
                . Versioned::get_stage()
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
        return $cacheKey;
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
     * open graph protocol.
     *
     * @see: http://ogp.me/
     *
     * @return string (HTML)
     */
    protected function addOGTags()
    {
        $this->siteConfig = SiteConfig::current_site_config();
        $array = [
            'title' => Convert::raw2att($this->MetaTagsMetaTitle()),
            'type' => 'website',
            'url' => Convert::raw2att($this->page->AbsoluteLink()),
            'site_name' => Convert::raw2att($this->siteConfig->Title),
            'description' => Convert::raw2att($this->page->MetaDescription),
        ];
        $shareImage = $this->shareImage();
        if ($shareImage && $shareImage->exists()) {
            $array['image'] = Convert::raw2att($shareImage->getAbsoluteURL());
        }
        foreach ($array as $key => $value) {
            $this->addToMetatags('meta',['property' => 'og:'.$key, 'content' => $value,]);
        }
    }

    /**
     * twitter version of open graph protocol
     * twitter is only added if you set a handle in the configs:.
     *
     *     MetaTagsContentControllerEXT:
     *       twitter_handle: "relevant_twitter_handle"
     *
     */
    protected function addTwitterTags()
    {
        $this->siteConfig = SiteConfig::current_site_config();
        $handle = $this->siteConfig->TwitterHandle;
        if (! $handle) {
            $handle = Config::inst()->get(self::class, 'twitter_handle');
        }
        if ($handle) {
            $array = [
                'title' => Convert::raw2att($this->MetaTagsMetaTitle()),
                'description' => Convert::raw2att($this->page->MetaDescription),
                'url' => Convert::raw2att($this->page->AbsoluteLink()),
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
                $this->addToMetatags('meta',['name' => 'twitter:'.$key, 'content' => $value,]);
            }
        }
    }

    protected function addIconTags(?bool $hasBaseFolderFavicon = false)
    {
        $favicon = null;
        $cacheKey =
            'iconTags_'
            . preg_replace(
                '#[^a-z0-9]#i',
                '_',
                $baseURL
            );
        $baseURL = rtrim($this->baseURL, '/');
        $cache = self::get_meta_tag_cache();

        $icons = unserialize($cache->get($cacheKey));
        if (empty($icons)) {
            $sizes = Config::inst()->get(self::class, 'favicon_sizes');
            if ($hasBaseFolderFavicon) {
                if (is_array($sizes)) {
                    $sizes = array_diff($sizes, [16]);
                }
            }
            foreach ($sizes as $size) {
                $fileName = 'icons/' . 'icon-' . $size . 'x' . $size . '.png';
                $file = ThemeResourceLoader::inst()->findThemedResource(
                    $fileName
                );
                if ($file) {
                    $sizes = $size . 'x' . $size . '"  href="' . Controller::join_links($baseURL , $file);
                    $this->addToMetatags('link',['name' => 'icon', 'type' => 'image/png', 'sizes' => $sizes,]);
                    $this->addToMetatags('link',['name' => 'apple-touch-icon', 'type' => 'image/png', 'sizes' => $sizes,]);
                } else{
                    if(! isset($faviconImage)) {
                        $faviconImage = false;
                        if ($this->siteConfig->FaviconID) {
                            $faviconImage = $this->siteConfig->Favicon();
                        }
                    }
                    if ($faviconImage && $faviconImage->exists() && $faviconImage instanceof Image) {
                        $generatedImage = $faviconImage->ScaleWidth($size);
                        if ($generatedImage && $generatedImage->exists()) {
                            $sizes = $size . 'x' . $size . '"  href="' . $baseURL . $generatedImage->Link();
                            $this->addToMetatags('link',['name' => 'icon', 'type' => 'image/png', 'sizes' => $sizes,]);
                            $this->addToMetatags('link',['name' => 'apple-touch-icon', 'type' => 'image/png', 'sizes' => $sizes,]);
                        }
                    }
                }
            }
            if (! $hasBaseFolderFavicon) {
                $faviconLocation = ThemeResourceLoader::inst()->findThemedResource('icons/favicon.ico');
                if ($faviconLocation) {
                    //do nothing
                } elseif ($faviconImage) {
                    $generatedImage = $faviconImage->ScaleWidth(16);
                    $faviconLocation = $generatedImage->Link();
                }
                $faviconLink = Controller::join_links($baseURL, $faviconLocation);
                if ($faviconLink) {
                    $this->addToMetatags('link',['rel' => 'SHORTCUT ICON', 'href' => $faviconLink,]);
                }
            }
            $cache->set($cacheKey, serialize($icons));
        }

        $this->metatags = array_merge($this->metatags, $icons);
    }

    /**
     * create Meta Tag Title
     * @return string
     */
    protected function MetaTagsMetaTitle() : string
    {
        if(! $this->metatagMetaTitle) {
            $this->metatagMetaTitle = '';
            if (Config::inst()->get(self::class, 'use_separate_metatitle')) {
                if (! empty($this->page->MetaTitle)) {
                    $this->metatagMetaTitle = $this->page->MetaTitle;
                }
            }
            if (! $this->metatagMetaTitle) {
                $this->metatagMetaTitle = $this->page->Title;
                if (! $this->metatagMetaTitle ) {
                    $this->metatagMetaTitle = $this->page->MenuTitle;
                }
            }
        }
        return $this->metatagMetaTitle;
    }

    /**
     * @return null|Image
     */
    protected function shareImage() :?Image
    {
        if (! isset($this->shareImageCache[$this->page->ID])) {
            $this->shareImageCache[$this->page->ID] = null;
        }
        if (null === $this->shareImageCache[$this->page->ID]) {
            $this->addToShareImageCache('ShareOnFacebookImage');
            if (! $this->shareImageCache[$this->page->ID]) {
                $og_image_method_map = Config::inst()->get(self::class, 'og_image_method_map');
                $method = $og_image_method_map[$this->page->ClassName] ?? 'ERROR';
                if($method) {
                    $this->addToShareImageCache($method);
                }
            }
            if (! $this->shareImageCache[$this->page->ID]) {
                $hasOnes = $this->page->hasOne();
                foreach ($hasOnes as $hasOneName => $hasOneType) {
                    if ('ShareOnFacebookImage' !== $hasOneName) {
                        if (Image::class === $hasOneType || is_subclass_of($hasOneType, Image::class)) {
                            if($this->addToShareImageCache($hasOneName)) {
                                break;
                            }
                        }
                    }
                }
            }
        }
        //make sure to return NULL or Image
        return $this->shareImageCache[$this->page->ID] ?:null;
    }

    protected function addToShareImageCache(string $methodName) : bool
    {
        $field = $methodName . 'ID';
        if (isset($this->page->{$field})) {
            if($this->page->hasMethod($methodName)) {
                $this->shareImageCache[$this->page->ID] = $this->page->{$methodName}();
                if(
                    $this->shareImageCache[$this->page->ID] &&
                    $this->shareImageCache[$this->page->ID]->exists() &&
                    $this->shareImageCache[$this->page->ID] instanceof Image
                ) {
                    return true;
                }
            }
        }
        $this->shareImageCache[$this->page->ID] = false;
        return false;
    }

    protected function addToMetatags(string $tag, ?array $attributes = [], $selfClosing = true, ?string $content = '')
    {
        $this->metatags[] = [
            'Tag' => $tag,
            'Attributes' => $attributes,
            'SelfClosing' => $selfClosing,
            'Content' => $content,
            'RawHTML' => '',
        ];
    }

}
