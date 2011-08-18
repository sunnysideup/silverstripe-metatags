<tbody>
	<% control MyRecords %>
	<tr class="$FirstLast $EvenOdd" id="TR-$ID">

		<td class="filename">
			<% control ParentSegments %>
				<% if Last %>
				<strong>$URLSegment</strong>
				<% else %>
				<a href="$Link" title="File Type: $ClassName, Title: $Title  - click to open pages on this level" class="goOneUpLink" rel="TR-$ID">$FilenameSegment/</a>
				<% end_if %>
			<% end_control %>
			<% if ClassName = Folder %>
				<% if ChildrenLink %><a href="$ChildrenLink" class="goOneDownLink" title="go down one level and view child pages of: $Name.ATT" rel="TR-$ID">+</a><% end_if %>
				<div class="iconHolder"><img src="/metatags/images/Folder.png" alt="$ClassName" class="defaultIcon" /></div>
			<% else %>
				<% if CMSThumbnail %>
					<div class="iconHolder"><a href="$Link">$CMSThumbnail</a></div>
				<% end_if %>
				<div class="fileInfo">
					<% if getFileType %><br /><span class="label">Type:</span> <span class="data">$getFileType</span><% end_if %>
					<% if getSize %><br /><span class="label">Size:</span> <span class="data">$getSize</span><% end_if %>
					<% if getDimensions %><br /><span class="label">Dimensions:</span class="data"> <span>$getDimensions</span><% end_if %>
				</div>
			<% end_if %>
			
		</td>

		<td class="title">
			<span class="highRes">
				<textarea type="text" id="Title_{$ID}" name="Title_{$ID}" rows="2" colspan="20">$Title</textarea>
			</span>
		</td>

		<td class="content">
			<span>
				<textarea rows="2" cols="20" id="Content_{$ID}" name="Content_{$ID}">$Content</textarea>
			</span>
		</td>

	</tr>
	<% end_control %>
</tbody>
