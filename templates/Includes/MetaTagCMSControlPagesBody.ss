	<% loop MyRecords %>
	<tr class="$FirstLast $EvenOdd" id="TR-$ID">

		<td class="url">
			<% loop ParentSegments %>
				<% if Last %>
				<a href="/admin/show/$ID" title="Page Type: $ClassName - click to open in CMS" class="newWindow bold">$URLSegment/</a>
				<% else %>
				<a href="$Link" title="Page Type: $ClassName, Title: $Title  - click to open pages on this level" class="goOneUpLink" rel="TR-$ID">$URLSegment/</a>
				<% end_if %>
			<% end_loop %>
			<% if ChildrenLink %><a href="$ChildrenLink" class="goOneDownLink" title="go down one level and view child pages of: $MenuTitle.ATT" rel="TR-$ID">+</a><% end_if %>
		</td>

		<td class="title">
			<span class="highRes">
				<textarea type="text" id="Title_{$ID}" name="Title_{$ID}" rows="2" colspan="20">$Title</textarea>
			</span>
		</td>

		<td class="auto">
			<input type="checkbox" value="1" id="AutomateMetatags_{$ID}" name="AutomateMetatags_{$ID}"<% if AutomateMetatags %> checked="checked" <% end_if %> />
		</td>

		<td class="menu">
			<span class="<% if MenuTitleIdentical %>lowRes<% else %>highRes<% end_if %>">
				<textarea type="text" id="MenuTitle_{$ID}" name="MenuTitle_{$ID}"<% if MenuTitleAutoUpdate %>disabled="disabled"<% end_if %>  rows="2" colspan="20">$MenuTitle</textarea>
			</span>
		</td>

		<td class="metaT">
			<span class="<% if MetaTitleIdentical %>lowRes<% else %>highRes<% end_if %>">
				<textarea rows="2" cols="20" id="MetaTitle_{$ID}" name="MetaTitle_{$ID}"<% if MetaTitleAutoUpdate %> disabled="disabled"<% end_if %>>$MetaTitle.XML</textarea>
			</span>
		</td>

		<td class="metaD">
			<span>
				<textarea rows="2" cols="20" id="MetaDescription_{$ID}" name="MetaDescription_{$ID}"<% if MetaDescriptionAutoUpdate %> disabled="disabled"<% end_if %>>$MetaDescription.XML</textarea>
			</span>
		</td>

	</tr>
	<% end_loop %>
