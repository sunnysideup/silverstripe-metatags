<?php
/**
 * developed by www.sunnysideup.co.nz
 * authors:
 * martijn: marvanni [at] hotmail.com
 * Nicolaas modules [at] sunnysideup.co.nz
 * dont
 **/

//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START metatags MODULE ----------------===================
//dont forget to add $this->addBasicMetatagRequirements to Page_Controller->init(); and add this to your theme: $ExtendedMetatags
//Object::add_extension('SiteTree', 'MetaTagAutomation');
//Object::add_extension('ContentController', 'MetaTagAutomation_controller');
/* pop-ups and form interaction */
//MetaTagAutomation::set_disable_update_popup(0);
/* default value for auto-update pages' metatags */
//MetaTagAutomation::set_default_state_auto_update_checkbox(0);
/* meta-title */
//MetaTagAutomation::set_update_meta_title(0);
//MetaTagAutomation::set_prepend_to_meta_title("");
//MetaTagAutomation::set_append_to_meta_title("");
/* meta descriptions */
//MetaTagAutomation::set_update_meta_desc(0);
//MetaTagAutomation::set_meta_desc_length(12);
/* meta keywords */
//MetaTagAutomation::set_update_meta_keys(0);
//MetaTagAutomation::set_number_of_keywords(12);
//MetaTagAutomation::set_min_word_char(3);
//MetaTagAutomation::set_exclude_words("the,and,from");
/* additional metatag information */
//MetaTagAutomation_controller::set_country("New Zealand");
//MetaTagAutomation_controller::set_copyright("owner");
//MetaTagAutomation_controller::set_design("owner");
//MetaTagAutomation_controller::set_coding("owner");
/* combined files */
//MetaTagAutomation_controller::set_folder_for_combined_files("assets");
//MetaTagAutomation_controller::set_combine_css_files_into_one(0);
//MetaTagAutomation_controller::set_combine_js_files_into_one(0);
//===================---------------- END metatags MODULE ----------------===================

//===================---------------- START OLD metatags MODULE ----------------===================
//Object::add_extension('SiteTree', 'MetaTagger');
//Object::add_extension('ContentController', 'MetaTagger_Controller');
//MetaTagger::$country = "New Zealand";
//MetaTagger::$copyright = 'owner';
//MetaTagger::$design = '';
//MetaTagger::$project = 'mysite';
//MetaTagger::$coding = "";
//===================---------------- END OLD metatags MODULE ----------------===================
