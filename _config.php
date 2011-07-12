<?php
/**
 * developed by www.sunnysideup.co.nz
 * authors:
 * martijn: marvanni [at] hotmail.com
 * Nicolaas modules [at] sunnysideup.co.nz
 **/

Director::addRules(8, array(
	MetatagCMSControlPages::get_url_segment().'//$Action/$ID/$OtherID' => 'MetatagCMSControlPages',
	MetatagCMSControlFiles::get_url_segment().'//$Action/$ID/$OtherID' => 'MetatagCMSControlFiles'
));
define('SS_METATAG_DIR', 'metatags');


//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START metatags MODULE ----------------===================
//dont forget to add $this->addBasicMetatagRequirements() to Page_Controller->init(); and add this to your theme: $ExtendedMetatags
//Object::add_extension('SiteConfig', 'MetaTagSiteConfigExtension');
//Object::add_extension('SiteTree', 'MetaTagAutomation');
//Object::add_extension('ContentController', 'MetaTagAutomation_controller');
/* pop-ups and form interaction */
//MetaTagAutomation::set_disable_update_popup(false);
/* meta descriptions */
//MetaTagAutomation::set_meta_desc_length(12);
/* meta keywords */
//MetaTagAutomation::set_hide_keywords_altogether(true);
//FONTS - see google fonts for options, include within CSS file as: body {font-family: Inconsolata;}
//MetaTagAutomation::add_google_font("Inconsolata");
/* combined files */
//MetaTagAutomation_controller::set_folder_for_combined_files("cache");
//MetaTagAutomation_controller::set_combine_css_files_into_one(true);
//MetaTagAutomation_controller::set_combine_js_files_into_one(true);
/* favicons */
//MetaTagAutomation::set_use_themed_favicon(true);
//===================---------------- END metatags MODULE ----------------===================

