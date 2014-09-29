/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.document.tags.areablock");
pimcore.document.tags.areablock = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {

        this.id = id;
        this.name = name;
        this.elements = [];
        this.options = this.parseOptions(options);

        this.toolbarGlobalVar = this.getType() + "toolbar";

        if(typeof this.options["toolbar"] == "undefined" || this.options["toolbar"] != false) {
            this.createToolBar();
        }

        var plusButton, minusButton, upButton, downButton, optionsButton, plusDiv, minusDiv, upDiv, downDiv, optionsDiv,
            typemenu, typeDiv, typebuttontext, editDiv, editButton;
        this.elements = Ext.get(id).query("div." + name + "[key]");

        // reload or not => default not
        if(typeof this.options["reload"] == "undefined") {
            this.options.reload = false;
        }

        // type mapping
        var typeNameMappings = {};
        this.allowedTypes = []; // this is for the toolbar to check if an brick can be dropped to this areablock
        for (var i=0; i<this.options.types.length; i++) {
            typeNameMappings[this.options.types[i].type] = {
                name: this.options.types[i].name,
                description: this.options.types[i].description,
                icon: this.options.types[i].icon
            };

            this.allowedTypes.push(this.options.types[i].type);
        }

        var limitReached = false;
        if(typeof options["limit"] != "undefined" && this.elements.length >= options.limit) {
            limitReached = true;
        }


        if (this.elements.length < 1) {
            this.createInitalControls();
        }
        else {
            for (var i = 0; i < this.elements.length; i++) {
                this.elements[i].key = this.elements[i].getAttribute("key");
                this.elements[i].type = this.elements[i].getAttribute("type");

                // edit button
                try {
                    editDiv = Ext.get(this.elements[i]).query(".pimcore_area_edit_button_" + this.name)[0];
                    if(editDiv) {
                    editButton = new Ext.Button({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_edit",
                        handler: this.editmodeOpen.bind(this, this.elements[i])
                    });
                    editButton.render(editDiv);
                    }
                } catch (e) {}

                if(!limitReached) {
                    // plus button
                    plusDiv = Ext.get(this.elements[i]).query(".pimcore_block_plus_" + this.name)[0];
                    plusButton = new Ext.Button({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_plus",
                        menu: [this.getTypeMenu(this, this.elements[i])],
                        listeners: {
                            /*"menushow": function () {
                                Ext.get(this).addClass("pimcore_tag_areablock_force_show_buttons");
                            }.bind(this.elements[i]),
                            "menuhide": function () {
                                Ext.get(this).removeClass("pimcore_tag_areablock_force_show_buttons");
                            }.bind(this.elements[i])*/
                        }
                    });
                    plusButton.render(plusDiv);
                }

                // minus button
                minusDiv = Ext.get(this.elements[i]).query(".pimcore_block_minus_" + this.name)[0];
                minusButton = new Ext.Button({
                    cls: "pimcore_block_button_minus",
                    iconCls: "pimcore_icon_minus",
                    listeners: {
                        "click": this.removeBlock.bind(this, this.elements[i])
                    }
                });
                minusButton.render(minusDiv);

                // up button
                upDiv = Ext.get(this.elements[i]).query(".pimcore_block_up_" + this.name)[0];
                upButton = new Ext.Button({
                    cls: "pimcore_block_button_up",
                    iconCls: "pimcore_icon_up",
                    listeners: {
                        "click": this.moveBlockUp.bind(this, this.elements[i])
                    }
                });
                upButton.render(upDiv);

                // down button
                downDiv = Ext.get(this.elements[i]).query(".pimcore_block_down_" + this.name)[0];
                downButton = new Ext.Button({
                    cls: "pimcore_block_button_down",
                    iconCls: "pimcore_icon_down",
                    listeners: {
                        "click": this.moveBlockDown.bind(this, this.elements[i])
                    }
                });
                downButton.render(downDiv);

                // type button
                typebuttontext = "<b>"  + this.elements[i].type + "</b>";
                if(typeNameMappings[this.elements[i].type]
                                        && typeof typeNameMappings[this.elements[i].type].name != "undefined") {
                    typebuttontext = "<b>" + typeNameMappings[this.elements[i].type].name + "</b> "
                                                + typeNameMappings[this.elements[i].type].description;
                }

                typeDiv = Ext.get(this.elements[i]).query(".pimcore_block_type_" + this.name)[0];
                var typeButton = new Ext.Button({
                    cls: "pimcore_block_button_type",
                    text: typebuttontext,
                    handleMouseEvents: false,
                    tooltip: t("drag_me"),
                    icon: "/pimcore/static/img/icon/arrow_nw_ne_sw_se.png",
                    style: "cursor: move;"
                });
                typeButton.on("afterrender", function (index, v) {

                    var element = this.elements[index];

                    v.dragZone = new Ext.dd.DragZone(v.getEl(), {
                        hasOuterHandles: true,
                        getDragData: function(e) {
                            var sourceEl = element;
                            var proxyEl = null;

                            /*if(Ext.get(element).getHeight() > 300 || Ext.get(element).getWidth() > 900) {
                                // use the button as proxy if the area itself is to big
                                proxyEl = v.getEl().dom;
                            } else {
                                proxyEl = element;
                            }*/

                            // only use the button as proxy element
                            proxyEl = v.getEl().dom;

                            if (sourceEl) {
                                var d = proxyEl.cloneNode(true);
                                d.id = Ext.id();

                                return v.dragData = {
                                    sourceEl: sourceEl,
                                    repairXY: Ext.fly(sourceEl).getXY(),
                                    ddel: d
                                };
                            }
                        },

                        onStartDrag: this.createDropZones.bind(this),
                        afterDragDrop: this.removeDropZones.bind(this),
                        afterInvalidDrop: this.removeDropZones.bind(this),

                        getRepairXY: function() {
                            return this.dragData.repairXY;
                        }
                    });
                }.bind(this, i));
                typeButton.render(typeDiv);


                // option button
                optionsDiv = Ext.get(this.elements[i]).query(".pimcore_block_options_" + this.name)[0];
                optionsButton = new Ext.Button({
                    cls: "pimcore_block_button_options",
                    iconCls: "pimcore_icon_options",
                    listeners: {
                        "click": this.optionsClickhandler.bind(this, this.elements[i])
                    }
                });
                optionsButton.render(optionsDiv);


                /*
                Ext.get(this.elements[i]).on("mouseenter", function () {
                    Ext.get(this.query(".pimcore_block_buttons")[0]).show();
                });
                Ext.get(this.elements[i]).on("mouseleave", function () {
                    Ext.get(this.query(".pimcore_block_buttons")[0]).hide();
                });
                */
            }
        }
    },

    copyToClipboard: function (element) {
        var ea;
        var areaIdentifier = {name: this.getName(), key: element.getAttribute("key")};
        var item = {
            identifier: areaIdentifier,
            type: element.getAttribute("type"),
            values: {}
        };

        // check which editables are inside this area and get the data
        for (var i = 0; i < editables.length; i++) {
            try {
                ea = editables[i];
                if (ea.getName().indexOf(areaIdentifier["name"] + areaIdentifier["key"]) > 0 && ea.getName() && !ea.getInherited()) {
                    item.values[ea.getName()] = {};
                    item.values[ea.getName()].data = ea.getValue();
                    item.values[ea.getName()].type = ea.getType();
                }
            } catch (e) { }
        }

        pimcore.globalmanager.add("areablock_clipboard", item);
    },

    optionsClickhandler: function (element, btn, e) {
        var menu = new Ext.menu.Menu();

        if(element != false) {
            menu.add(new Ext.menu.Item({
                text: t('copy'),
                iconCls: "pimcore_icon_copy",
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.copyToClipboard(element);
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('cut'),
                iconCls: "pimcore_icon_cut",
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.copyToClipboard(element);
                    this.removeBlock(element);
                }.bind(this)
            }));
        }

        if(pimcore.globalmanager.exists("areablock_clipboard")) {
            menu.add(new Ext.menu.Item({
                text: t('paste'),
                iconCls: "pimcore_icon_paste",
                handler: function (item) {
                    item.parentMenu.destroy();
                    var item = pimcore.globalmanager.get("areablock_clipboard");
                    var areaIdentifier = {name: this.getName(), key: (this.getNextKey()+1)};

                    // push the data as an object compatible to the pimcore.document.tag interface to the rest of
                    // available editables so that they get read by pimcore.document.edit.getValues()
                    Ext.iterate(item.values, function (key, value, object) {
                        editables.push({
                            getName: function () {
                                var newKey = key.replace(new RegExp(item["identifier"]["name"] + item["identifier"]["key"], "g"), areaIdentifier["name"] + areaIdentifier["key"]);
                                var tmpKey;

                                while(tmpKey != newKey) {
                                    tmpKey = newKey;
                                    newKey = newKey.replace(new RegExp(item["identifier"]["name"] + "_(.*)" + item["identifier"]["key"] + "_", "g"), areaIdentifier["name"] + "_$1" + areaIdentifier["key"] + "_");
                                }

                                return newKey;
                            },
                            getValue: function () {
                                return value["data"];
                            },
                            getInherited: function () {
                                return false;
                            },
                            getType: function () {
                                return value["type"];
                            }
                        });
                    });

                    this.addBlock(element, item.type);
                }.bind(this)
            }));
        }

        if(menu.items && menu.items.getCount()) {
            menu.showAt(e.getXY());
        }

        e.stopEvent();
    },

    setInherited: function ($super, inherited) {
        var elements = Ext.get(this.id).query(".pimcore_block_buttons_" + this.name);
        if(elements.length > 0) {
            for(var i=0; i<elements.length; i++) {
                $super(inherited, Ext.get(elements[i]));
            }
        }
    },

    createDropZones: function () {

        //Ext.get(this.id).addClass("pimcore_tag_areablock_hide_buttons");

        if(this.elements.length > 0) {
            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i]) {
                    if(i == 0) {
                        var b = Ext.DomHelper.insertBefore(this.elements[i], {
                            tag: "div",
                            index: i,
                            "class": "pimcore_area_dropzone"
                        });
                        this.addDropZoneToElement(b);
                    }
                    var a = Ext.DomHelper.insertAfter(this.elements[i], {
                        tag: "div",
                        index: i+1,
                        "class": "pimcore_area_dropzone"
                    });

                    this.addDropZoneToElement(a);
                }
            }
        } else {
            // this is only for inserting when no element is in the areablock
            var c = Ext.DomHelper.append(Ext.get(this.id), {
                tag: "div",
                index: i+1,
                "class": "pimcore_area_dropzone"
            });

            this.addDropZoneToElement(c);
        }

        // update body height on drag & drop (dnd)
        // set the body height again because adding the drop zones will usually change the height of the whole body
        pimcore.edithelpers.setBodyHeight();
    },

    addDropZoneToElement: function (el) {
        el.dropZone = new Ext.dd.DropZone(el, {

            getTargetFromEvent: function(e) {
                return el;
            },

            onNodeEnter : function(target, dd, e, data){
                Ext.fly(target).addClass('pimcore_area_dropzone_hover');
            },

            onNodeOut : function(target, dd, e, data){
                Ext.fly(target).removeClass('pimcore_area_dropzone_hover');
            },

            onNodeOver : function(target, dd, e, data){
                return Ext.dd.DropZone.prototype.dropAllowed;
            },

            onNodeDrop : function(target, dd, e, data){
                if(data.fromToolbar) {
                    this.addBlockAt(data.brick.type, target.getAttribute("index"));
                    return true;
                } else {
                    this.moveBlockTo(data.sourceEl, target.getAttribute("index"));
                    return true;
                }
            }.bind(this)
        });
    },

    removeDropZones: function () {

        //Ext.get(this.id).removeClass("pimcore_tag_areablock_hide_buttons");

        var dropZones = Ext.get(this.id).query("div.pimcore_area_dropzone");
        for(var i=0; i<dropZones.length; i++) {
            dropZones[i].dropZone.unreg();
            Ext.get(dropZones[i]).remove();
        }
    },
    
    createInitalControls: function () {
        
        var plusEl = document.createElement("div");
        plusEl.setAttribute("class", "pimcore_block_plus");

        var optionsEl = document.createElement("div");
        optionsEl.setAttribute("class", "pimcore_block_options");

        var clearEl = document.createElement("div");
        clearEl.setAttribute("class", "pimcore_block_clear");

        Ext.get(this.id).appendChild(plusEl);
        Ext.get(this.id).appendChild(optionsEl);
        Ext.get(this.id).appendChild(clearEl);

        // plus button
        var plusButton = new Ext.Button({
            cls: "pimcore_block_button_plus",
            iconCls: "pimcore_icon_plus",
            menu: [this.getTypeMenu(this, null)]
        });
        plusButton.render(plusEl);

        // plus button
        var optionsButton = new Ext.Button({
            cls: "pimcore_block_button_options",
            iconCls: "pimcore_icon_options",
            listeners: {
                click: this.optionsClickhandler.bind(this, false)
            }
        });
        optionsButton.render(optionsEl);
    },
    
    getTypeMenu: function (scope, element) {
        var menu = [];
        var groupMenu;

        if(typeof this.options.group != "undefined") {
            var groups = Object.keys(this.options.group);
            for (var g=0; g<groups.length; g++) {
                if(groups[g].length > 0) {
                    groupMenu = {
                        text: groups[g],
                        iconCls: "pimcore_icon_area",
                        hideOnClick: false,
                        menu: []
                    };

                    for (var i=0; i<this.options.types.length; i++) {
                        if(in_array(this.options.types[i].type,this.options.group[groups[g]])) {
                            groupMenu.menu.push(this.getMenuConfigForBrick(this.options.types[i], scope, element));
                        }
                    }
                    menu.push(groupMenu);
                }
            }
        } else {
            for (var i=0; i<this.options.types.length; i++) {
                menu.push(this.getMenuConfigForBrick(this.options.types[i], scope, element));
            }
        }

        return menu;
    },

    getMenuConfigForBrick: function (brick, scope, element) {

        var menuText = "<b>" + brick.name + "</b>";
        if(brick.description) {
            menuText += " | " + brick.description;
        }

        var tmpEntry = {
            text: menuText,
            iconCls: "pimcore_icon_area",
            listeners: {
                "click": this.addBlock.bind(scope, element, brick.type)
            }
        };

        if(brick.icon) {
            delete tmpEntry.iconCls;
            tmpEntry.icon = brick.icon;
        }

        return tmpEntry;
    },

    getNextKey: function () {
        var nextKey = 0;
        var currentKey;

        for (var i = 0; i < this.elements.length; i++) {
            currentKey = intval(this.elements[i].key);
            if (currentKey > nextKey) {
                nextKey = currentKey;
            }
        }

        return nextKey;
    },

    addBlock : function (element, type) {
        
        var index = this.getElementIndex(element) + 1;
        this.addBlockAt(type, index)
    },

    addBlockAt: function (type, index) {

        if(typeof this.options["limit"] != "undefined" && this.elements.length >= this.options.limit) {
            Ext.MessageBox.alert(t("error"), t("limit_reached"));
            return;
        }

        // get next heigher key
        var nextKey = this.getNextKey();
        var amount = 1;


        var args = [index, 0];

        for (var p = 0; p < amount; p++) {
            nextKey++;
            args.push({
                key: nextKey,
                type: type
            });
        }

        this.elements.splice.apply(this.elements, args);
        this.reloadDocument();
    },

    removeBlock: function (element) {

        var index = this.getElementIndex(element);

        this.elements.splice(index, 1);
        Ext.get(element).remove();

        // there is no existing block element anymore
        if (this.elements.length < 1) {
            this.createInitalControls();
        }

        // this is necessary because of the limit which is only applied when initializing
        this.reloadDocument();
    },

    moveBlockTo: function (block, toIndex) {

        //Ext.get(Ext.get(block).query(".pimcore_block_buttons")[0]).hide();

        toIndex = intval(toIndex);

        var currentIndex = this.getElementIndex(block);
        var tmpElements = [];

        for (var i = 0; i < this.elements.length; i++) {
            if (this.elements[i] && this.elements[i] != block) {
                tmpElements.push(this.elements[i]);
            }
        }

        if(currentIndex < toIndex) {
            toIndex--;
        }

        tmpElements.splice(toIndex,0,block);

        var elementAfter = tmpElements[toIndex+1];
        if(elementAfter) {
            Ext.get(block).insertBefore(elementAfter);
        } else {
            // to the last position
            Ext.get(block).insertAfter(this.elements[this.elements.length-1]);
        }

        this.elements = tmpElements;

        if(this.options.reload) {
            this.reloadDocument();
        }
    },

    moveBlockDown: function (element) {

        var index = this.getElementIndex(element);

        if (index < (this.elements.length-1)) {
            this.moveBlockTo(element, index+2);
        }
    },

    moveBlockUp: function (element) {

        var index = this.getElementIndex(element);

        if (index > 0) {
            this.moveBlockTo(element, index-1);
        }
    },

    getElementIndex: function (element) {

        try {
            var key = Ext.get(element).dom.key;
            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i].key == key) {
                    var index = i;
                    break;
                }
            }
        }
        catch (e) {
            return 0;
        }

        return index;
    },


    editmodeOpen: function (element) {

        var content = Ext.get(element).query(".pimcore_area_editmode")[0];

        this.editmodeWindow = new Ext.Window({
            modal: true,
            width: 500,
            height: 330,
            title: "Edit Block",
            closeAction: "hide",
            bodyStyle: "padding: 10px;",
            closable: false,
            autoScroll: true,
            listeners: {
                afterrender: function (content) {
                    Ext.get(content).removeClass("pimcore_area_editmode_hidden");

                    var elements = Ext.get(content).query(".pimcore_editable");
                    for (var i=0; i<elements.length; i++) {
                        var name = elements[i].getAttribute("id").split("pimcore_editable_").join("");
                        for (var e=0; e<editables.length; e++) {
                            if(editables[e].getName() == name) {
                                if(editables[e].element) {
                                    if(typeof editables[e].element.doLayout == "function") {
                                        editables[e].element.doLayout();
                                    }
                                }
                                break;
                            }
                        }
                    }

                }.bind(this, content)
            },
            buttons: [{
                text: t("save"),
                listeners: {
                    "click": this.editmodeSave.bind(this)
                },
                icon: "/pimcore/static/img/icon/tick.png"
            }],
            contentEl: content
        });
        this.editmodeWindow.show();
    },

    editmodeSave: function () {
        this.editmodeWindow.close();

        this.reloadDocument();
    },

    createToolBar: function () {
        var buttons = [];
        var button;
        var bricksInThisArea = [];
        var groupsInThisArea = {};
        var areaBlockToolbarSettings = this.options["areablock_toolbar"];
        var itemCount = 0;

        if(pimcore.document.tags[this.toolbarGlobalVar] != false
                                                && pimcore.document.tags[this.toolbarGlobalVar].itemCount) {
            itemCount = pimcore.document.tags[this.toolbarGlobalVar].itemCount;
        }

        if(typeof this.options.group != "undefined") {
            var groupMenu;
            var groupItemCount = 0;
            var isExistingGroup;
            var brickKey;
            var groups = Object.keys(this.options.group);

            for (var g=0; g<groups.length; g++) {
                groupMenu = null;
                isExistingGroup = false;
                if(groups[g].length > 0) {

                    if(pimcore.document.tags[this.toolbarGlobalVar] != false) {
                        if(pimcore.document.tags[this.toolbarGlobalVar]["groups"][groups[g]]) {
                            groupMenu = pimcore.document.tags[this.toolbarGlobalVar]["groups"][groups[g]];
                            isExistingGroup = true;
                        }
                    }

                    if(!groupMenu) {
                        groupMenu = new Ext.Button({
                            xtype: "button",
                            text: groups[g],
                            iconCls: "pimcore_icon_area",
                            hideOnClick: false,
                            width: areaBlockToolbarSettings.buttonWidth,
                            menu: []
                        });
                    }

                    groupsInThisArea[groups[g]] = groupMenu;

                    for (var i=0; i<this.options.types.length; i++) {
                        if(in_array(this.options.types[i].type,this.options.group[groups[g]])) {
                            itemCount++;
                            brickKey = groups[g] + " - " + this.options.types[i].type;
                            button = this.getToolBarButton(this.options.types[i], brickKey, itemCount, "menu");
                            if(button) {
                                bricksInThisArea.push(brickKey);
                                groupMenu.menu.add(button);
                                groupItemCount++;
                            }
                        }
                    }

                    if(!isExistingGroup && groupItemCount > 0) {
                        buttons.push(groupMenu);
                    }
                }
            }
        } else {
            for (var i=0; i<this.options.types.length; i++) {
                var brick = this.options.types[i];
                itemCount++;

                brickKey = brick.type;
                button = this.getToolBarButton(brick, brickKey, itemCount);
                if(button) {
                    bricksInThisArea.push(brickKey);
                    buttons.push(button);
                }
            }
        }

        // only initialize the toolbar once, even when there are more than one area on the page
        if(pimcore.document.tags[this.toolbarGlobalVar] == false) {

            var x = areaBlockToolbarSettings["x"];
            if(areaBlockToolbarSettings["xAlign"] == "right") {
                x = Ext.getBody().getWidth()-areaBlockToolbarSettings["x"]-areaBlockToolbarSettings["width"];
            }

            var toolbar = new Ext.Window({
                title: areaBlockToolbarSettings.title,
                width: areaBlockToolbarSettings.width,
                border:false,
                shadow: false,
                resizable: false,
                autoHeight: true,
                style: "position:fixed;",
                collapsible: true,
                cls: "pimcore_areablock_toolbar",
                closable: false,
                x: x,
                y: areaBlockToolbarSettings["y"],
                items: [buttons],
                listeners: {
                    move: function (win, x, y) {
                        var scroll = Ext.getBody().getScroll();
                        win.getEl().setStyle("top", y - scroll.top + "px");
                        win.getEl().setStyle("left", x - scroll.left + "px");
                    }
                }
            });

            toolbar.show();

            pimcore.document.tags[this.toolbarGlobalVar] = {
                toolbar: toolbar,
                groups: groupsInThisArea,
                bricks: bricksInThisArea,
                areablocks: [this],
                itemCount: buttons.length
            };
        } else {
            pimcore.document.tags[this.toolbarGlobalVar].toolbar.add(buttons);
            pimcore.document.tags[this.toolbarGlobalVar].bricks =
                                    array_merge(pimcore.document.tags[this.toolbarGlobalVar].bricks, bricksInThisArea);
            pimcore.document.tags[this.toolbarGlobalVar].groups =
                                    array_merge(pimcore.document.tags[this.toolbarGlobalVar].groups, groupsInThisArea);
            pimcore.document.tags[this.toolbarGlobalVar].itemCount += buttons.length;
            pimcore.document.tags[this.toolbarGlobalVar].areablocks.push(this);
            pimcore.document.tags[this.toolbarGlobalVar].toolbar.doLayout();
        }

    },

    getToolBarButton: function (brick, key, itemCount, type) {

        if(pimcore.document.tags[this.toolbarGlobalVar] != false) {
            if(in_array(key, pimcore.document.tags[this.toolbarGlobalVar].bricks)) {
                return;
            }
        }

        var areaBlockToolbarSettings = this.options["areablock_toolbar"];
        var maxButtonCharacters = areaBlockToolbarSettings.buttonMaxCharacters;
        // this contains fallback-icons
        var iconStore = ["flag_black","flag_blue","flag_checked","flag_france","flag_green","flag_grey",
            "flag_orange","flag_pink","flag_purple","flag_red","flag_white","flag_yellow",
            "award_star_bronze_1","award_star_bronze_2","award_star_bronze_3","award_star_gold_1",
            "award_star_gold_1","award_star_gold_1","award_star_silver_1","award_star_silver_2",
            "award_star_silver_3","medal_bronze_1","medal_bronze_2","medal_bronze_3","medal_gold_1",
            "medal_gold_1","medal_gold_1","medal_silver_1","medal_silver_2","medal_silver_3"];


        if(!brick.icon) {
            brick.icon = "/pimcore/static/img/icon/" + iconStore[itemCount] + ".png";
        }

        var button = {
            xtype: "button",
            icon: brick.icon,
            text: brick.name.length > maxButtonCharacters ? brick.name.substr(0,maxButtonCharacters) + "..."
                : brick.name,
            width: areaBlockToolbarSettings.buttonWidth,
            listeners: {
                "afterrender": function (brick, v) {

                    v.dragZone = new Ext.dd.DragZone(v.getEl(), {
                        getDragData: function(e) {
                            var sourceEl = v.getEl().dom;
                            if (sourceEl) {
                                d = sourceEl.cloneNode(true);
                                d.id = Ext.id();
                                return v.dragData = {
                                    sourceEl: sourceEl,
                                    repairXY: Ext.fly(sourceEl).getXY(),
                                    ddel: d,
                                    fromToolbar: true,
                                    brick: brick
                                }
                            }
                        },

                        onStartDrag: function () {
                            var areablocks = pimcore.document.tags[this.toolbarGlobalVar].areablocks;
                            for(var i=0; i<areablocks.length; i++) {
                                if(in_array(brick.type, areablocks[i].allowedTypes)) {
                                    areablocks[i].createDropZones();
                                }
                            }
                        }.bind(this),
                        afterDragDrop: function () {
                            var areablocks = pimcore.document.tags[this.toolbarGlobalVar].areablocks;
                            for(var i=0; i<areablocks.length; i++) {
                                areablocks[i].removeDropZones();
                            }
                        }.bind(this),
                        afterInvalidDrop: function () {
                            var areablocks = pimcore.document.tags[this.toolbarGlobalVar].areablocks;
                            for(var i=0; i<areablocks.length; i++) {
                                areablocks[i].removeDropZones();
                            }
                        }.bind(this),

                        getRepairXY: function() {
                            return this.dragData.repairXY;
                        }
                    });
                }.bind(this, brick)
            }
        };

        if(brick.description) {
            button["tooltip"] = brick.description;
        }

        if(type == "menu") {
            delete button["width"];
            delete button["xtype"];
            button["text"] = brick.name;// not shortened
        }

        return button;
    },

    getValue: function () {
        var data = [];
        for (var i = 0; i < this.elements.length; i++) {
            if (this.elements[i]) {
                if (this.elements[i].key) {
                    data.push({
                        key: this.elements[i].key,
                        type: this.elements[i].type
                    });
                }
            }
        }

        return data;
    },

    getType: function () {
        return "areablock";
    }
});

pimcore.document.tags.areablocktoolbar = false;
