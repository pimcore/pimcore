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

/*global CKEDITOR*/
pimcore.registerNS("pimcore.document.tags.wysiwyg");
pimcore.document.tags.wysiwyg = Class.create(pimcore.document.tag, {

    type: "wysiwyg",

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.setupWrapper();
        options = this.parseOptions(options);

        if (!data) {
            data = "";
        }
        this.data = data;
        this.options = options;


        var textareaId = id + "_textarea";
        this.textarea = document.createElement("div");
        this.textarea.setAttribute("contenteditable","true");

        Ext.get(id).appendChild(this.textarea);

        Ext.get(id).insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');

        this.textarea.id = textareaId;
        this.textarea.innerHTML = data;

        var textareaHeight = 100;
        if (options.height) {
            textareaHeight = options.height;
        }
        if (options.placeholder) {
            this.textarea.setAttribute('data-placeholder', options["placeholder"]);
        }

        var inactiveContainerWidth = options.width + "px";
        if (typeof options.width == "string" && options.width.indexOf("%") >= 0) {
            inactiveContainerWidth = options.width;
        }

        Ext.get(this.textarea).addCls("pimcore_wysiwyg_inactive");
        Ext.get(this.textarea).addCls("pimcore_wysiwyg");
        Ext.get(this.textarea).applyStyles("width: " + inactiveContainerWidth  + "; min-height: " + textareaHeight
                                                                                                + "px;");

        // register at global DnD manager
        if (typeof dndManager !== 'undefined') {
            dndManager.addDropTarget(Ext.get(id), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));
        }

        this.startCKeditor();

        this.checkValue();
    },

    startCKeditor: function () {
        
        try {
            CKEDITOR.config.language = pimcore.globalmanager.get("user").language;
            var eConfig = Object.clone(this.options);

            // if there is no toolbar defined use Full which is defined in CKEDITOR.config.toolbar_Full, possible
            // is also Basic
            if (!this.options["toolbarGroups"]) {
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
            }

            delete eConfig.width;

            var removePluginsAdd = "";
            if(eConfig.removePlugins) {
                removePluginsAdd = "," + eConfig.removePlugins;
            }

            eConfig.language = pimcore.settings["language"];
            eConfig.removePlugins = 'bgcolor,' + removePluginsAdd;
            eConfig.entities = false;
            eConfig.entities_greek = false;
            eConfig.entities_latin = false;
            eConfig.allowedContent = true; // disables CKEditor ACF (will remove pimcore_* attributes from links, etc.)

            this.ckeditor = CKEDITOR.inline(this.textarea, eConfig);

            this.ckeditor.on('focus', function () {
                Ext.get(this.textarea).removeCls("pimcore_wysiwyg_inactive");
            }.bind(this));

            this.ckeditor.on('blur', function () {
                Ext.get(this.textarea).addCls("pimcore_wysiwyg_inactive");
            }.bind(this));

            this.ckeditor.on('change', this.checkValue.bind(this));

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

        }
        catch (e) {
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
        	
        	if (data.previewUrl){
        		var link = '';
        		if (data.previewUrl.indexOf('%o_key')){
        			link = data.previewUrl.replace('%o_key', data.text);
        		}else if (data.previewUrl.indexOf('%o_id')){
        			link = data.previewUrl.replace('%o_id', data.id);
        		}
        		
        		if (link != ''){
        			insertEl = CKEDITOR.dom.element.createFromHtml('<a href="'+link+'" pimcore_type="object" pimcore_id="' + id + '">' + wrappedText + '</a>');
            		this.ckeditor.insertElement(insertEl);
            	   
            		return true;
        		}else{
        			return false;
        		}
            	   
        	}
        }
        /*
        if (data.elementType == "object") {
        	
        	// Load static routes
			this.store = pimcore.helpers.grid.buildDefaultStore( 
					'/admin/settings/staticroutes?',
					[
						{name:'id', type: 'int'},
						{name:'name'},
						{name:'pattern', allowBlank:false},
						{name:'reverse', allowBlank:true},
						{name:'module'},
						{name:'controller'},
						{name:'action'},
						{name:'variables'},
						{name:'defaults'},
						{name:'siteId'},
						{name:'priority', type:'int'},
						{name: 'creationDate'},
						{name: 'modificationDate'}
					], null, {
						remoteSort: false,
						remoteFilter: false
					}
			);
			            
            this.store.setAutoSync(true);
           
			// Load object data
			Ext.Ajax.request({
				url: "/admin/object/get/",
				params: {id: id, lock: 0},
				success: function(response) {
					this.object = Ext.decode(response.responseText);
				
				}.bind(this)
			});
           
            this.route = null;
           
    	    this.routeWindow = Ext.create('Ext.Window', {
    	        title: 'Object: ' + data.path + ' (ID: ' + id + ')',
    	        bodyStyle: 'padding: 20px;',
    	        width: 500,
    	        height: 200,
    	        plain: true,
    	        items:[
    	               {
    	                   xtype: 'combobox',
    	                   fieldLabel: t('select_static_route'),
    	                   labelWidth: 120,
    	                   width: 400,
    	                   queryMode: 'local',
    	                   store:this.store,
    	                   emptyText: t('static_route'),
    	                   tpl: '<tpl for="."><div class="x-boundlist-item" >{name} ({reverse})</div></tpl>',
    	                   displayTpl:'<tpl for=".">{name} ({reverse})</tpl>',
    	                   displayField: 'name',
    	                   valueField: 'reverse',
    	                   typeAhead: true,
    	                   forceSelection: true,
    	                   listeners: {
    	                       select: function(combo, records){
    	                    	   this.route = records.data;
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
    	                	   var reverse = this.route.reverse;

    	                	   if (this.route && this.object){
    	                		   
    	                		   var object = this.object;
    	                		   var link = reverse;
    	                		   var link_values = [];
    	                		   
    	                		   var keys = this.route.variables.replace(/\s+/g, '').split(",");
        	                	   keys.forEach(function(val){
        	                		   
        	                		   link_values[val] = '';
        	                		   
        	                		   switch(val) {
	        	                		    case 'key':
	        	                		    	link_values['key'] = object.general.o_key;
	        	                		        break;
	        	                		    case 'o_key':
	        	                		    	link_values['o_key'] = object.general.o_key;
	        	                		        break;
	        	                		    case 'id':
	        	                		    	link_values['id'] = object.general.o_id;
	        	                		    case 'o_id':
	        	                		    	link_values['o_id'] = object.general.o_id;
	        	                		        break;
	        	                		    default:
	        	                		        if (object.data.hasOwnProperty(val)){
	        	                		        	if (typeof object.data[val] === 'string' || typeof object.data[val] === 'number'){
	        	                		        		link_values[val] = object.data[val];
	        	                		        	}
	        	                		        }
	        	                		}
        	                
        	                		 
        	                	   });
        	                	   
        	                	   var values_added = false;
        	                	   for (var key in link_values){
        	                		   if (link_values.hasOwnProperty(key)) {
	        	                		   link = link.replace('%'+key, link_values[key]);
	        	                		   if (link_values[key] != ''){ values_added=true; }
        	                		   }
        	                	   };
        	                	   
        	                	   // Check if there are values added to the link
        	                	   if (values_added){
        	                	  	   
        	                    	   insertEl = CKEDITOR.dom.element.createFromHtml('<a href="'+link+'" pimcore_type="object" pimcore_id="' + id + '">' + wrappedText + '</a>');
        	                    	   this.ckeditor.insertElement(insertEl);
        	                    	   
        	                    	   this.routeWindow.close();
        	                    	   
        	                	   }else{
        	                		   Ext.Msg.show({
            	                		   title:t('save_error'),
            	                		   msg: t('link_no_values_error'),
            	                		   buttons: Ext.Msg.OK,
            	                		   animEl: 'elId'
            	                		});
        	                	   }
        	                	
        	              
    	                	   }
    	                	
    	                   }.bind(this)
    	               }
    	           }]
    	    }).show();
        }
		*/
    },

    checkValue: function () {

        var value = this.getValue();

        if(trim(strip_tags(value)).length < 1) {
            Ext.get(this.textarea).addCls("empty");
        } else {
            Ext.get(this.textarea).removeCls("empty");
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
        } else if (data.elementType=="asset" && data.type != "folder" ){
            return true;
        } else if (data.elementType=="object" && data.type != "folder" && data.previewUrl ){
            return true;
        }

        return false;

    },


    getValue: function () {

        var value = this.data;

        if (this.ckeditor) {
            value = this.ckeditor.getData();
        }

        this.data = value;

        return value;
    },

    getType: function () {
        return "wysiwyg";
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