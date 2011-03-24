Ext.layout.PimcoreFormLayout = Ext.extend(Ext.layout.FormLayout, {
    type: 'form',

    monitorResize: true,

    onLayout : function(ct, target){
        Ext.layout.AutoLayout.superclass.onLayout.call(this, ct, target);
        var cs = this.getRenderedItems(ct), len = cs.length, i, c;
        for(i = 0; i < len; i++){
            c = cs[i];
            if (c.doLayout){
                
                c.doLayout(true);
            }
        }
    }
});
Ext.Container.LAYOUTS['pimcoreform'] = Ext.layout.PimcoreFormLayout;