mSearch2.grid.Search = function(config) {
	config = config || {};

	this.exp = new Ext.grid.RowExpander({
		expandOnDblClick: true
		,tpl : new Ext.Template('<p>{intro}</p>')
		,renderer : function(v, p, record) {return record.data.intro != '' && record.data.intro != null ? '<div class="x-grid3-row-expander">&#160;</div>' : '&#160;';}
	});

	Ext.applyIf(config,{
		id: 'msearch2-grid-search'
		,url: mSearch2.config.connector_url
		,baseParams: {
			action: 'mgr/search/getlist'
		}
		,fields: ['id','pagetitle','intro','weight','published','deleted']
		,autoHeight: true
		,paging: true
		,remoteSort: true
		,columns: [this.exp
			,{header: _('id'), dataIndex: 'id',width: 70}
			,{header: _('pagetitle'), dataIndex: 'pagetitle', width: 150, renderer: this.renderPagetitle}
			,{header: _('mse2_weight'), dataIndex: 'weight', width: 50}
			,{header: _('published'), dataIndex: 'published', width: 50, renderer: this.renderPublished}
			,{header: _('deleted'), dataIndex: 'deleted', width: 50, renderer: this.renderDeleted}
		]
		,plugins: this.exp
		,tbar: [
			{
				xtype: 'textfield'
				,name: 'query'
				,width: 400
				,id: 'msearch2-input-search'
				,emptyText: _('mse2_search')
				,listeners: {
					render: {fn: function(tf) {	tf.getEl().addKeyListener(Ext.EventObject.ENTER, function() {this.search(tf);}, this);if (MODx.request.query) {tf.setValue(this.getUrlParam('query'));this.search(tf);}},scope: this}
					,change: {fn: function(tf) {this.getStore().baseParams.query = tf.getValue();},scope: this}
				}
			}
			,'-'
			,{xtype: 'button', id: 'mse2_search_btn', text: _('mse2_search'),listeners: {click: {fn: function() {var tf = Ext.getCmp('msearch2-input-search'); this.search(tf)} , scope: this}}}
			,'-'
			,{xtype: 'button',text: _('mse2_search_clear'),listeners: {click: {fn: this.clearFilter, scope: this}}}
			,'-'
			,{xtype: 'xcheckbox',value: 1,boxLabel: _('mse2_show_unpublished'),checked: false,name: 'unpublished', id: 'msearch2-check-unpublished'
				,listeners: {check: {fn: function() {Ext.getCmp('mse2_search_btn').fireEvent('click');}}}
			}
			,'-'
			,{xtype: 'xcheckbox',value: 1,boxLabel: _('mse2_show_deleted'),checked: false,name: 'deleted', id: 'msearch2-check-deleted'
				,listeners: {check: {fn: function() {Ext.getCmp('mse2_search_btn').fireEvent('click');}}}
			}
		]
	});
	mSearch2.grid.Search.superclass.constructor.call(this,config);
};
Ext.extend(mSearch2.grid.Search,MODx.grid.Grid,{
	windows: {}

	,search: function(tf, nv, ov) {
		var s = this.getStore();
		var query = tf.getValue();
		if (query != '') {
			s.baseParams.query = query;
			s.baseParams.unpublished = Ext.getCmp('msearch2-check-unpublished').getValue() == true ? 1 : 0;
			s.baseParams.deleted = Ext.getCmp('msearch2-check-deleted').getValue() == true ? 1 : 0;
			this.getBottomToolbar().changePage(1);
			this.refresh();
		}
	}

	,clearFilter: function(btn,e) {
		var s = this.getStore();
		s.baseParams.query = '';
		Ext.getCmp('msearch2-input-search').setValue('');
		this.getBottomToolbar().changePage(1);
		this.refresh();
	}

	,getUrlParam: function(param) {
		var params = Ext.urlDecode(location.search.substring(1));
		return param ? params[param] : params;
	}

	,renderPagetitle: function(val, cell, row) {
		var action = MODx.action ? MODx.action['resource/update'] : 'resource/update';
		var url = 'index.php?a='+action+'&id='+row.data['id'];

		return '<a href="' + url + '" target="_blank" style="color:#0088cc">' + val + '</a>'
	}

	,renderPublished: function(val, cell, row) {
		return (val == 1)
			? '<span style="color:green;">' + _('yes') + '</span>'
			: '<span style="color:red;">' + _('no') + '</span>'
		;
	}

	,renderDeleted: function(val, cell, row) {
		return (val == 1)
			? '<span style="color:red;">' + _('yes') + '</span>'
			: '<span style="color:green;">' + _('no') + '</span>'
		;
	}

});
Ext.reg('msearch2-grid-search',mSearch2.grid.Search);