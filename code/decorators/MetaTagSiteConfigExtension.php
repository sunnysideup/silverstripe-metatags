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
		$linkToManagerForPages = "/" . MetaTagCMSControlPages::get_url_segment() ."/";
		$linkToManagerForFiles = "/" . MetaTagCMSControlFiles::get_url_segment() ."/";
		$fields->addFieldsToTab("Root.SearchEngines",
			new TabSet("Options",
				new Tab("Menus",
					new LiteralField("MenuTitleExplanation", "<h3>Menu Title</h3><p>To improve consistency, you can set the menu title to automatically match the page title for any page on the site. </p>"),
					new CheckboxField("UpdateMenuTitle", "Automatically update the Menu Title to match the Page Title?")
				),
				new Tab("Meta Title",
					new LiteralField("MetaTitleExplanation", "<h3>&ldquo;Meta Title&rdquo;: Title for Search Engines and Tab/Window</h3><p>The Meta Title is the name of your page shown in the top of the browser.  This is an important indication to search engine about the content of the page.  It should be around seven words long.  Below you can add something to the front / end of every meta title (e.g. the name of your business).  Often this is done with some characters, e.g. two colons (::) or a hyphen ( - ) so that the full title reads something like: 'My Business Site :: Contact Us Page'</p>"),
					new CheckboxField("UpdateMetaTitle", "Automatically update every meta title to the same content as the page title?"),
					new TextField("PrependToMetaTitle", "Prepend (add in front) of Meta Title"),
					new TextField("AppendToMetaTitle", "Append (add at the end) of Meta Title")
				),
				new Tab("Meta Description",
					new LiteralField("MetaDescriptionExplanation", "<h3>&ldquo;Meta Description&rdquo;: Summary for Search Engines</h3><p>The Meta Description is not visible on the website itself. However, it is picked up by search engines like google.  They display it as the short blurb underneath the link to your pages. It will not get you much higher in the rankings, but it will entice people to click on your link.</p>"),
					new CheckboxField("UpdateMetaDescription", "Automatically update every meta description on every page (using the page content) - this only updates if there is no existing description?")
				),
				new Tab("Other Meta Data",
					new LiteralField("MetaOtherExplanation", "<h3>Other &ldquo;Meta Data&rdquo;: More hidden information about the page</h3><p>You can add some other <i>hidden</i> information to your pages - which can be picked up by Search Engines and other automated readers decyphering your website.</p>"),
					new TextField("MetaDataCountry", "Country"),
					new TextField("MetaDataCopyright", "Content Copyright"),
					new TextField("MetaDataDesign", "Design provided by ..."),
					new TextField("MetaDataCoding", "Website Coding carried out by ...")
				),
				new Tab("Pages",
					new LiteralField("ManageLinksForPages", "<iframe src=\"$linkToManagerForPages\" name=\"manage links for pages\" width=\"100%\" height=\"90%\">you browser does not support i-frames</iframe>"),
					new LiteralField("LinkToManagerHeaderForPages", "<p>Need more room? <a href=\"$linkToManagerForPages\" target=\"_blank\">Review and Edit</a> pages in a new window ...</p>")
				),
				new Tab("Files",
					new LiteralField("ManageLinksForFiles", "<iframe src=\"$linkToManagerForFiles\" name=\"manage links for files\" width=\"100%\" height=\"90%\">you browser does not support i-frames</iframe>"),
					new LiteralField("LinkToManagerHeaderForFiles", "<p>Need more room? <a href=\"$linkToManagerForFiles\" target=\"_blank\">Review and Edit</a> files in a new window ...</p>")
				),
				new Tab("Back Links",
					new LiteralField("MetaTagsLinksExplanation", "<h3>Referencing Websites</h3><p>A big part of Search Engine Optimisation is getting other sites to link to your site.  Below you can keep a record of these back links.</p>"),
					new ComplexTableField($controller = $this->owner, $name = "MetaTagsLinks", $sourceClass = "MetaTagsLinks")
				)
			)
		);
		return $fields;
	}
}
