<?php

class MetaTagsControllerExtension extends DataExtension {

    public function MetaTags($tags)
    {
        $page = $this->getOwner();
        if ($page->has_extension('MetaTagsExtension')) {
            $page = $this->getOwner();
            if (strlen($page->MetaTitle)) {
                $tags = preg_replace('/<title>.*<\/title>/', '<title>' . $page->MetaTitle . '</title>', $tags);
            }
        }

        return $tags;
    }


}