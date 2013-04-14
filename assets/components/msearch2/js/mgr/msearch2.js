var mSearch2 = function(config) {
	config = config || {};
	mSearch2.superclass.constructor.call(this,config);
};
Ext.extend(mSearch2,Ext.Component,{
	page:{},window:{},grid:{},tree:{},panel:{},combo:{},config: {},view: {}
});
Ext.reg('msearch2',mSearch2);

mSearch2 = new mSearch2();