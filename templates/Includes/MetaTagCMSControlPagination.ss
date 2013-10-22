<% if MyPaginatedRecords %>
	<% if MyPaginatedRecords.MoreThanOnePage %>
	<p class="pageNumbers">
	<% if MyPaginatedRecords.PrevLink %>
	<a href="$MyPaginatedRecords.PrevLink"><< Prev</a>
	<% end_if %>

	<% loop MyPaginatedRecords.Pages %>
	<% if CurrentBool %>
	<strong>$PageNum</strong>
	<% else %>
	<a href="/$Link" title="Go to page $PageNum" class="">$PageNum</a>
	<% end_if %>
	<% end_loop %>

	<% if MyPaginatedRecords.NextLink %>
	 <a href="$MyPaginatedRecords.NextLink">Next >></a>
	<% end_if %>
	</p>
	<% end_if %>
<% end_if %>


