<?php

namespace Sunnysideup\MetaTags\Api;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use Sunnysideup\MetaTags\Extension\MetaTagsContentControllerEXT;

class MetaTagsApi implements Flushable
{
    use Extensible;
    use Injectable;
    use Configurable;

    public $this;

    public $baseURL;

    protected $page;

    protected $baseUrl = '';

    protected $siteConfig;

    protected $metatags = [];

    protected $shareImageCache = [];

    protected $metatagMetaTitle = [];

    /**
     * @var array
     */
    private static array $favicon_sizes = [
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

    private static array $skipped_tags = [];

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
     *             consider: shrink-to-fit=no
     */
    private static $viewport_setting = 'width=device-width,minimum-scale=1.0,maximum-scale=10.0,initial-scale=1.0';

    /**
     * map Page types and methods for use in the
     * facebook open graph. e.g.
     * Logo
     * Image.
     *
     * @var array
     */
    private static $og_image_method_map = [];

    private static $favicon_themed_dir = 'dist/favicons';

    public function __construct($page)
    {
        $this->page = $page;
        $this->baseUrl = Director::absoluteBaseURL();
        $this->siteConfig = SiteConfig::current_site_config();
    }

    public function getMetaTags(): array
    {
        if (empty($this->metatags)) {
            $cacheKey = $this->getCacheKey();
            $cache = self::get_meta_tag_cache();

            //useful later on

            if ($cacheKey !== '' && $cacheKey !== '0' && $cache->has($cacheKey)) {
                // @property array $metatags
                $this->metatags = unserialize((string) $cache->get($cacheKey));
                if (! is_array($this->metatags)) {
                    $this->metatags = [];
                }
            }

            if (empty($this->metatags)) {
                //base tag
                $this->addToMetaTags('baseTag', 'base', ['href' => $this->baseUrl]);
                $titleArray = [
                    $this->siteConfig->PrependToMetaTitle,
                    $this->MetaTagsMetaTitle(),
                    $this->siteConfig->AppendToMetaTitle,
                ];
                $content = trim(implode(' ', array_filter($titleArray)));
                $this->addToMetaTags('title', 'title', [], false, Convert::raw2att($content));
                $this->addToMetaTags('metaTitle', 'meta', ['name' => 'title', 'content' => Convert::raw2att($content)]);

                if ($this->page->hasMethod('CanonicalLink')) {
                    $canonicalLink = $this->page->CanonicalLink();
                    if ($canonicalLink) {
                        $this->addToMetaTags('canonical', 'link', ['rel' => 'canonical', 'href' => $canonicalLink]);
                    }
                }

                //these go first - for some reason ...
                $this->addToMetaTags('ie', 'meta', ['http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge']);
                $this->addToMetaTags('viewport', 'meta', ['name' => 'viewport', 'content' => Config::inst()->get(self::class, 'viewport_setting')]);

                if ($this->page->MetaDescription) {
                    $this->addToMetaTags('description', 'meta', ['name' => 'description', 'content' => Convert::raw2att($this->page->MetaDescription)]);

                    $noopd = '';
                } else {
                    $this->metatags['description'] = null;
                    $noopd = 'NOODP, ';
                }

                //use base url rather than / so that sites that aren't a run from the root directory can have a favicon
                $hasBaseFolderFavicon = false;
                $publicDir = PUBLIC_DIR;
                $faviconFileName = 'favicon.ico';
                $faviconLocation = Controller::join_links($this->baseUrl, $publicDir, $faviconFileName);
                if (file_exists($faviconLocation)) {
                    $this->addToMetaTags('favicon', 'link', ['rel' => 'SHORTCUT ICON', 'href' => $faviconFileName]);
                    $hasBaseFolderFavicon = true;
                    //ie only...
                }

                if (! $this->page->ExtraMeta && $this->siteConfig->ExtraMeta) {
                    $this->page->ExtraMeta = $this->siteConfig->ExtraMeta;
                }

                if (! $this->siteConfig->MetaDataCopyright) {
                    $this->siteConfig->MetaDataCopyright = $this->siteConfig->Title;
                }

                $botsValue = $this->page->ExcludeFromSearchEngines ? $noopd . 'none, noindex, nofollow' : $noopd . 'all, index, follow';
                $this->addToMetaTags('robots', 'meta', ['name' => 'robots', 'content' => $botsValue]);
                $this->addToMetaTags('googlebot', 'meta', ['name' => 'googlebot', 'content' => $botsValue]);
                $this->addToMetaTags('created', 'meta', ['name' => 'created', 'content' => date('Ymd', strtotime((string) $this->page->LastEdited))]);
                if ($this->siteConfig->MetaDataCopyright) {
                    $this->addToMetaTags('rights', 'meta', ['name' => 'rights', 'content' => Convert::raw2att($this->siteConfig->MetaDataCopyright)]);
                }

                if ($this->siteConfig->MetaDataDesign) {
                    $this->addToMetaTags('designer', 'meta', ['name' => 'web_author', 'content' => $this->siteConfig->MetaDataDesign]);
                }

                if ($this->siteConfig->MetaDataCoding) {
                    $this->addToMetaTags('web_author', 'meta', ['name' => 'web_author', 'content' => $this->siteConfig->MetaDataCoding]);
                }

                if ($this->siteConfig->MetaDataCountry) {
                    $this->addToMetaTags('geo.region', 'meta', ['name' => 'geo.region', 'content' => $this->siteConfig->MetaDataCountry]);
                }

                if ($this->page->ExtraMeta) {
                    $this->metatags[] = [
                        'html' => $this->page->ExtraMeta,
                    ];
                }

                $this->addOGTags();
                $this->addTwitterTags();
                $this->addIconTags($hasBaseFolderFavicon);
                if ($cacheKey && $cache) {
                    $cache->set($cacheKey, serialize($this->metatags));
                }
            }
        }

        return array_filter($this->metatags);
    }

    public static function flush()
    {
        self::get_meta_tag_cache()->clear();
    }

    /**
     * if metatagsCacheKey returns false then there is not cacheKey.
     */
    protected function getCacheKey(): string
    {
        $add = null;
        $cacheKey = '';
        if ($this->page->hasMethod('metatagsCacheKey')) {
            $add = $this->page->metatagsCacheKey();
        }

        if (! isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '';
        }
        // if metatagsCacheKey returns false then it should not be cached.
        if (false !== $add) {
            $cacheKey =
                'ExtendedMetaTags_'
                . abs($this->page->ID) . '_'
                . strtotime((string) $this->page->LastEdited) . '_'
                . strtotime((string) $this->siteConfig->LastEdited) . '_'
                . $this->baseUrl . '_'
                . Versioned::get_stage()
                . filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
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

    protected static function get_meta_tag_cache()
    {
        return Injector::inst()->get(CacheInterface::class . '.metatags');
    }

    /**
     * open graph protocol.
     *
     * @see: http://ogp.me/
     */
    protected function addOGTags()
    {
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
            if ($value) {
                $this->addToMetaTags('og' . $key, 'meta', ['property' => 'og:' . $key, 'content' => $value]);
            }
        }
    }

    /**
     * twitter version of open graph protocol
     * twitter is only added if you set a handle in the configs:.
     *
     *     MetaTagsContentControllerEXT:
     *       twitter_handle: "relevant_twitter_handle"
     */
    protected function addTwitterTags()
    {
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
                if ($value) {
                    $this->addToMetaTags('twitter' . $key, 'meta', ['name' => 'twitter:' . $key, 'content' => $value]);
                }
            }
        }
    }

    protected function addIconTags(?bool $hasBaseFolderFavicon = false)
    {
        $faviconImage = false;
        if ($this->siteConfig->FaviconID) {
            $faviconImage = $this->siteConfig->Favicon();
            if (! ($faviconImage && $faviconImage->exists() && $faviconImage instanceof Image)) {
                $faviconImage = false;
            }
        }

        $sizes = (array) Config::inst()->get(self::class, 'favicon_sizes');
        if ($hasBaseFolderFavicon && is_array($sizes)) {
            $sizes = array_diff($sizes, [16]);
        }

        foreach ($sizes as $size) {
            $href = $this->iconToUrl('icon-' . $size . 'x' . $size . '.png', $faviconImage, $size);
            if ($href !== '' && $href !== '0') {
                $sizes = $size . 'x' . $size;
                $this->addToMetaTags('icon' . $size, 'link', ['rel' => 'icon', 'type' => 'image/png', 'sizes' => $sizes, 'href' => $href]);
                $this->addToMetaTags('iconApple' . $size, 'link', ['rel' => 'apple-touch-icon', 'type' => 'image/png', 'sizes' => $sizes, 'href' => $href]);
            }
        }

        if (! $hasBaseFolderFavicon) {
            $href = $this->iconToUrl('favicon.ico', $faviconImage, 16);
            if ($href !== '' && $href !== '0') {
                $this->addToMetaTags('favicon', 'link', ['rel' => 'SHORTCUT ICON', 'href' => $href]);
            }
        }
    }

    /**
     * create Meta Tag Title.
     */
    protected function MetaTagsMetaTitle(): string
    {
        if (! $this->metatagMetaTitle) {
            $this->metatagMetaTitle = '';
            if (Config::inst()->get(MetaTagsContentControllerEXT::class, 'use_separate_metatitle') && ! empty($this->page->MetaTitle)) {
                $this->metatagMetaTitle = (string) $this->page->MetaTitle;
            }

            if (! $this->metatagMetaTitle) {
                $this->metatagMetaTitle = (string) $this->page->Title;
                if (! $this->metatagMetaTitle) {
                    $this->metatagMetaTitle = (string) $this->page->MenuTitle;
                }
            }
        }

        return (string) $this->metatagMetaTitle;
    }

    protected function shareImage(): ?Image
    {
        if (! isset($this->shareImageCache[$this->page->ID])) {
            $this->shareImageCache[$this->page->ID] = null;
        }

        if (null === $this->shareImageCache[$this->page->ID]) {
            $this->addToShareImageCache('ShareOnFacebookImage');
            if (! $this->shareImageCache[$this->page->ID]) {
                $methods = Config::inst()->get(self::class, 'og_image_method_map');
                if (is_array($methods) && count($methods)) {
                    foreach ($methods as $method) {
                        $this->addToShareImageCache($method);
                        if ($this->shareImageCache[$this->page->ID]) {
                            break;
                        }
                    }
                }
            }

            if (! $this->shareImageCache[$this->page->ID]) {
                $hasOnes = $this->page->hasOne();
                foreach ($hasOnes as $hasOneName => $hasOneType) {
                    if ('ShareOnFacebookImage' !== $hasOneName && (Image::class === $hasOneType || is_subclass_of($hasOneType, Image::class)) && $this->addToShareImageCache($hasOneName)) {
                        break;
                    }
                }
            }
        }

        //make sure to return NULL or Image
        return $this->shareImageCache[$this->page->ID] ?: null;
    }

    protected function addToShareImageCache(string $methodName): bool
    {
        if ($this->page->hasMethod($methodName)) {
            $this->shareImageCache[$this->page->ID] = $this->page->{$methodName}();
            if (
                $this->shareImageCache[$this->page->ID] &&
                $this->shareImageCache[$this->page->ID]->exists() &&
                $this->shareImageCache[$this->page->ID] instanceof Image
            ) {
                return true;
            }
        }

        $this->shareImageCache[$this->page->ID] = false;

        return false;
    }

    protected function addToMetaTags(string $name, string $tag, ?array $attributes = [], $selfClosing = true, ?string $content = '')
    {
        $skipped = (array) Config::inst()->get(self::class, 'skipped_tags');
        if(!empty($skipped) && in_array($name, $skipped)) {
            return;
        }
        $this->metatags[$name] = [
            'tag' => $tag,
            'attributes' => $attributes,
            'selfclosing' => $selfClosing,
            'content' => $content,
            'html' => '',
        ];
    }

    protected function iconToUrl(string $iconName, $faviconImage = null, ?int $size = 16): string
    {
        $faviconDir = Config::inst()->get(self::class, 'favicon_themed_dir');
        $fileName = Controller::join_links($faviconDir, $iconName);
        $file = ThemeResourceLoader::inst()->findThemedResource(
            $fileName,
            SSViewer::get_themes()
        );

        $href = (string) ModuleResourceLoader::singleton()->resolveURL($file);
        if (! $href && $faviconImage && $faviconImage instanceof Image && $faviconImage->exists()) {
            $generatedImage = $faviconImage->ScaleWidth($size);
            $href = (string) $generatedImage->getURL();
        }

        return $href;
    }
}
