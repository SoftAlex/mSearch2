<div class="row" id="mse2_mfilter">
	<div class="span3">
		<form action="" method="post" id="mse2_filters">
			[[+filters]]
		</form>

		<div>[[%mse2_filter_total]] <span id="mse2_total">[[+total:default=`0`]]</span></div>
	</div>

	<div class="span9">
		<div id="mse2_sort">
			<a href="#" data-sort="resource|publishedon:desc" class="[[+resource|publishedon:desc]]">[[%mse2_sort_publishedon]], [[%mse2_sort_desc]]</a> /
			<a href="#" data-sort="resource|publishedon:asc" class="[[+resource|publishedon:asc]]">[[%mse2_sort_publishedon]], [[%mse2_sort_asc]]</a>
		</div>

		<div id="mse2_results">
			[[+results]]
		</div>

		<div class="pagination">
			<ul id="mse2_pagination">
				[[!+page.nav]]
			</ul>
		</div>

	</div>
</div>