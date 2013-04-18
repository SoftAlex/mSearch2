mSearch2.panel.Index = function(config) {
	config = config || {};
	Ext.applyIf(config,{
		id: 'modx-panel-search'
		,cls: 'container form-with-labels'
		,labelAlign: 'left'
		,autoHeight: true
		,title: _('search_criteria')
		,labelWidth: 200
		,items: [{
			layout: 'form'
			,cls: 'main-wrapper'
			,border: false
			,items: this.getFields(config)
		},{
			html: '<hr />'
			,border: false
		}]
		,buttonAlign: 'left'
		,buttons: [
			{text: _('mse2_index_create'),handler: function() {this.indexCreate(0);},scope: this}
			,'-'
			,{text: _('mse2_index_clear'),handler: function() {this.indexClear();},scope: this}
		]
		,listeners: {
			render: {fn: this.getStat, scope: this}
		}
	});
	mSearch2.panel.Index.superclass.constructor.call(this,config);
};

Ext.extend(mSearch2.panel.Index,MODx.FormPanel,{
	filters: {}

	,getFields: function() {
		var fields = [];
		var tmp = {
			total: {value: 0}
			,indexed: {value: 0}
			,words: {value: 0}
		};

		for (var i in tmp) {
			if (!tmp.hasOwnProperty(i)) {continue;}
			var field = tmp[i];
			Ext.applyIf(field, {
				name: i
				,xtype: 'displayfield'
				,fieldLabel: _('mse2_index_' + i)
			});
			fields.push(field);
		}

		return fields;
	}

	,indexCreate: function(offset) {
		var el = this.getEl();
		el.mask(_('loading'),'x-mask-loading');
		MODx.Ajax.request({
			url: mSearch2.config.connector_url
			,params: {
				action: 'mgr/index/create'
				,limit: 10
				,offset: offset
			}
			,listeners: {
				success: {fn:function(r) {
					if (r.object.indexed > 0) {
						this.indexCreate(Number(r.object.indexed) + Number(offset));
					}
					else {
						el.unmask();
						this.getStat();
					}
				},scope: this}
				,failure: {fn:function(r) {
					el.unmask();
				}, scope:this}
			}
		})
	}

	,indexClear: function() {
		var el = this.getEl();
		el.mask(_('loading'),'x-mask-loading');
		MODx.Ajax.request({
			url: mSearch2.config.connector_url
			,params: {
				action: 'mgr/index/remove'
			}
			,listeners: {
				success: {fn:function(r) {
					el.unmask();
					this.getStat();
				},scope: this}
				,failure: {fn:function(r) {
					el.unmask();
				}, scope:this}
			}
		})
	}

	,getStat: function() {
		var el = this.getEl();
		el.mask(_('loading'),'x-mask-loading');
		MODx.Ajax.request({
			url: mSearch2.config.connector_url
			,params: {
				action: 'mgr/index/stat'
			}
			,listeners: {
				success: {fn:function(r) {el.unmask();
					var form = this.getForm();
					form.setValues(r.object);
				},scope: this}
				,failure: {fn:function(r) {el.unmask();}, scope:this}
			}
		})
	}

});
Ext.reg('msearch2-form-index',mSearch2.panel.Index);