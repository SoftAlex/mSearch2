Ext.onReady(function() {
	MODx.load({ xtype: 'msearch2-page-home'});
});

mSearch2.page.Home = function(config) {
	config = config || {};
	Ext.applyIf(config,{
		components: [{
			xtype: 'msearch2-panel-home'
			,renderTo: 'msearch2-panel-home-div'
		}]
	}); 
	mSearch2.page.Home.superclass.constructor.call(this,config);
};
Ext.extend(mSearch2.page.Home,MODx.Component);
Ext.reg('msearch2-page-home',mSearch2.page.Home);