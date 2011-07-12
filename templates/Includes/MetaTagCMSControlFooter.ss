<% if MyRecords %>
	<% if MyRecords.MoreThanOnePage %>
	<p class="pageNumbers">
	<% if MyRecords.PrevLink %>
	<a href="/$MyRecords.PrevLink"><< Prev</a> |
	<% end_if %>

	<% control MyRecords.Pages %>
	<% if CurrentBool %>
	<strong>$PageNum</strong>
	<% else %>
	<a href="/$Link" title="Go to page $PageNum">$PageNum</a>
	<% end_if %>
	<% end_control %>

	<% if MyRecords.NextLink %>
	| <a href="/$MyRecords.NextLink">Next >></a>
	<% end_if %>
	</p>
	<% end_if %>
<% end_if %>
</body>
</html>
