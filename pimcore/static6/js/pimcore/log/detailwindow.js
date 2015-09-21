pimcore.registerNS("pimcore.log.detailwindow");
pimcore.log.detailwindow = Class.create({
    getClassName: function (){
        return "pimcore.plugin.eventscheduler.detailwindow";
    },
    
	initialize: function (data) {
		this.data = data;
		this.getInputWindow();
        this.detailWindow.show();
	},    


    getInputWindow: function () {
        
        if(!this.detailWindow) {
            this.detailWindow = new Ext.Window({
				width: 600,
				height: 420,
                iconCls: "errorlog-icon",
                title: t('log_detailinformation'),
				closeAction:'close',
				plain: true,
				maximized: false,
				autoScroll: true,
				modal: true,
				buttons: [
                    {
                        text: t('close'),
                        handler: function(){
                            this.detailWindow.hide();
                            this.detailWindow.destroy();
                        }.bind(this)
                    }					
                ]
			});
			
			this.createPanel();
        }
        return this.detailWindow;
    },
	

	createPanel: function() {
		var items = [];
		items.push({
			xtype: "textfield",
			fieldLabel: t('log_timestamp'),
			name: "timestamp",
            readOnly: true,
			value: this.data.timestamp,
			width: 400
		});
		items.push({
			xtype: "textarea",
			fieldLabel: t('log_message'),
			name: "message",
            readOnly: true,
			value: this.data.message,
			width: 400,
            height: 200
		});
		items.push({
			xtype: "textfield",
			fieldLabel: t('log_type'),
			name: "type",
            readOnly: true,
			value: this.data.priority,
			width: 400
		});
        items.push({
            xtype: "textfield",
            fieldLabel: t('log_component'),
            name: "component",
            readOnly: true,
            value: this.data.component,
            width: 400
        });
        items.push(new Ext.form.FieldContainer({
            layout: 'hbox',
            items: [{
                        xtype: "textfield",
                        fieldLabel: t('log_relatedobject'),
                        name: "relatedobject",
                        readOnly: true,
                        value: this.data.relatedobject,
                        width: 370
                    },{
                        xtype: "button",
                        iconCls: "pimcore_icon_edit",
                        handler: function() {
                            pimcore.helpers.openObject(this.data.relatedobject);
                            this.detailWindow.destroy();
                        }.bind(this)
                    }]
        }));

        var text = this.data.fileobject;
        if(text.length > 60) {
            text = text.substr(0, 60) + "...";
        }

        items.push({
            xtype: "displayfield",
            fieldLabel: t('log_fileobject'),
            name: "fileobject",
            readOnly: true,
            html: Ext.String.format('<a href="{0}" target="_blank">{1}</a>', this.data.fileobject, text),
            width: 400
        });


        var panel = new Ext.form.FormPanel({
            border: false,
			frame:false,
		    bodyStyle: 'padding:10px',
            items: items,
			labelWidth: 130,
			collapsible: false,
            autoScroll: true
        });
		
		this.detailWindow.add(panel);
	}

});