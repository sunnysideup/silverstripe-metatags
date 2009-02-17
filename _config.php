<?php
/**
 * developed by www.sunnysideup.co.nz
 * author: Nicolaas modules [at] sunnysideup.co.nz*
 *
 * This Module allows you to add metatags easily...
 * add the following to your Page.ss file:
 * <head>
 *   <% base_tag %>
 *   $MetaTagsSunnySideUp
 * </head>
 * in page_controller->init()
 * add: $this->addRequirements() - this adds all the basic JS and CSS
 *
 **/

//DataObject::add_extension('SiteTree', 'MetaTagger');