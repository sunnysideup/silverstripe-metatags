Meta Tags
===============================================

This module simplifies the management of the header
tags for any html document.  It adds all the basics by
adding just one line to your silverstripe template:
$ExtendedMetaTags.

Secondly, this module can help with search engine optimisation.
This module contains extensive systems for improving
metatags such as metadescription as well as image titles.
It allows you to sort and organise your images into folders
that contain SEO friendly names, and so on.


Developer
-----------------------------------------------
Nicolaas Francken [at] sunnysideup.co.nz


Requirements
-----------------------------------------------
see composer.json


Documentation
-----------------------------------------------
Please contact author for more details.

Any bug reports and/or feature requests will be
looked at in detail

We are also very happy to provide personalised support
for this module in exchange for a small donation.


Installation Instructions
-----------------------------------------------
1. Find out how to add modules to SS and add module as per usual.

2. Review configs and add entries to app/_config/config.yml
(or similar) as necessary.
In the _config/ folder of this module
you can usually find some examples of config options (if any).

3. add the following to your Page.ss file:
<head>
	$ExtendedMetaTags
</head>

4. add icons to /themes/mytheme/dist/favicons/, consider using:
   - http://www.favicon-generator.org/.
   - http://realfavicongenerator.net/
   - http://iconifier.net/
   - https://developer.chrome.com/multidevice/android/installtohomescreen

Note, you can add two variables:
ExtendedMetaTags($includeTitle = true, $addExtraSearchEngineData = true)
