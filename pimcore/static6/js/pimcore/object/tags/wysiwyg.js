/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.object.tags.wysiwyg");
pimcore.object.tags.wysiwyg = Class.create(pimcore.object.tags.abstract, {

    type: "wysiwyg",

    initialize: function (data, fieldConfig) {
        this.data = "";
        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
        this.editableDivId = "object_wysiwyg_" + uniqid();
    },

    /**
     * @extjs since HTMLEditor seems not working properly in grid, this feature is deactivated for now
     */
    /*getGridColumnEditor: function(field) {
        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        if(field.layout.noteditable) {
            return null;
        }
        // WYSIWYG
        if (field.type == "wysiwyg") {
            return Ext.create('Ext.form.HtmlEditor', {
                width: 500,
                height: 300
            });
        }
    },*/

    getGridColumnFilter: function(field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayout: function () {

        var html = '<div class="pimcore_tag_wysiwyg" id="' + this.editableDivId + '" contenteditable="true">' + this.data + '</div>';
        var pConf = {
            iconCls: "pimcore_icon_droptarget",
            title: this.fieldConfig.title,
            html: html,
            border: true,
            style: "margin-bottom: 10px",
            manageHeight: false,
            cls: "object_field"
        };

        if(this.fieldConfig.width) {
            pConf["width"] = this.fieldConfig.width;
        }

        if(this.fieldConfig.height) {
            pConf["height"] = this.fieldConfig.height;
            pConf["autoScroll"] = true;
        } else {
            pConf["autoHeight"] = true;
            pConf["autoScroll"] = true;
        }

        this.component = new Ext.Panel(pConf);
    },

    getLayoutShow: function () {
        this.getLayout();
        this.component.on("afterrender", function() {
            Ext.get(this.editableDivId).dom.setAttribute("contenteditable", "false");
        }.bind(this));
        return this.component;
    },

    getLayoutEdit: function () {
        this.getLayout();
        this.component.on("afterlayout", this.initCkEditor.bind(this));
        this.component.on("beforedestroy", function() {
            if(this.ckeditor) {
                this.ckeditor.destroy();
                this.ckeditor = null;
            }
        }.bind(this));
        return this.component;
    },

    initCkEditor: function () {

        if (this.ckeditor) {
            return;
        }

        // add drop zone, use the parent panel here (container), otherwise this can cause problems when specifying a fixed height on the wysiwyg
        var dd = new Ext.dd.DropZone(Ext.get(this.editableDivId).parent(), {
            ddGroup: "element",

            getTargetFromEvent: function(e) {
                return this.getEl();
            },

            onNodeOver : function(target, dd, e, data) {
                return Ext.dd.DropZone.prototype.dropAllowed;
            },

            onNodeDrop : this.onNodeDrop.bind(this)
        });

        var eConfig = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            resize_enabled: false,
            language: pimcore.settings["language"]
        };


        eConfig.toolbarGroups = [
            { name: 'clipboard', groups: [ "sourcedialog", 'clipboard', 'undo', "find" ] },
            { name: 'basicstyles', groups: [ 'basicstyles', 'list'] },
            '/',
            { name: 'paragraph', groups: [ 'align', 'indent'] },
            { name: 'blocks' },
            { name: 'links' },
            { name: 'insert' },
            "/",
            { name: 'styles' },
            { name: 'tools', groups: ['colors', "tools", 'cleanup', 'mode', "others"] }
        ];

        if(this.fieldConfig.toolbarConfig) {
            eConfig.toolbarGroups = Ext.decode("[" + this.fieldConfig.toolbarConfig + "]")
        }

        eConfig.allowedContent = true; // disables CKEditor ACF (will remove pimcore_* attributes from links, etc.)
        eConfig.removePlugins = "tableresize";

        if(typeof(pimcore.object.tags.wysiwyg.defaultEditorConfig) == 'object'){
            eConfig = mergeObject(pimcore.object.tags.wysiwyg.defaultEditorConfig,eConfig);
        }

        if (intval(this.fieldConfig.width) > 1) {
            eConfig.width = this.fieldConfig.width;
        }
        if (intval(this.fieldConfig.height) > 1) {
            eConfig.height = this.fieldConfig.height;
        }

        try {
            this.ckeditor = CKEDITOR.inline(this.editableDivId, eConfig);

            // disable URL field in image dialog
            this.ckeditor.on("dialogShow", function (e) {
                var urlField = e.data.getElement().findOne("input");
                if(urlField && urlField.getValue()) {
                    if(urlField.getValue().indexOf("/image-thumbnails/") > 1) {
                        urlField.getParent().getParent().getParent().hide();
                    }
                } else if (urlField) {
                    urlField.getParent().getParent().getParent().show();
                }
            });

            // HACK - clean all pasted html
            this.ckeditor.on('paste', function(evt) {
                evt.data.dataValue = '<!--class="Mso"-->' + evt.data.dataValue;
            }, null, null, 1);

        } catch (e) {
            console.log(e);
        }
    },

    onNodeDrop: function (target, dd, e, data) {
        var record = data.records[0];
        data = record.data;

        if (!this.ckeditor ||!this.dndAllowed(data)) {
            return;
        }
   
        // we have to foxus the editor otherwise an error is thrown in the case the editor wasn't opend before a drop element
        this.ckeditor.focus();

        var wrappedText = data.text;
        var textIsSelected = false;
        
        try {
            var selection = this.ckeditor.getSelection();
            var bookmarks = selection.createBookmarks();
            var range = selection.getRanges()[ 0 ];
            var fragment = range.clone().cloneContents();

            selection.selectBookmarks(bookmarks);
            var retval = "";
            var childList = fragment.getChildren();
            var childCount = childList.count();

            for (var i = 0; i < childCount; i++) {
                var child = childList.getItem(i);
                retval += ( child.getOuterHtml ?
                        child.getOuterHtml() : child.getText() );
            }

            if (retval.length > 0) {
                wrappedText = retval;
                textIsSelected = true;
            }
        }
        catch (e2) {
        }

        // remove existing links out of the wrapped text
        wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
            if($1.toLowerCase() == "a") {
                return "";
            }
            return $0;
        });

        var insertEl = null;
        var id = data.id;
        var uri = data.path;
        var browserPossibleExtensions = ["jpg","jpeg","gif","png"];

        if (data.elementType == "asset") {
            if (data.type == "image" && textIsSelected == false) {
                // images bigger than 600px or formats which cannot be displayed by the browser directly will be
                // converted by the pimcore thumbnailing service so that they can be displayed in the editor
                var defaultWidth = 600;
                var additionalAttributes = "";

                if(typeof data.imageWidth != "undefined") {
                    uri = "/admin/asset/get-image-thumbnail/id/" + id + "/width/" + defaultWidth + "/aspectratio/true";
                    if(data.imageWidth < defaultWidth
                            && in_arrayi(pimcore.helpers.getFileExtension(data.text),
                                        browserPossibleExtensions)) {
                        uri = data.path;
                        additionalAttributes += ' pimcore_disable_thumbnail="true"';
                    }

                    if(data.imageWidth < defaultWidth) {
                        defaultWidth = data.imageWidth;
                    }

                    additionalAttributes += ' style="width:' + defaultWidth + 'px;"';
                }

                insertEl = CKEDITOR.dom.element.createFromHtml('<img src="'
                            + uri + '" pimcore_type="asset" pimcore_id="' + id + '" ' + additionalAttributes + ' />');
                this.ckeditor.insertElement(insertEl);
                return true;
            }
            else {
                insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri
                            + '" target="_blank" pimcore_type="asset" pimcore_id="' + id + '">' + wrappedText + '</a>');
                this.ckeditor.insertElement(insertEl);
                return true;
            }
        }

        if (data.elementType == "document" && (data.type=="page"
                            || data.type=="hardlink" || data.type=="link")){
            insertEl = CKEDITOR.dom.element.createFromHtml('<a href="' + uri + '" pimcore_type="document" pimcore_id="'
                                                                        + id + '">' + wrappedText + '</a>');
            this.ckeditor.insertElement(insertEl);
            return true;
        }
 
        if (data.elementType == "object") {

    		var document_language = '';
    		
    		this.url = '';
    		
    		Ext.Ajax.request({
				url: "/admin/object/get-object-url/",
				params: {id: id},
				success: function(response) {
					var data = Ext.decode(response.responseText);
					if (data.succes){

						insertEl = CKEDITOR.dom.element.createFromHtml('<a href="'+data.url+'" pimcore_type="object" pimcore_id="' + id + '">' + wrappedText + '</a>');
	            		this.ckeditor.insertElement(insertEl);
	            		return true;
					}else{
						
						this.url = data.url;
						
						if (data.not_filled.indexOf('%language') > -1){
							var languagestore = [];
				            var websiteLanguages = pimcore.settings.websiteLanguages;
				            var selectContent = "";
				            for (var i=0; i<websiteLanguages.length; i++) {
				                selectContent = pimcore.available_languages[websiteLanguages[i]] + " [" + websiteLanguages[i] + "]";
				                languagestore.push([websiteLanguages[i], selectContent]);
				            }
				        
				            this.language = '';
				            
				    	    this.languageWindow = Ext.create('Ext.Window', {
				    	        title:  t('language'),
				    	        bodyStyle: 'padding: 20px;',
				    	        width: 400,
				    	        height: 200,
				    	        plain: true,
				    	        items:[
				    	               {
				    	                   xtype: 'combobox',
				    	                   fieldLabel: t('language'),
				    	                   name: "language",
				    	                   store: languagestore,
				    	                   editable: false,
				    	                   forceSelection: true,
				    	                   triggerAction: 'all',
				    	                   mode: "local",
				    	                   listeners: {
				    	                       select: function(combo, records){
				    	                    	   this.language = records.data.field1;
				    	                    	   Ext.getCmp('saveBtn').enable();
				    	                       }.bind(this)
				    	                       
				    	                   }
				    	           }],
				    	           buttons: [{
				    	               text: t('save'),
				    	               id: 'saveBtn',
				    	               formBind: true,
				    	               disabled: true,
				    	               listeners: {
				    	                   click: function (){
				    	                	   
				    	                	   this.url = this.url.replace('%language', this.language);
				    	                	   insertEl = CKEDITOR.dom.element.createFromHtml('<a href="'+this.url+'" pimcore_type="object" pimcore_id="' + id + '">' + wrappedText + '</a>');
				    	                	   this.ckeditor.insertElement(insertEl);
				    	                	   
				    	                	   this.languageWindow.close();
				    	                	   
				    	                   }.bind(this)
				    	               }
				    	           }]
				    	    }).show();
				    	 
						}else{
							Ext.Msg.show({
							   title: t('url_error'),
							   msg: t('url_error_message') + data.not_filled.toString(), 
	                		   buttons: Ext.Msg.OK,
	                		   animEl: 'elId'
	                		});
						}
						return false;
					}
					
				
				}.bind(this)
			});
       
        	
        }
    },
    
    onNodeOver: function(target, dd, e, data) {
        var record = data.records[0];
        data = record.data;
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },
 
    dndAllowed: function(data) {

        if (data.elementType == "document" && (data.type=="page"
                            || data.ype=="hardlink" || data.type=="link")){
            return true;
        } else if (data.elementType=="asset" && data.type != "folder"){
            return true;
        } else if (data.elementType=="object" && data.type != "folder" && data.objectUrl ){
            return true;
        }

        return false;

    },

    getValue: function () {

        var data = this.data;
        try {
            if (this.ckeditor) {
                data = this.ckeditor.getData();
            }
        }
        catch (e) {
        }

        this.data = data;

        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        if(this.dirty) {
            return this.dirty;
        }
        
        if(this.ckeditor) {
            return this.ckeditor.checkDirty();
        }
        return false;
    }
});

CKEDITOR.disableAutoInline = true;

// IE Hack see: http://dev.ckeditor.com/ticket/9958
// problem is that every button in a CKEDITOR window fires the onbeforeunload event
CKEDITOR.on('instanceReady', function (event) {
    event.editor.on('dialogShow', function (dialogShowEvent) {
        if (CKEDITOR.env.ie) {
            $(dialogShowEvent.data._.element.$).find('a[href*="void(0)"]').removeAttr('href');
        }
    });
});
