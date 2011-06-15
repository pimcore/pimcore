/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( 'pimcorelink',
{
	init : function( editor )
	{
		// Add the link and unlink buttons.
		editor.addCommand( 'link', new CKEDITOR.pimcoreLinkCommand() );
		editor.addCommand( 'anchor', new CKEDITOR.dialogCommand( 'anchor' ) );
		editor.addCommand( 'unlink', new CKEDITOR.pimcoreUnlinkCommand() );

		editor.ui.addButton( 'Link',
			{
				label : editor.lang.link.toolbar,
				command : 'link'
			} );
		editor.ui.addButton( 'Unlink',
			{
				label : editor.lang.unlink,
				command : 'unlink'
			} );
		editor.ui.addButton( 'Anchor',
			{
				label : editor.lang.anchor.toolbar,
				command : 'anchor'
			} );

//			CKEDITOR.dialog.add( 'link', this.path + 'dialogs/link.js' );
 			CKEDITOR.dialog.add( 'anchor', '/pimcore/static/js/lib/ckeditor-plugins/pimcorelink/dialogs/anchor.js' );

		// Add the CSS styles for anchor placeholders.

		var side = ( editor.lang.dir == 'rtl' ? 'right' : 'left' );
		var basicCss =
			'background:url(' + CKEDITOR.getUrl( this.path + 'images/anchor.gif' ) + ') no-repeat ' + side + ' center;' +
			'border:1px dotted #00f;';

		editor.addCss(
			'a.cke_anchor,a.cke_anchor_empty' +
			// IE6 breaks with the following selectors.
			( ( CKEDITOR.env.ie && CKEDITOR.env.version < 7 ) ? '' :
				',a[name],a[data-cke-saved-name]' ) +
			'{' +
				basicCss +
				'padding-' + side + ':18px;' +
				// Show the arrow cursor for the anchor image (FF at least).
				'cursor:auto;' +
			'}' +
			( CKEDITOR.env.ie ? (
				'a.cke_anchor_empty' +
				'{' +
					// Make empty anchor selectable on IE.
					'display:inline-block;' +
				'}'
				) : '' ) +
			'img.cke_anchor' +
			'{' +
				basicCss +
				'width:16px;' +
				'min-height:15px;' +
				// The default line-height on IE.
				'height:1.15em;' +
				// Opera works better with "middle" (even if not perfect)
				'vertical-align:' + ( CKEDITOR.env.opera ? 'middle' : 'text-bottom' ) + ';' +
			'}');

		// Register selection change handler for the unlink button.
		 editor.on( 'selectionChange', function( evt )
			{
				if ( editor.readOnly )
					return;

				/*
				 * Despite our initial hope, document.queryCommandEnabled() does not work
				 * for this in Firefox. So we must detect the state by element paths.
				 */
				var command = editor.getCommand( 'unlink' ),
					element = evt.data.path.lastElement && evt.data.path.lastElement.getAscendant( 'a', true );
				if ( element && element.getName() == 'a' && element.getAttribute( 'href' ) && element.getChildCount() )
					command.setState( CKEDITOR.TRISTATE_OFF );
				else
					command.setState( CKEDITOR.TRISTATE_DISABLED );
			} );

		editor.on( 'doubleclick', function( evt )
			{
				var element = CKEDITOR.plugins.pimcorelink.getSelectedLink( editor ) || evt.data.element;

				if ( !element.isReadOnly() )
				{
					if ( element.is( 'a' ) )
					{
						evt.data.dialog = ( element.getAttribute( 'name' ) && ( !element.getAttribute( 'href' ) || !element.getChildCount() ) ) ? 'anchor' : 'link';
						editor.getSelection().selectElement( element );
					}
					else if ( CKEDITOR.plugins.pimcorelink.tryRestoreFakeAnchor( editor, element ) )
						evt.data.dialog = 'anchor';
				}
			});

		// If the "menu" plugin is loaded, register the menu items.
		if ( editor.addMenuItems )
		{
			editor.addMenuItems(
				{
					anchor :
					{
						label : editor.lang.anchor.menu,
						command : 'anchor',
						group : 'anchor'
					},

					link :
					{
						label : editor.lang.link.menu,
						command : 'link',
						group : 'link',
						order : 1
					},

					unlink :
					{
						label : editor.lang.unlink,
						command : 'unlink',
						group : 'link',
						order : 5
					}
				});
		}

		// If the "contextmenu" plugin is loaded, register the listeners.
		if ( editor.contextMenu )
		{
			editor.contextMenu.addListener( function( element, selection )
				{
					if ( !element || element.isReadOnly() )
						return null;

					var anchor = CKEDITOR.plugins.pimcorelink.tryRestoreFakeAnchor( editor, element );

					if ( !anchor && !( anchor = CKEDITOR.plugins.pimcorelink.getSelectedLink( editor ) ) )
							return null;

					var menu = {};

					if ( anchor.getAttribute( 'href' ) && anchor.getChildCount() )
						menu = { link : CKEDITOR.TRISTATE_OFF, unlink : CKEDITOR.TRISTATE_OFF };

					if ( anchor && anchor.hasAttribute( 'name' ) )
						menu.anchor = CKEDITOR.TRISTATE_OFF;

					return menu;
				});
		}
	},

	afterInit : function( editor )
	{
		// Register a filter to displaying placeholders after mode change.

		var dataProcessor = editor.dataProcessor,
			dataFilter = dataProcessor && dataProcessor.dataFilter,
			htmlFilter = dataProcessor && dataProcessor.htmlFilter,
			pathFilters = editor._.elementsPath && editor._.elementsPath.filters;

		if ( dataFilter )
		{
			dataFilter.addRules(
				{
					elements :
					{
						a : function( element )
						{
							var attributes = element.attributes;
							if ( !attributes.name )
								return null;

							var isEmpty = !element.children.length;

							if ( CKEDITOR.plugins.pimcorelink.synAnchorSelector )
							{
								// IE needs a specific class name to be applied
								// to the anchors, for appropriate styling.
								var ieClass = isEmpty ? 'cke_anchor_empty' : 'cke_anchor';
								var cls = attributes[ 'class' ];
								if ( attributes.name && ( !cls || cls.indexOf( ieClass ) < 0 ) )
									attributes[ 'class' ] = ( cls || '' ) + ' ' + ieClass;

								if ( isEmpty && CKEDITOR.plugins.pimcorelink.emptyAnchorFix )
								{
									attributes.contenteditable = 'false';
									attributes[ 'data-cke-editable' ] = 1;
								}
							}
							else if ( CKEDITOR.plugins.pimcorelink.fakeAnchor && isEmpty )
								return editor.createFakeParserElement( element, 'cke_anchor', 'anchor' );

							return null;
						}
					}
				});
		}

		if ( CKEDITOR.plugins.pimcorelink.emptyAnchorFix && htmlFilter )
		{
			htmlFilter.addRules(
				{
					elements :
					{
						a : function( element )
						{
							delete element.attributes.contenteditable;
						}
					}
				});
		}

		if ( pathFilters )
		{
			pathFilters.push( function( element, name )
				{
					if ( name == 'a' )
					{
						if ( CKEDITOR.plugins.pimcorelink.tryRestoreFakeAnchor( editor, element ) ||
							( element.getAttribute( 'name' ) && ( !element.getAttribute( 'href' ) || !element.getChildCount() ) ) )
						{
							return 'anchor';
						}
					}
				});
		}
	},

	requires : [ 'fakeobjects' ]
} );

CKEDITOR.plugins.pimcorelink =
{
	/**
	 *  Get the surrounding link element of current selection.
	 * @param editor
	 * @example CKEDITOR.plugins.pimcorelink.getSelectedLink( editor );
	 * @since 3.2.1
	 * The following selection will all return the link element.
	 *	 <pre>
	 *  <a href="#">li^nk</a>
	 *  <a href="#">[link]</a>
	 *  text[<a href="#">link]</a>
	 *  <a href="#">li[nk</a>]
	 *  [<b><a href="#">li]nk</a></b>]
	 *  [<a href="#"><b>li]nk</b></a>
	 * </pre>
	 */
	getSelectedLink : function( editor )
	{
		try
		{
			var selection = editor.getSelection();
			if ( selection.getType() == CKEDITOR.SELECTION_ELEMENT )
			{
				var selectedElement = selection.getSelectedElement();
				if ( selectedElement.is( 'a' ) )
					return selectedElement;
			}

			var range = selection.getRanges( true )[ 0 ];
			range.shrink( CKEDITOR.SHRINK_TEXT );
			var root = range.getCommonAncestor();
			return root.getAscendant( 'a', true );
		}
		catch( e ) { return null; }
	},

	// Opera and WebKit don't make it possible to select empty anchors. Fake
	// elements must be used for them.
	fakeAnchor : CKEDITOR.env.opera || CKEDITOR.env.webkit,

	// For browsers that don't support CSS3 a[name]:empty(), note IE9 is included because of #7783.
	synAnchorSelector : CKEDITOR.env.ie,

	// For browsers that have editing issue with empty anchor.
	emptyAnchorFix : CKEDITOR.env.ie && CKEDITOR.env.version < 8,

	tryRestoreFakeAnchor : function( editor, element )
	{
		if ( element && element.data( 'cke-real-element-type' ) && element.data( 'cke-real-element-type' ) == 'anchor' )
		{
			var link  = editor.restoreRealElement( element );
			if ( link.data( 'cke-saved-name' ) )
				return link;
		}
	}
};

CKEDITOR.pimcoreUnlinkCommand = function(){};
CKEDITOR.pimcoreUnlinkCommand.prototype =
{
	/** @ignore */
	exec : function( editor )
	{
		/*
		 * execCommand( 'unlink', ... ) in Firefox leaves behind <span> tags at where
		 * the <a> was, so again we have to remove the link ourselves. (See #430)
		 *
		 * TODO: Use the style system when it's complete. Let's use execCommand()
		 * as a stopgap solution for now.
		 */
		var selection = editor.getSelection(),
			bookmarks = selection.createBookmarks(),
			ranges = selection.getRanges(),
			rangeRoot,
			element;

		for ( var i = 0 ; i < ranges.length ; i++ )
		{
			rangeRoot = ranges[i].getCommonAncestor( true );
			element = rangeRoot.getAscendant( 'a', true );
			if ( !element )
				continue;
			ranges[i].selectNodeContents( element );
		}

		selection.selectRanges( ranges );
		editor.document.$.execCommand( 'unlink', false, null );
		selection.selectBookmarks( bookmarks );
	},

	startDisabled : true
};



/** PIMCORE SPECIFIC COMMANDS **/
CKEDITOR.pimcoreLinkCommand = function(){};
CKEDITOR.pimcoreLinkCommand.prototype =
{
	exec : function( editor )
	{

        var data = {};
        
        this.ckeditor = editor;
        var link = CKEDITOR.plugins.pimcorelink.getSelectedLink(editor);
        this.linkElement = link;

        if(link) {
            var linkType = link.getAttribute("pimcore_id") ? "internal" : "external";
            var urlParts = {
                path: link.getAttribute("href"),
                anchor: "",
                parameters: ""
            };

            if(linkType == "internal") {
                var hrefParts = this.parseUrl(link.getAttribute("href"));
                urlParts.path = hrefParts.path;
                if(hrefParts.query) urlParts.parameters = hrefParts.query;
                if(hrefParts.fragment) urlParts.anchor = hrefParts.fragment;
            }

            data = {
                path: urlParts.path,
                anchor: urlParts.anchor,
                parameters: urlParts.parameters,
                type: linkType,
                pimcore_id: link.getAttribute("pimcore_id"),
                pimcore_type: link.getAttribute("pimcore_type"),
                rel: link.getAttribute("rel"),
                accesskey: link.getAttribute("accesskey"),
                tabindex: link.getAttribute("tabindex"),
                target: link.getAttribute("target"),
                title: link.getAttribute("title")
            };
        }

        this.defaultData = {
            type: "internal",
            path: "",
            parameters: "",
            anchor: "",
            accesskey: "",
            rel: "",
            tabindex: "",
            target: "",
            pimcore_id: "",
            pimcore_type: "",
            title: ""
        };

        this.data = mergeObject(this.defaultData, data);

        this.openEditor();
	},

    parseUrl: function (str, component) {
        // http://kevin.vanzonneveld.net
        // +      original by: Steven Levithan (http://blog.stevenlevithan.com)
        // + reimplemented by: Brett Zamir (http://brett-zamir.me)
        // + input by: Lorenzo Pisani
        // + input by: Tony
        // + improved by: Brett Zamir (http://brett-zamir.me)
        // %          note: Based on http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
        // %          note: blog post at http://blog.stevenlevithan.com/archives/parseuri
        // %          note: demo at http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
        // %          note: Does not replace invalid characters with '_' as in PHP, nor does it return false with
        // %          note: a seriously malformed URL.
        // %          note: Besides function name, is essentially the same as parseUri as well as our allowing
        // %          note: an extra slash after the scheme/protocol (to allow file:/// as in PHP)
        // *     example 1: parse_url('http://username:password@hostname/path?arg=value#anchor');
        // *     returns 1: {scheme: 'http', host: 'hostname', user: 'username', pass: 'password', path: '/path', query: 'arg=value', fragment: 'anchor'}
        var key = ['source', 'scheme', 'authority', 'userInfo', 'user', 'pass', 'host', 'port',
                            'relative', 'path', 'directory', 'file', 'query', 'fragment'],
            ini = (this.php_js && this.php_js.ini) || {},
            mode = (ini['phpjs.parse_url.mode'] &&
                ini['phpjs.parse_url.mode'].local_value) || 'php',
            parser = {
                php: /^(?:([^:\/?#]+):)?(?:\/\/()(?:(?:()(?:([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?()(?:(()(?:(?:[^?#\/]*\/)*)()(?:[^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
                strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
                loose: /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/\/?)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/ // Added one optional slash to post-scheme to catch file:/// (should restrict this)
            };

        var m = parser[mode].exec(str),
            uri = {},
            i = 14;
        while (i--) {
            if (m[i]) {
              uri[key[i]] = m[i];
            }
        }

        if (component) {
            return uri[component.replace('PHP_URL_', '').toLowerCase()];
        }
        if (mode !== 'php') {
            var name = (ini['phpjs.parse_url.queryKey'] &&
                    ini['phpjs.parse_url.queryKey'].local_value) || 'queryKey';
            parser = /(?:^|&)([^&=]*)=?([^&]*)/g;
            uri[name] = {};
            uri[key[12]].replace(parser, function ($0, $1, $2) {
                if ($1) {uri[name][$1] = $2;}
            });
        }
        delete uri.source;
        return uri;
    },

    openEditor: function () {

        this.fieldPath = new Ext.form.TextField({
            fieldLabel: t('path'),
            value: this.data.path,
            name: "path",
            cls: "pimcore_droptarget_input"
        });

        var initDD = function (el) {
            var domElement = el.getEl().dom;
            domElement.dndOver = false;

            domElement.reference = this;

            dndZones.push(domElement);
            el.getEl().on("mouseover", function (e) {
                this.dndOver = true;
            }.bind(domElement));
            el.getEl().on("mouseout", function (e) {
                this.dndOver = false;
            }.bind(domElement));

        }

        this.fieldPath.on("render", initDD.bind(this));

        this.form = new Ext.FormPanel({
            items: [
                {
                    xtype:'tabpanel',
                    activeTab: 0,
                    deferredRender: false,
                    defaults:{autoHeight:true, bodyStyle:'padding:10px'},
                    border: false,
                    items: [
                        {
                            title:t('basic'),
                            layout:'form',
                            border: false,
                            defaultType: 'textfield',
                            items: [
                                {
                                    xtype: "compositefield",
                                    items: [this.fieldPath, {
                                        xtype: "button",
                                        iconCls: "pimcore_icon_search",
                                        handler: this.openSearchEditor.bind(this)
                                    }]
                                },
                                {
                                    xtype:'fieldset',
                                    title: t('properties'),
                                    collapsible: false,
                                    autoHeight:true,
                                    defaultType: 'textfield',
                                    items :[
                                        {
                                            xtype: "combo",
                                            fieldLabel: t('target'),
                                            name: 'target',
                                            triggerAction: 'all',
                                            editable: true,
                                            store: ["","_blank","_self","_top","_parent"],
                                            value: this.data.target
                                        },
                                        {
                                            fieldLabel: t('parameters'),
                                            name: 'parameters',
                                            value: this.data.parameters
                                        },
                                        {
                                            fieldLabel: t('anchor'),
                                            name: 'anchor',
                                            value: this.data.anchor
                                        },
                                        {
                                            fieldLabel: t('title'),
                                            name: 'title',
                                            value: this.data.title
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            title: t('advanced'),
                            layout:'form',
                            defaultType: 'textfield',
                            border: false,
                            items: [
                                {
                                    fieldLabel: t('accesskey'),
                                    name: 'accesskey',
                                    value: this.data.accesskey
                                },
                                {
                                    fieldLabel: t('relation'),
                                    name: 'rel',
                                    value: this.data.rel
                                },
                                {
                                    fieldLabel: ('tabindex'),
                                    name: 'tabindex',
                                    value: this.data.tabindex
                                }
                            ]
                        }
                    ]
                }
            ],
            buttons: [
                {
                    text: t("cancel"),
                    listeners:  {
                        "click": this.cancel.bind(this)
                    }
                },
                {
                    text: t("save"),
                    listeners: {
                        "click": this.save.bind(this)
                    },
                    icon: "/pimcore/static/img/icon/tick.png"
                }
            ]
        });


        this.window = new Ext.Window({
            modal: true,
            width: 500,
            height: 330,
            title: "Edit link",
            items: [this.form],
            layout: "fit"
        });
        this.window.show();
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset","document"]
        });
    },

    addDataFromSelector: function (item) {
        if (item) {
            this.fieldPath.setValue(item.fullpath);
            return true;
        }
    },

    getLinkContent: function () {

        var text = "[" + t("not_set") + "]";
        if (this.data.text) {
            text = this.data.text;
        }
        if (this.data.path) {
            return '<a href="' + this.data.path + '">' + text + '</a>'
        }
        return text;
    },

    onNodeDrop: function (target, dd, e, data) {

        if(this.dndAllowed(data)){
            this.fieldPath.setValue(data.node.attributes.path);
            return true;
        } else return false;
    },

    onNodeOver: function(target, dd, e, data) {
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    dndAllowed: function(data) {

        if (data.node.attributes.elementType == "asset" && data.node.attributes.type != "folder") {
            return true;
        } else if (data.node.attributes.elementType == "document" && data.node.attributes.type=="page"){
            return true;
        }
        return false;

    },

    save: function () {

        // close window
        this.window.hide();

        var values = this.form.getForm().getFieldValues();
        this.data = mergeObject(this.data, values);

        var href = this.data.path;
        if(this.data.parameters) {
            href += "?";
            href += this.data.parameters;
        }
        if(this.data.anchor) {
            href += "#";
            href += this.data.anchor;
        }

        console.log(href);

        if(this.linkElement) {
            
            this.linkElement.setAttribute("href",href);
            this.linkElement.setAttribute("rel",this.data.rel);
            this.linkElement.setAttribute("accesskey",this.data.accesskey);
            this.linkElement.setAttribute("tabindex",this.data.tabindex);
            this.linkElement.setAttribute("target",this.data.target);
            this.linkElement.setAttribute("title",this.data.title);

            if(this.data.pimcore_id) {
                this.linkElement.setAttribute("pimcore_id",this.data.pimcore_id);
            }
            if(this.data.pimcore_type) {
                this.linkElement.setAttribute("pimcore_type",this.data.pimcore_type);
            }
        } else {


            var attributeString =
                'href="' + href + '" ' +
                'pimcore_id="' + this.data.pimcore_id + '" ' +
                'pimcore_type="' + this.data.pimcore_type + '" ' +
                'rel="' + this.data.rel + '" ' +
                'accesskey="' + this.data.accesskey + '" ' +
                'tabindex="' + this.data.tabindex + '" ' +
                'target="' + this.data.target + '" ';

            var wrappedText = "";
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
            catch (e) {
            }

            // remove existing links out of the wrapped text
            wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
                if($1.toLowerCase() == "a") {
                    return "";
                }
                return $0;
            });

            this.ckeditor.insertHtml('<a ' + attributeString + '">' + wrappedText + ' LINK DIALOG TEST</a>');
        }

    },

    cancel: function () {
        this.window.close();
    }
};



CKEDITOR.tools.extend( CKEDITOR.config,
{
	linkShowAdvancedTab : true,
	linkShowTargetTab : true
} );
