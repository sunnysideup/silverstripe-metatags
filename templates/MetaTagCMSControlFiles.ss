<% include MetaTagCMSControlHeader %>
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
				<th class="bactchactions"><a href="#" title="Make changes to all pages at once.">Rules and Quick Fixes</a></th>
				<th scope="col" class="title" title="The title of this image, any title edit will also update the filename of the image / file / folder">Title</th>
				<th scope="col" class="content" title="If you want to add a bit more information about this image / file / folder" >Description</th>
			</tr>


			<tr class="actions subsequentActions">

				<th class="bactchactions">Quick fixes:</th>

				<td class="title">
					<ul>
						<li>file titles to...
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
	<p>No pages found</p>
<% end_if %>
</form>

<% include MetaTagCMSControlFooter %>
