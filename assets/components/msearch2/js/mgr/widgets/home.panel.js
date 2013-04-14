mSearch2.panel.Home = function(config) {
	config = config || {};
	Ext.apply(config,{
		border: false
		,baseCls: 'modx-formpanel'
		,items: [{
			html: '<h2>'+_('msearch2')+'</h2>'
			,border: false
			,cls: 'modx-page-header container'
		},{
			xtype: 'modx-tabs'
			,bodyStyle: 'padding: 10px'
			,defaults: { border: false ,autoHeight: true }
			,border: true
			,activeItem: 0
			,hideMode: 'offsets'
			,items: [{
				title: _('msearch2_items')
				,items: [{
					html: _('msearch2_intro_msg')
					,border: false
					,bodyCssClass: 'panel-desc'
					,bodyStyle: 'margin-bottom: 10px'
				},{
					xtype: 'msearch2-grid-items'
					,preventRender: true
				}]
			}]
		}]
	});
	mSearch2.panel.Home.superclass.constructor.call(this,config);
};
Ext.extend(mSearch2.panel.Home,MODx.Panel);
Ext.reg('msearch2-panel-home',mSearch2.panel.Home);
