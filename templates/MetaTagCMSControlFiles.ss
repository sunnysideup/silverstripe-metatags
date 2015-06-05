<% include MetaTagCMSControlHeader %>
<div id="MetatagOuterHolder">
	<form method="get" action="$FormAction" id="MetaTagCMSControlForm">
		<div class="response"><% if Message %>$Message<% else %>Improve Search Engine visibility: review and update File titles and related data. Careful! Any edits below will update your website immediately.<% end_if %></div>
		<div><input id="FieldName" name="fieldName" type="hidden"/></div>
	<% if MyRecords %>
		<table summary="update metatag overview" id="root">
			<thead>
				<tr>
					<th scope="col" class="filename" title="Review all the basic information about this image / file / folder. Click on the plus to go down one level and click on the min to go up one level.">Basic Info</th>
					<th scope="col" class="title" title="The title of this image, any title edit will also update the filename of the image / file / folder">Title</th>
					<th scope="col" class="content" title="If you want to add a bit more information about this image / file / folder" >Description</th>
				</tr>
			</thead>
			<tfoot>
				<tr class="actions">
					<th class="batchactionsDISABLED"><a href="#" title="Make changes to all pages at once.">Rules and Quick Fixes DISABLED</a></th>
					<th scope="col" class="title" title="The title of this image, any title edit will also update the filename of the image / file / folder">Title</th>
					<th scope="col" class="content" title="If you want to add a bit more information about this image / file / folder" >Description</th>
				</tr>


				<tr class="actions subsequentActions">

					<th class="quickFixesGeneral">
						Quick fixes
						<ul>
								<li><a href="{$Link}recyclefolder/$ParentID/" class="recycle ajaxify" rel="Title">recycle all un-used files in this folder</a></li>
								<li><a href="{$Link}upgradefilenames/$ParentID/" class="updatefilenames ajaxify" rel="Title">guess file titles for this folder, based on links to other objects</a></li>
								<li><a href="{$Link}cleanupfolders/" class="updatefilenames external" rel="Title">clean and straighten folders and files</a></li>
						</ul>
					</th>

					<td class="title">
						<ul>
							<li>ALL file titles to...
								<ul>
									<li><a href="{$Link}lowercase/Title/" class="lowercase ajaxify" rel="Title">lowercase</a></li>
									<li><a href="{$Link}titlecase/Title/" class="titlecase ajaxify" rel="Title">Title Case</a></li>

								</ul>
							</li>
						</ul>
					</td>

					<td class="content">
					</td>
				</tr>

			</tfoot>
			<tbody>
	<% include MetaTagCMSControlFilesBody %>
			</tbody>
		</table>
		<div class="response">$Message</div>
	<% else %>
		<p>No files found</p>
	<% end_if %>
	</form>
</div>
	<% include MetaTagCMSControlFooter %>

