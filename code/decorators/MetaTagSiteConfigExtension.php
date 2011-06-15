<?php


class MetaTagSiteConfigExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array (
			'db' => array(
				//meta title embelishments
				'PrependToMetaTitle' => 'Varchar(60)',
				'AppendToMetaTitle' => 'Varchar(60)',
				//other meta data
				'MetaDataCountry' => 'Varchar(60)',
				'MetaDataCopyright' => 'Varchar(60)',
				'MetaDataDesign' => 'Varchar(60)',
				'MetaDataCoding' => 'Varchar(60)',
				// flags
				'UpdateMetaTitle' => 'Boolean',
				'UpdateMenuTitle' => 'Boolean',
				'UpdateMetaDescription' => 'Boolean'
			)
		);
	}

	function updateCMSFields(FieldSet &$fields) {
		$linkToManager = "/" . MetaTagCMSControl::get_url_segment() ."/";
		$fields->addFieldsToTab("Root.SearchEngines", array(
			new CheckboxField("UpdateMenuTitle", "Automatically update every Menu Title to match the Page Title?"),
			new LiteralField("MetaTitleExplanation", "<h3>Meta Title</h3><p>The Meta Title is the name of your page shown in the top of the browser.  This is an important indication to search engine about the content of the page.  It should be around seven words long.  Below you can add something to the front / end of every meta title (e.g. the name of your business).  Often this is done with some characters, e.g. two colons (::) or a hyphen ( - ) so that the full title reads something like: 'My Business Site :: Contact Us Page'</p>"),
			new CheckboxField("UpdateMetaTitle", "Automatically update every meta title to the same content as the page title?"),
			new TextField("PrependToMetaTitle", "Prepend (add in front) of Meta Title"),
			new TextField("AppendToMetaTitle", "Append (add at the end) of Meta Title"),
			new LiteralField("MetaDescriptionExplanation", "<h3>Meta Description</h3><p>The Meta Description is not visible on the website itself. However, it is picked up by search engines like google.  They display it as the short blurb underneath the link to your pages. It will not get you much higher in the rankings, but it will entice people to click on your link.</p>"),
			new CheckboxField("UpdateMetaDescription", "Automatically update every meta description on every page (using the page content) - this only updates if there is no existing description?"),
			new LiteralField("MetaOtherExplanation", "<h3>Other Meta Data</h3><p>You can add some other <i>hidden</i> information to your pages - which can be picked up by Search Engines and other automated readers decyphering your website.</p>"),
			new TextField("MetaCountry", "Country"),
			new TextField("MetaCopyright", "Content Copyright"),
			new TextField("MetaDesign", "Design provided by ..."),
			new TextField("MetaCoding", "Website Coding carried out by ..."),
			new LiteralField("LinkToManagerHeader", "<h3><a href=\"$linkToManager\" target=\"_blank\">Review and Edit Search Engine Data</a></h3>")
		));
		return $fields;
	}
}
