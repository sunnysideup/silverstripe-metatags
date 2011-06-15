<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Edit Search Engine Data</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
	<h1>Manage your page title and related titles to improve Search Engine visibility ...</h1>
<form method="get" action="$FormAction" id="MetaTagCMSControlForm">
	<div class="response"><% if Message %>$Message<% else %>Move your mouse over the links and headers for more information ... Any edits below will update your website immediately.<% end_if %></div>
	<div><input id="FieldName" name="fieldName" type="hidden"/></div>
<% if MyPages %>
	<table summary="update metatag overview" id="root">
		<thead>
			<tr>
				<th scope="col" class="url" title="This is the web address used for the page - it is setup to automatically match the Title of the Page.">URL</th>
				<th scope="col" class="title" title="This is the key field - start by getting the Page Titles right and the rest will follow.">Title</th>
				<th scope="col" class="auto" title="Update Meta and Menu Titles and Descriptions automatically?"><img src="/metatags/images/linked_horizontal_flip.png" alt="linked" title="This image identifies which fields are linked to the Page Title." class="right" />Auto-update</th>
				<th scope="col" class="menu" title="A shorter version of the Page Title, used in menus with limited room."><% if AlwaysUpdateMenuDescription %><img src="/metatags/images/linked.png" alt="linked" title="Menu Title automatically updates to Page Title (linked)" /><% end_if %>Menu Title</th>
				<th scope="col" class="metaT" title="The Page Title as it is 'seen' by search engines."><% if AlwaysUpdateMetaTitle %><img src="/metatags/images/linked.png" alt="linked" title="Meta  Title automatically updates to Page Title (linked)" /><% end_if %>Search Engine Title</th>
				<th scope="col" class="metaD" title="The Page Description as shown on Search Engine result pages."><% if AlwaysUpdateMetaDescription %><img src="/metatags/images/linked.png" alt="linked" title="Meta Description uses page content if no content has been entered" /><% end_if %>Search Engine Description</th>
			</tr>
		</thead>
		<tfoot>
			<tr class="actions">
				
				<th class="bactchactions"><a href="#" title="Make changes to all pages at once.">Batch Actions</a></th>

				<td class="title">
					<ul>
						<li>Convert all titles to...
							<ul>
								<li><a href="{$Link}lowercase/Title/" class="lowercase" rel="Title">lowercase</a></li>
								<li><a href="{$Link}titlecase/Title/" class="titlecase" rel="Title">Title Case</a></li>
							</ul>
						</li>
					</ul>
				</td>
				
				<td class="auto">
					<ul>
						<li>Automatically update pages ...
							<ul>
								<li><a href="{$Link}setpageflag/AutomateMetatags/1/" class="setpageflag" rel="AutomateMetatags">auto-update all</a></li>
								<li><a href="{$Link}setpageflag/AutomateMetatags/0/" class="setpageflag" rel="AutomateMetatags">auto-update none</a></li>
							</ul>
						</li>
					</ul>
				</td>
				
				<td class="menu">
					<ul>
						<li>Convert all menu titles to ...
							<ul>
								<li><a href="{$Link}lowercase/MenuTitle/" class="lowercase" rel="MenuTitle">lowercase</a></li>
								<li><a href="{$Link}titlecase/MenuTitle/" class="titlecase" rel="MenuTitle">Title Case</a></li>
							</ul>
						</li>
						<li>
							Match with title
							<% if AlwaysUpdateMenuTitle %>(currently updating automatically)<% else %>(currently not updating automatically)<% end_if %>...
							<ul>
								<li><a href="{$Link}copyfromtitle/MenuTitle/" class="copyfromtitle" rel="MenuTitle">now</a></li>
								<li>
									<% if AlwaysUpdateMenuTitle %>
										<a href="{$Link}togglecopyfromtitle/UpdateMenuTitle/" class="togglecopyfromtitle" rel="MenuTitle">cancel automatic update</a>
									<% else %>
										<a href="{$Link}togglecopyfromtitle/UpdateMenuTitle/" class="togglecopyfromtitle" rel="MenuTitle">turn on automatic update</a>
									<% end_if %>
								</li>
							</ul>
						</li>
					</ul>
				</td>

				<td class="metaT">
					<ul>
						<li>Convert all meta titles to...
							<ul>
								<li><a href="{$Link}lowercase/MetaTitle/" class="lowercase" rel="MetaTitle">lowercase</a></li>
								<li><a href="{$Link}titlecase/MetaTitle/" class="titlecase" rel="MetaTitle">Title Case</a></li>
							</ul>
						</li>
						<li>Match with title ...
							<% if AlwaysUpdateMetaTitle %>(currently updating automatically)<% else %>(currently not updating automatically)<% end_if %>...						
							<ul>
								<li><a href="{$Link}copyfromtitle/MetaTitle/" class="copyfromtitle" rel="MetaTitle">do it now</a></li>
								<li>
									<% if AlwaysUpdateMetaTitle %>
										<a href="{$Link}togglecopyfromtitle/UpdateMetaTitle/" class="togglecopyfromtitle" rel="MetaTitle">cancel automatic update</a>
									<% else %>
										<a href="{$Link}togglecopyfromtitle/UpdateMetaTitle/" class="togglecopyfromtitle" rel="MetaTitle">turn on automaticalupdate</a>
									<% end_if %>
								</li>
							</ul>
						</li>
					</ul>
				</td>

				<td class="metaD">
					<ul>
						<li>Use page content
							<% if AlwaysUpdateMetaDescription %>
							(currently updating automatically)
							<% else %>
							(currently not updating automatically)
							<% end_if %>
							...
							<ul>
								<li><a href="{$Link}copyfromcontent/MetaDescription/" class="copyfromcontent" rel="MetaDescription">do it now</a></li>
								<li>
									<% if AlwaysUpdateMetaDescription %>
										<a href="{$Link}togglecopyfromtitle/UpdateMetaDescription/" class="togglecopyfromtitle" rel="MetaDescription">cancel automatical update </a>
									<% else %>
										<a href="{$Link}togglecopyfromtitle/UpdateMetaDescription/" class="togglecopyfromtitle" rel="MetaDescription">turn on automatically update</a>
									<% end_if %>
								</li>
							</ul>
						</li>
					</ul>
				</td>
			</tr>
		</tfoot>
		
<% include MetaTagCMSControlBody %>

	</table>
	<div class="response">$Message</div>
<% else %>
	<p>No pages found</p>
<% end_if %>
</form>
</body>
</html>
