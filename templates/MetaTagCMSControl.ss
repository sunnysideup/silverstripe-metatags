<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Edit Search Engine Data</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<% if GoOneUpLink %><p><a href="$GoOneUpLink" class="goOneUpLink">..</a></p><% end_if %>
<form method="get" action="$FormAction" id="MetaTagCMSControlForm">
	<div class="response"></div>
	<div><input id="fieldName" name="fieldName" type="hidden"/></div>
<% if MyPages %>
	<ul id="root">
		<% control MyPages %>
		<li>
			<% if ChildrenLink %><span class="children"><a href="$ChildrenLink">+</a></span><% end_if %>
			<a href="$Link">$Title</a>
			<ul>
				<li><label for="URLSegment_{$ID}">URL: $Parent.Link</label>
					<span class="highRes">
						<input type="text" value="$URLSegment" id="URLSegment_{$ID}" name="URLSegment_{$ID}"/>
					</span>
				</li>
				<li><label for="Title_{$ID}">Title:</label>
					<span class="highRes">
						<input type="text" value="$Title" id="Title_{$ID}" name="Title_{$ID}"/>
					</span>
				</li>
				<li><label for="MetaTitle_{$ID}">Meta:</label>
					<span class="<% if MetaTitleIdentical %>lowRes<% else %>highRes<% end_if %>">
						<input type="text" value="$MetaTitle" id="MetaTitle_{$ID}" name="MetaTitle_{$ID}"/>
					</span>
				</li>
				<li><label for="MenuTitle_{$ID}">Menu:</label>
					<span class="<% if MenuTitleIdentical %>lowRes<% else %>highRes<% end_if %>">
						<input type="text" value="$MenuTitle" id="MenuTitle_{$ID}" name="MenuTitle_{$ID}"/>
					</span>
				</li>
				
			</ul>
		</li>
		<% end_control %>
	</ul>
	<div class="response"></div>
<% else %>
	<p>No pages found</p>
<% end_if %>
</form>
</body>
</html>
