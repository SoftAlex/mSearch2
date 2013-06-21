mSearch2 = {
	options: {
		filters: '#mse2_filters'
		,results: '#mse2_results'
		,total: '#mse2_total'
		,pagination: '#mse2_pagination'
		,sort: '#mse2_sort'

		,pagination_link: '#mse2_pagination a'
		,sort_link: '#mse2_sort a'
		,active_class: 'active'
		,disabled_class: 'disabled'

		,delimeter: '/'
		,prefix: 'mse2_'
		,suggestion: 'sup' // inside filter item, e.g. #mse2_filters
	}
	,initialize: function(selector) {
		var elements = ['filters','results','pagination','total','sort'];
		for (i in elements) {
			var elem = elements[i];
			this[elem] = $(selector).find(this.options[elem]);
			if (!this[elem].length) {
				//console.log('Error: could not initialize element "' + elem + '" with selector "' + this.options[elem] + '".');
			}
		}

		this.handlePagination();
		this.handleSort();

		$(document).on('submit', this.options.filters, function(e) {
			mSearch2Config.page = '';
			mSearch2.load();
			return false;
		});

		$(document).on('change', this.options.filters, function(e) {
			return $(this).submit();
		});

		mSearch2.setTotal(this.total.text());
		return true;
	}


	,handlePagination: function() {
		$(document).on('click', this.options.pagination_link, function(e) {
			if (!$(this).hasClass(mSearch2.options.active_class)) {
				$(mSearch2.options.pagination).removeClass(mSearch2.options.active_class);
				$(this).addClass(mSearch2.options.active_class);

				var tmp = $(this).prop('href').match(/page=(\d+)/);
				var page = tmp && tmp[1] ? Number(tmp[1]) : 1;
				mSearch2Config.page = (page != mSearch2Config.start_page) ? page : '';

				mSearch2.load();
			}

			return false;
		});
	}

	,handleSort: function() {
		$(document).on('click', this.options.sort_link, function(e) {
			if (!$(this).hasClass(mSearch2.options.active_class)) {
				$(mSearch2.options.sort_link).removeClass(mSearch2.options.active_class);
				$(this).addClass(mSearch2.options.active_class);

				var sort = $(this).data('sort');
				mSearch2Config.sort = (sort != mSearch2Config.start_sort) ? sort : '';

				mSearch2.load();
			}

			return false;
		});
	}

	,load: function() {
		var params = this.getFilters();
		if (mSearch2Config.query != '') {
			params.query = mSearch2Config.query;
		}
		if (mSearch2Config.sort != '') {
			params.sort = mSearch2Config.sort;
		}
		if (mSearch2Config.page > 0) {
			params.page = mSearch2Config.page;
		}
		this.Hash.set(params);

		params.action = 'filter';
		params.pageId = mSearch2Config.pageId;

		this.beforeLoad();
		$.post(mSearch2Config.actionUrl, params, function(response) {
			mSearch2.afterLoad();
			if (response.success) {
				mSearch2.Message.success(response.message);
				mSearch2.results.html(response.data.results);
				mSearch2.pagination.html(response.data.pagination);
				mSearch2.setTotal(response.data.total);
				mSearch2.setSuggestions(response.data.suggestions);
				if (response.data.log) {
					$('.mFilterLog').html(response.data.log);
				}
			}
			else {
				mSearch2.Message.error(response.message);
			}
		}, 'json');
	}

	,getFilters: function() {
		var data = {};

		$.map(this.filters.serializeArray(), function(n, i) {
			if (data[n['name']]) {
				data[n['name']] += ',' + n['value'];
			}
			else {
				data[n['name']] = n['value'];
			}
		});

		return data;
	}

	,setSuggestions: function(suggestions) {
		for (filter in suggestions) {
			if (suggestions.hasOwnProperty(filter)) {
				var arr = suggestions[filter];
				for (value in arr) {
					if (arr.hasOwnProperty(value)) {
						var count = arr[value];
						var selector = filter.replace(mSearch2Config.delimeter, "\\" + mSearch2Config.delimeter);
						var input = $('#' + mSearch2.options.prefix + selector, mSearch2.filters).find('[value="' + value + '"]');

						if (input.prop('type') != 'checkbox') {continue;}

						var label = $('#' + mSearch2.options.prefix + selector, mSearch2.filters).find('label[for="' + input.prop('id') + '"]');
						var elem = input.parent().find(mSearch2.options.suggestion);
						elem.text(count);

						if (count == 0) {
							input.prop('disabled', true);
							label.addClass(mSearch2.options.disabled_class);
						}
						else {
							input.prop('disabled', false);
							label.removeClass(mSearch2.options.disabled_class);
						}
						if (input.is(':checked')) {elem.hide();}
						else {elem.show();}
					}
				}
			}
		}
	}

	,setTotal: function(total) {
		if (!total || total == 0) {
			this.total.parent().hide();
			this.sort.hide();
			this.total.text(0);
		}
		else {
			this.total.parent().show();
			this.sort.show();
			this.total.text(total);
		}
	}

	,beforeLoad: function() {
		this.results.css('opacity', .5);
		$(this.options.pagination_link).addClass(this.options.active_class);
		this.filters.find('input, select').prop('disabled', true).addClass(this.options.disabled_class);
	}

	,afterLoad: function() {
		this.results.css('opacity', 1);
		this.filters.find('.' + this.options.disabled_class).prop('disabled', false).removeClass(this.options.disabled_class);
	}

};

mSearch2.Message = {
	success: function(message) {

	}
	,error: function(message) {
		alert(message);
	}
};

mSearch2.Hash = {
	get: function() {
		var vars = {}, hash;
		var pos = window.location.href.indexOf('?');
		if (pos < 0) {return vars;}

		var hashes = decodeURIComponent(window.location.href.substr(pos + 1));
		if (hashes.length == 0) {return vars;}
		else {hashes = hashes.split('&');}

		for (var i in hashes) {
			if (hashes.hasOwnProperty(i)) {
				hash = hashes[i].split('=');
				if (typeof hash[1] == 'undefined') {
					vars['anchor'] = hash[0];
				}
				else {
					vars[hash[0]] = hash[1];
				}
			}
		}
		return vars;
	}
	,set: function(vars) {
		var hash = '';
		for (var i in vars) {
			if (vars.hasOwnProperty(i)) {
				hash += '&' + i + '=' + vars[i];
			}
		}
		window.history.pushState(hash, '', document.location.pathname + '?' + hash.substr(1));
	}
	,add: function(key, val) {
		var hash = this.get();
		hash[key] = val;
		this.set(hash);
	}
	,remove: function(key) {
		var hash = this.get();
		delete hash[key];
		this.set(hash);
	}
	,clear: function() {
		window.location.hash = '';
	}
};

mSearch2.initialize('#mse2_mfilter');