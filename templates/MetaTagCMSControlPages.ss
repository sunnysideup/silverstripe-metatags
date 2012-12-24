<% include MetaTagCMSControlHeader %>
<div id="MetatagOuterHolder">
	<form method="get" action="$FormAction" id="MetaTagCMSControlForm">
		<div class="response"><% if Message %>$Message<% else %>Improve Search Engine visibility: review and update Page titles and related data. Careful! Any edits below will update your website immediately.<% end_if %></div>
		<div><input id="FieldName" name="fieldName" type="hidden"/></div>
	<% if MyRecords %>
		<table summary="update metatag overview" id="root">
			<thead>
				<tr>
					<th scope="col" class="url" title="This is the web address used for the page - it is setup to automatically match the Title of the Page.">URL</th>
					<th scope="col" class="title" title="This is the key field - start by getting the Page Titles right and the rest will follow.">Page Title</th>
					<th scope="col" class="auto" title="Update Meta and Menu Titles and Descriptions automatically?"><img src="/metatags/images/linked_horizontal_flip.png" alt="linked" title="This image identifies which fields are linked to the Page Title." class="right" />Apply Rules?</th>
					<th scope="col" class="menu" title="A shorter version of the Page Title, used in menus with limited room."><% if AlwaysUpdateMenuTitle %><img src="/metatags/images/linked.png" alt="linked" title="Menu Title automatically updates to Page Title (linked)" /><% end_if %>Menu Title</th>
					<th scope="col" class="metaT" title="The Page Title as it is 'seen' by search engines. It should be around seven words long and the words should reflect the keywords on the page."><% if AlwaysUpdateMetaTitle %><img src="/metatags/images/linked.png" alt="linked" title="Meta Title (or search engine title) automatically updates to Page Title (linked)" /><% end_if %>Meta Title</th>
					<th scope="col" class="metaD" title="The Page Description as shown on Search Engine result pages.  This should be one to three sentences describing the page.  Keep it simple and avoid things like: 'on this page'.  Example: All the contact details for the "><% if AlwaysUpdateMetaDescription %><img src="/metatags/images/linked.png" alt="linked" title="Meta Description (or search engine description) uses page content if no content has been entered" /><% end_if %>Meta Description</th>
				</tr>
			</thead>
			<tfoot>
				<tr class="actions">

					<th class="bactchactions"><a href="#" title="Make changes to all pages at once.">Rules and Quick Fixes</a></th>
					<th scope="col" class="title" title="This is the key field - start by getting the Page Titles right and the rest will follow.">Page Title</th>
					<th scope="col" class="auto" title="Update Meta and Menu Titles and Descriptions automatically?"><img src="/metatags/images/linked_horizontal_flip.png" alt="linked" title="This image identifies which fields are linked to the Page Title." class="right" />Apply rules?</th>
					<th scope="col" class="menu" title="A shorter version of the Page Title, used in menus with limited room."><% if AlwaysUpdateMenuDescription %><img src="/metatags/images/linked.png" alt="linked" title="Menu Title automatically updates to Page Title (linked)" /><% end_if %>Menu Title</th>
					<th scope="col" class="metaT" title="The Page Title as it is 'seen' by search engines."><% if AlwaysUpdateMetaTitle %><img src="/metatags/images/linked.png" alt="linked" title="Meta  Title automatically updates to Page Title (linked)" /><% end_if %>Search Engine Title</th>
					<th scope="col" class="metaD" title="The Page Description as shown on Search Engine result pages."><% if AlwaysUpdateMetaDescription %><img src="/metatags/images/linked.png" alt="linked" title="Meta Description uses page content if no content has been entered" /><% end_if %>Search Engine Description</th>
				</tr>


				<tr class="actions subsequentActions">

					<th class="bactchactions">setup rules:</th>

					<td class="title"></td>

					<td class="auto"></td>

					<td class="menu">
						<ul>
							<li>
								<% if AlwaysUpdateMenuTitle %>
									<a href="{$Link}togglecopyfromtitle/UpdateMenuTitle/" class="togglecopyfromtitle ajaxify" rel="MenuTitle"><input type="checkbox" value="1" checked="checked" /></a>
								<% else %>
									<a href="{$Link}togglecopyfromtitle/UpdateMenuTitle/" class="togglecopyfromtitle ajaxify" rel="MenuTitle"><input type="checkbox" value="0" /></a>
								<% end_if %>
								Always copy from page title? <br />
								<% if AlwaysUpdateMenuTitle %>
									<i>(currently the menu title automatically copies from the page title)</i>
								<% else %>
									<i>(currently the menu title does not automatically copy from the page title)</i>
								<% end_if %>...
							</li>
						</ul>
					</td>

					<td class="metaT">
						<ul>
							<li>
								<% if AlwaysUpdateMetaTitle %>
									<a href="{$Link}togglecopyfromtitle/UpdateMetaTitle/" class="togglecopyfromtitle ajaxify" rel="MetaTitle"><input type="checkbox" value="1" checked="checked" /></a>
								<% else %>
									<a href="{$Link}togglecopyfromtitle/UpdateMetaTitle/" class="togglecopyfromtitle ajaxify" rel="MetaTitle"><input type="checkbox" value="1" /></a>
								<% end_if %>
								Always copy from page title? <br />
								<% if AlwaysUpdateMetaTitle %>
									(<i>currently the meta title is automatically copied from the page title)</i>
								<% else %>
									<i>(currently the meta title is not automatically copied from the page title)</i>
								<% end_if %>...
							</li>
						</ul>
					</td>

					<td class="metaD">
						<ul>
							<li>
								<% if AlwaysUpdateMetaDescription %>
										<a href="{$Link}togglecopyfromtitle/UpdateMetaDescription/" class="togglecopyfromtitle ajaxify" rel="MetaDescription"><input type="checkbox" value="1" checked="checked" /></a>
									<% else %>
										<a href="{$Link}togglecopyfromtitle/UpdateMetaDescription/" class="togglecopyfromtitle ajaxify" rel="MetaDescription"><input type="checkbox" value="1" /></a>
									<% end_if %>
								Always copy from Page Content? <br />
								<% if AlwaysUpdateMetaDescription %>
								<i>(currently the meta description is copied automatically from the Content of the page)</i>
								<% else %>
								<i>(currently the meta description is NOT copied automatically from the page content)</i>
								<% end_if %>
								...
							</li>
						</ul>
					</td>
				</tr>


				<tr class="actions subsequentActions">

					<th class="bactchactions">Quick fixes:</th>

					<td class="title">
						<ul>
							<li>page titles to...
								<ul>
									<li><a href="{$Link}lowercase/Title/" class="lowercase ajaxify" rel="Title">lowercase</a></li>
									<li><a href="{$Link}titlecase/Title/" class="titlecase ajaxify" rel="Title">Title Case</a></li>
								</ul>
							</li>
						</ul>
					</td>

					<td class="auto">
						<ul>
							<li>follow rules?
								<ul>
									<li><a href="{$Link}setpageflag/AutomateMetatags/1/" class="setpageflag ajaxify" rel="AutomateMetatags">all pages</a></li>
									<li><a href="{$Link}setpageflag/AutomateMetatags/0/" class="setpageflag ajaxify" rel="AutomateMetatags">none of the pages</a></li>
								</ul>
							</li>
						</ul>
					</td>

					<td class="menu">
						<ul>
							<li>menu titles to ...
								<ul>
									<li><a href="{$Link}lowercase/MenuTitle/" class="lowercase ajaxify" rel="MenuTitle">lowercase</a></li>
									<li><a href="{$Link}titlecase/MenuTitle/" class="titlecase ajaxify" rel="MenuTitle">Title Case</a></li>
									<li><a href="{$Link}copyfromtitle/MenuTitle/" class="copyfromtitle ajaxify" rel="MenuTitle">match the page title</a></li>
								</ul>
							</li>
						</ul>
					</td>

					<td class="metaT">
						<ul>
							<li>meta titles to...
								<ul>
									<li><a href="{$Link}lowercase/MetaTitle/" class="lowercase ajaxify" rel="MetaTitle">lowercase</a></li>
									<li><a href="{$Link}titlecase/MetaTitle/" class="titlecase ajaxify" rel="MetaTitle">Title Case</a></li>
									<li><a href="{$Link}copyfromtitle/MetaTitle/" class="copyfromtitle ajaxify" rel="MetaTitle">match the page title</a></li>
								</ul>
							</li>
						</ul>
					</td>

					<td class="metaD">
						<ul>
							<li>meta description to ...
								<ul>
									<li><a href="{$Link}copyfromcontent/MetaDescription/" class="copyfromcontent ajaxify" rel="MetaDescription">match the page content</a></li>
								</ul>
							</li>
						</ul>
					</td>
				</tr>

			</tfoot>
			<tbody>
	<% include MetaTagCMSControlPagesBody %>
			</tbody>
		</table>
		<div class="response">$Message</div>
	<% else %>
		<p>No pages found</p>
	<% end_if %>
	</form>
	<% include MetaTagCMSControlFooter %>
</div>
