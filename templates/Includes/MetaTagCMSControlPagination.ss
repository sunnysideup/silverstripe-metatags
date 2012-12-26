<% if MyRecords %>
	<% if MyRecords.MoreThanOnePage %>
	<p class="pageNumbers">
	<% if MyRecords.PrevLink %>
	<a href="/$MyRecords.PrevLink"><< Prev</a> |
	<% end_if %>

	<% loop MyRecords.Pages %>
	<% if CurrentBool %>
	<strong>$PageNum</strong>
	<% else %>
	<a href="/$Link" title="Go to page $PageNum" class="">$PageNum</a>
	<% end_if %>
	<% end_loop %>

	<% if MyRecords.NextLink %>
	| <a href="/$MyRecords.NextLink">Next >></a>
	<% end_if %>
	</p>
	<% end_if %>
<% end_if %>


