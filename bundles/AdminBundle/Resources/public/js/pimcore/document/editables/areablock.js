/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.editables.areablock");
pimcore.document.editables.areablock = Class.create(pimcore.document.area_abstract, {

    dialogBoxes: {},

    initialize: function(id, name, config, data, inherited) {

        this.initalConfig = config;
        this.id = id;
        this.name = name;
        this.elements = [];
        this.config = this.parseConfig(config);
        this.toolbarGlobalVar = this.getType() + "toolbar";
        this.applyFallbackIcons();

        if(typeof this.config["toolbar"] == "undefined" || this.config["toolbar"] != false) {
            this.createToolBar();
        }

        this.visibilityButtons = {};

        // reload or not => default not
        if(typeof this.config["reload"] == "undefined") {
            this.config.reload = false;
        }

        if(!this.config['controlsTrigger']) {
            this.config['controlsTrigger'] = 'hover';
        }

        // type mapping
        this.typeNameMappings = {};
        this.allowedTypes = []; // this is for the toolbar to check if an brick can be dropped to this areablock
        for (var i=0; i<this.config.types.length; i++) {
            this.typeNameMappings[this.config.types[i].type] = {
                name: this.config.types[i].name,
                description: this.config.types[i].description,
                icon: this.config.types[i].icon
            };

            this.allowedTypes.push(this.config.types[i].type);
        }

        // click outside, hide all block buttons
        if(this.config['controlsTrigger'] === 'hover') {
            Ext.getBody().on('click', function (event) {
                if (Ext.get(id) && !Ext.get(id).isAncestor(event.target)) {
                    Ext.get(id).query('.pimcore_area_buttons', false).forEach(function (el) {
                        el.hide();
                    });
                }
            });
        }
    },

    refresh: function() {
        var plusButton, minusButton, upButton, downButton, optionsButton, plusDiv, minusDiv, upDiv, downDiv, optionsDiv,
            typeDiv, typeButton, labelText, editDiv, editButton, visibilityDiv, labelDiv, plusUpDiv, plusUpButton,
            dialogBoxDiv, dialogBoxButton;

        this.elements = Ext.get(this.id).query('.pimcore_block_entry[data-name="' + this.name + '"][key]');


        this.brickTypeUsageCounter = [];
        var limitReached = false;
        if(this.config["limit"] && this.elements.length >= this.config.limit) {
            limitReached = true;
        }


        if (this.elements.length < 1) {
            this.createInitalControls();
        }
        else {
            var hideTimeout, activeBlockEl;

            for (var i = 0; i < this.elements.length; i++) {

                this.elements[i].type = this.elements[i].getAttribute("type");
                this.brickTypeUsageCounter[this.elements[i].type] = this.brickTypeUsageCounter[this.elements[i].type]+1 || 1;

                if(this.elements[i].key) {
                    continue;
                }

                this.elements[i].key = this.elements[i].getAttribute("key");

                if(!limitReached) {
                    // plus buttons
                    plusUpDiv = Ext.get(this.elements[i]).query('.pimcore_block_plus_up[data-name="' + this.name + '"]')[0];
                    plusUpButton = new Ext.Button({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_plus_up",
                        arrowVisible: false,
                        menu: this.getTypeMenu(this, this.elements[i], "before")
                    });
                    plusUpButton.render(plusUpDiv);

                    plusDiv = Ext.get(this.elements[i]).query('.pimcore_block_plus[data-name="' + this.name + '"]')[0];
                    plusButton = new Ext.Button({
                        cls: "pimcore_block_button_plus",
                        iconCls: "pimcore_icon_plus_down",
                        arrowVisible: false,
                        menu: this.getTypeMenu(this, this.elements[i], "after")
                    });
                    plusButton.render(plusDiv);
                }

                // minus button
                minusDiv = Ext.get(this.elements[i]).query('.pimcore_block_minus[data-name="' + this.name + '"]')[0];
                minusButton = new Ext.Button({
                    cls: "pimcore_block_button_minus",
                    iconCls: "pimcore_icon_minus",
                    listeners: {
                        "click": this.removeBlock.bind(this, this.elements[i])
                    }
                });
                minusButton.render(minusDiv);

                // up button
                upDiv = Ext.get(this.elements[i]).query('.pimcore_block_up[data-name="' + this.name + '"]')[0];
                upButton = new Ext.Button({
                    cls: "pimcore_block_button_up",
                    iconCls: "pimcore_icon_white_up",
                    listeners: {
                        "click": this.moveBlockUp.bind(this, this.elements[i])
                    }
                });
                upButton.render(upDiv);

                // down button
                downDiv = Ext.get(this.elements[i]).query('.pimcore_block_down[data-name="' + this.name + '"]')[0];
                downButton = new Ext.Button({
                    cls: "pimcore_block_button_down",
                    iconCls: "pimcore_icon_white_down",
                    listeners: {
                        "click": this.moveBlockDown.bind(this, this.elements[i])
                    }
                });
                downButton.render(downDiv);

                typeDiv = Ext.get(this.elements[i]).query('.pimcore_block_type[data-name="' + this.name + '"]')[0];
                typeButton = new Ext.Button({
                    cls: "pimcore_block_button_type",
                    handleMouseEvents: false,
                    tooltip: t("drag_me"),
                    iconCls: "pimcore_icon_white_move",
                    style: "cursor: move;"
                });
                typeButton.on("afterrender", function (index, v) {

                    var element = this.elements[index];

                    v.dragZone = new Ext.dd.DragZone(v.getEl(), {
                        hasOuterHandles: true,
                        getDragData: function(e) {
                            var sourceEl = element;

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

                        onStartDrag: this.startDragDrop.bind(this),
                        afterDragDrop: this.endDragDrop.bind(this),
                        afterInvalidDrop: this.endDragDrop.bind(this),
                        beforeDragOut: function (target) {
                            return target ? true : false;
                        },
                        getRepairXY: function() {
                            return this.dragData.repairXY;
                        }
                    });
                }.bind(this, i));
                typeButton.render(typeDiv);


                // option button
                optionsDiv = Ext.get(this.elements[i]).query('.pimcore_block_options[data-name="' + this.name + '"]')[0];
                optionsButton = new Ext.Button({
                    cls: "pimcore_block_button_options",
                    iconCls: "pimcore_icon_white_copy",
                    listeners: {
                        "click": this.optionsClickhandler.bind(this, this.elements[i])
                    }
                });
                optionsButton.render(optionsDiv);

                visibilityDiv = Ext.get(this.elements[i]).query('.pimcore_block_visibility[data-name="' + this.name + '"]')[0];
                this.visibilityButtons[this.elements[i].key] = new Ext.Button({
                    cls: "pimcore_block_button_visibility",
                    iconCls: "pimcore_icon_white_hide",
                    enableToggle: true,
                    pressed: (this.elements[i].dataset.hidden == "true"),
                    toggleHandler: function (index, el) {
                        Ext.get(this.elements[index]).toggleCls('pimcore_area_hidden');
                    }.bind(this, i)
                });
                this.visibilityButtons[this.elements[i].key].render(visibilityDiv);
                if(this.elements[i].dataset.hidden == "true") {
                    Ext.get(this.elements[i]).addCls('pimcore_area_hidden');
                }


                dialogBoxDiv = Ext.get(this.elements[i]).query('.pimcore_block_dialog[data-name="' + this.name + '"]')[0];
                if(dialogBoxDiv) {
                    dialogBoxButton = new Ext.Button({
                        cls: "pimcore_block_button_dialog",
                        iconCls: "pimcore_icon_white_edit",
                        listeners: {
                            "click": this.openEditableDialogBox.bind(this, this.elements[i], dialogBoxDiv)
                        }
                    });
                    dialogBoxButton.render(dialogBoxDiv);
                }

                labelDiv = Ext.get(Ext.get(this.elements[i]).query('.pimcore_block_label[data-name="' + this.name + '"]')[0]);
                labelText = "<b>"  + this.elements[i].type + "</b>";
                if(this.typeNameMappings[this.elements[i].type]
                    && typeof this.typeNameMappings[this.elements[i].type].name != "undefined") {
                    labelText = "<b>" + this.typeNameMappings[this.elements[i].type].name + "</b> "
                        + this.typeNameMappings[this.elements[i].type].description;
                }
                labelDiv.setHtml(labelText);


                var buttonContainer = Ext.get(this.elements[i]).selectNode('.pimcore_area_buttons', false);
                if (this.config['controlsAlign']) {
                    buttonContainer.addCls(this.config['controlsAlign']);
                } else {
                    // top is default
                    buttonContainer.addCls('top');
                }

                buttonContainer.addCls(this.config['controlsTrigger']);

                if(this.config['controlsTrigger'] === 'hover') {
                    Ext.get(this.elements[i]).on('mouseenter', function (event) {

                        if (Ext.dd.DragDropMgr.dragCurrent) {
                            return;
                        }

                        if (hideTimeout) {
                            window.clearTimeout(hideTimeout);
                        }

                        Ext.get(this.id).query('.pimcore_area_buttons', false).forEach(function (el) {
                            if (event.target != el.dom) {
                                el.hide();
                            }
                        });

                        var buttonContainer = Ext.get(event.target).selectNode('.pimcore_area_buttons', false);
                        buttonContainer.show();

                        if (activeBlockEl != event.target) {
                            Ext.menu.Manager.hideAll();
                        }
                        activeBlockEl = event.target;
                    }.bind(this));

                    Ext.get(this.elements[i]).on('mouseleave', function (event) {
                        hideTimeout = window.setTimeout(function () {
                            Ext.get(event.target).selectNode('.pimcore_area_buttons', false).hide();
                            hideTimeout = null;
                        }, 10000);
                    });
                }
            }
        }

        this.updateDropZones();
    },

    render: function () {
        this.refresh();
    },

    applyFallbackIcons: function() {
        // this contains fallback-icons
        var iconStore = ["circuit","display","biomass","deployment","electrical_sensor","dam",
            "light_at_the_end_of_tunnel","like","icons8_cup","sports_mode","landscape","selfie","cable_release",
            "bookmark","briefcase","graduation_cap","in_transit","diploma_2","circuit","display","biomass","deployment",
            "electrical_sensor","dam",
            "light_at_the_end_of_tunnel","like","icons8_cup","sports_mode","landscape","selfie","cable_release",
            "bookmark","briefcase","graduation_cap","in_transit","diploma_2"];

        if (this.config.types) {
            for (var i = 0; i < this.config.types.length; i++) {

                var brick = this.config.types[i];

                if (!brick.icon) {
                    brick.icon = "/bundles/pimcoreadmin/img/flat-color-icons/" + iconStore[i + 1] + ".svg";
                }
            }
        }
    },

    copyToClipboard: function (element) {

        var areaIdentifier = {
            name: this.getName(),
            realName: this.getRealName(),
            key: element.getAttribute("key")
        };

        var item = {
            identifier: areaIdentifier,
            type: element.getAttribute("type"),
            values: {}
        };

        // check which editables are inside this area and get the data
        Object.values(editableManager.getEditables()).forEach(editable => {
            try {
                if (!editable.getName()) {
                    return;
                }

                var editableData = this.copyData(areaIdentifier, editable);
                if (editableData) {
                    item.values[editable.getName()] = editableData;
                }
            } catch (e) {
                console.error(e);
            }
        });

        pimcore.globalmanager.add("areablock_clipboard", Ext.encode(item));
    },

    copyData: function (areaIdentifier, editable) {
        var areaBaseName = areaIdentifier.name + ':' + areaIdentifier.key + '.';

        if (editable.getName().indexOf(areaBaseName) !== 0) {
            return false; // editable is not inside area
        }

        // remove common base name (= parent area identifier) from relative name
        var relativeName = editable.getName().replace(new RegExp('^' + areaBaseName), '');
        var itemParts = relativeName.split('.');

        // last part is the real name
        itemParts.pop();

        var parents = [];
        if (itemParts.length > 0) {
            Ext.each(itemParts, function(parent) {
                var parentParts = parent.split(':');
                var parentEntry = {
                    name: parentParts[0],
                    key: null
                };

                if (parentParts.length > 1) {
                    parentEntry.key = parentParts[1];
                }

                parents.push(parentEntry);
            });
        }

        parents = parents || [];

        return {
            name: editable.getName(),
            realName: editable.getRealName(),
            data: editable.getValue(),
            type: editable.getType(),
            parents: parents
        };
    },

    getPasteName: function(areaIdentifier, item, editableData) {
        var editableName;

        // base name is area identifier + key
        var editableParts = [
            areaIdentifier.name + ':' + areaIdentifier.key
        ];

        // add relative parent paths as parsed when copying
        if (editableData.parents.length > 0) {
            Ext.each(editableData.parents, function (parentEntry) {
                var pathPart = parentEntry.name;
                if (null !== parentEntry.key) {
                    pathPart += ':' + parentEntry.key;
                }

                editableParts.push(pathPart);
            });
        }

        // add the real name as last part
        editableParts.push(editableData.realName);

        // join parts together with .
        editableName = editableParts.join('.');

        return editableName;
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
                    item = pimcore.globalmanager.get("areablock_clipboard");
                    /*
                    This occurs for the following reason: properties of object.prototype like toString()
                    and hasOwnProperty directly linked to window in which object was created
                     */
                    item = Ext.decode(item);

                    var areaIdentifier = {
                        name: this.getName(),
                        key: (this.getNextKey() + 1)
                    };

                    var that = this;

                    // push the data as an object compatible to the pimcore.document.editable interface to the rest of
                    // available editables so that they get read by pimcore.document.edit.getValues()
                    Ext.iterate(item.values, function (key, value, object) {
                        var editableName = that.getPasteName(areaIdentifier, item, value);

                        editableManager.add({
                            getName: function () {
                                return editableName;
                            },
                            getRealName: function() {
                                return value.realName;
                            },
                            getValue: function () {
                                return value.data;
                            },
                            getInherited: function () {
                                return false;
                            },
                            getType: function () {
                                return value.type;
                            }
                        });
                    });

                    this.addBlockAfter(element, item.type, true);
                    this.reloadDocument();
                }.bind(this)
            }));
        }

        if(!menu.items || !menu.items.getCount()) {
            menu.add(new Ext.menu.Item({
                text: t('no_action_available')
            }));
        }

        menu.showAt(e.getXY());
        e.stopEvent();
    },

    setInherited: function ($super, inherited) {
        var elements = Ext.get(this.id).query('.pimcore_area_buttons[data-name="' + this.name + '"]');
        if(elements.length > 0) {
            for(var i=0; i<elements.length; i++) {
                $super(inherited, Ext.get(elements[i]));
            }
        }
    },

    startDragDrop: function () {
        Ext.getBody().addCls('pimcore_drag_drop_active');
        Ext.get(this.id).addCls('pimcore_editable_areablock_dropzones_active');
    },

    endDragDrop: function () {
        Ext.getBody().removeCls('pimcore_drag_drop_active');
        Ext.get(this.id).removeCls('pimcore_editable_areablock_dropzones_active');
    },

    updateDropZones: function () {

        if(this.inherited) {
            return;
        }

        var dropZones = Ext.get(this.id).query("div.pimcore_area_dropzone");
        for(var i=0; i<dropZones.length; i++) {
            dropZones[i].dropZone.unreg();
            Ext.get(dropZones[i]).remove();
        }

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
    },

    addDropZoneToElement: function (el) {
        el.dropZone = new Ext.dd.DropZone(el, {

            getTargetFromEvent: function(e) {
                return el;
            },

            onNodeEnter : function(target, dd, e, data){
                Ext.fly(target).addCls('pimcore_area_dropzone_hover');
            },

            onNodeOut : function(target, dd, e, data){
                Ext.fly(target).removeCls('pimcore_area_dropzone_hover');
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

    createInitalControls: function () {

        var plusEl = document.createElement("div");
        plusEl.setAttribute("class", "pimcore_block_plus");
        plusEl.setAttribute("data-name", this.name);

        var optionsEl = document.createElement("div");
        optionsEl.setAttribute("class", "pimcore_block_options");
        optionsEl.setAttribute("data-name", this.name);

        var clearEl = document.createElement("div");
        clearEl.setAttribute("class", "pimcore_block_clear");
        clearEl.setAttribute("data-name", this.name);

        Ext.get(this.id).appendChild(plusEl);
        Ext.get(this.id).appendChild(optionsEl);
        Ext.get(this.id).appendChild(clearEl);

        // plus button
        var plusButton = new Ext.Button({
            cls: "pimcore_block_button_plus",
            arrowVisible: false,
            iconCls: "pimcore_icon_plus",
            menu: this.getTypeMenu(this, null)
        });
        plusButton.render(plusEl);

        // options button
        var optionsButton = new Ext.Button({
            cls: "pimcore_block_button_options",
            iconCls: "pimcore_icon_white_copy",
            listeners: {
                click: this.optionsClickhandler.bind(this, false)
            }
        });
        optionsButton.render(optionsEl);
    },

    getTypeMenu: function (scope, element, insertPosition) {
        var menu = [];
        var groupMenu;
        var limits = this.config["limits"] || {};

        if(typeof this.config.group != "undefined") {
            var groups = Object.keys(this.config.group);
            for (var g=0; g<groups.length; g++) {
                if(groups[g].length > 0) {
                    groupMenu = {
                        text: groups[g],
                        hideOnClick: false,
                        menu: []
                    };

                    for (var i=0; i<this.config.types.length; i++) {
                        if(in_array(this.config.types[i].type,this.config.group[groups[g]])) {
                            let type = this.config.types[i].type;
                            if (typeof limits[type] == "undefined" ||
                                typeof this.brickTypeUsageCounter[type] == "undefined" || this.brickTypeUsageCounter[type] < limits[type]) {
                                    groupMenu.menu.push(this.getMenuConfigForBrick(this.config.types[i], scope, element, insertPosition));
                            }
                        }
                    }
                    menu.push(groupMenu);
                }
            }
        } else {
            for (var i=0; i<this.config.types.length; i++) {
                let type = this.config.types[i].type;
                if (typeof limits[type] == "undefined" ||
                    typeof this.brickTypeUsageCounter[type] == "undefined" || this.brickTypeUsageCounter[type] < limits[type]) {
                    menu.push(this.getMenuConfigForBrick(this.config.types[i], scope, element, insertPosition));
                }
            }
        }

        return menu;
    },

    getMenuConfigForBrick: function (brick, scope, element, insertPosition) {

        var menuText = brick.name;
        if(brick.description) {
            menuText += " | " + brick.description;
        }

        if(!insertPosition) {
            insertPosition = 'after';
        }

        var addBLockFunction = "addBlock" + ucfirst(insertPosition);

        var tmpEntry = {
            text: menuText,
            iconCls: "pimcore_icon_area",
            listeners: {
                "click": this[addBLockFunction].bind(scope, element, brick.type)
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

    addBlockAfter : function (element, type, forceReload) {
        var index = this.getElementIndex(element) + 1;

        this.addBlockAt(type, index, forceReload);
    },

    addBlockBefore : function (element, type) {
        var index = this.getElementIndex(element);
        this.addBlockAt(type, index);
    },

    addBlockAt: function (type, index, forceReload) {
        var limits = this.config["limits"] || {};
        if (!this.elements.length) {
            index = 0;
        }

        if(typeof this.config["limit"] != "undefined" && this.elements.length >= this.config.limit) {
            Ext.MessageBox.alert(t("error"), t("limit_reached"));
            return;
        }

        let brickName = type;
        let brickIndex = this.allowedTypes.indexOf(brickName);

        if(typeof limits[type] != "undefined" && this.brickTypeUsageCounter[type] >= limits[type]) {
            if (brickIndex >= 0 && typeof this.config.types[brickIndex].name != "undefined") {
                brickName = this.config.types[brickIndex].name;
            }
            Ext.MessageBox.alert(t("error"), t("brick_limit_reached", null ,{bricklimit: limits[type], brickname: brickName}));
            return;
        }

        var nextKey = this.getNextKey();
        nextKey++;

        if(this.config.types[brickIndex]['needsReload'] || forceReload === true) {
            editWindow.lastScrollposition = '#' + this.id + ' .pimcore_block_entry[data-name="' + this.name + '"][key="' + nextKey + '"]';

            this.elements.splice.apply(this.elements, [index, 0, {
                key: nextKey,
                type: type
            }]);

            this.reloadDocument();
        } else {
            let saveData = this.getValue();
            saveData.splice.apply(saveData, [index, 0, {
                key: nextKey,
                type: type
            }]);

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_document_page_areabrick-render-index-editmode'),
                method: 'post',
                params: {
                    documentId: window.editWindow.document.id,
                    name: this.getName(),
                    realName: this.getRealName(),
                    index: index,
                    blockStateStack: this.config['blockStateStack'],
                    areablockConfig: Ext.encode(this.initalConfig),
                    areablockData: Ext.encode(saveData)
                },
                success: function (response) {
                    let res = Ext.decode(response.responseText);
                    if(!this.elements.length) {
                        Ext.get(this.id).setHtml(res['htmlCode']);
                    } else if (this.elements[index-1]) {
                        Ext.get(this.elements[index-1]).insertHtml('afterEnd', res['htmlCode'], true);
                    } else if (this.elements[index]) {
                        Ext.get(this.elements[index]).insertHtml('beforeBegin', res['htmlCode'], true);
                    }

                    res['editableDefinitions'].forEach(editableDef => {
                        editableManager.addByDefinition(editableDef);
                    });

                    this.refresh();

                }.bind(this)
            });
        }
    },

    removeBlock: function (element) {
        let container = Ext.get(element);
        let editablesContainer = container.query('[data-block-names]');
        editablesContainer.forEach(editableDiv => {
            editableManager.remove(editableDiv.dataset.name);
        });

        container.remove();

        this.refresh();
    },

    moveBlockTo: function (block, toIndex) {
        toIndex = intval(toIndex);
        if(this.elements[toIndex]) {
            Ext.get(block).insertBefore(this.elements[toIndex]);
        } else {
            // to the last position
            Ext.get(block).insertAfter(this.elements[this.elements.length-1]);
        }

        this.refresh();

        if(this.config.reload) {
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



    createToolBar: function () {
        var buttons = [];
        var button;
        var bricksInThisArea = [];
        var groupsInThisArea = {};
        var areaBlockToolbarSettings = this.config["areablock_toolbar"];
        var itemCount = 0;

        if(pimcore.document.editables[this.toolbarGlobalVar] != false
                                                && pimcore.document.editables[this.toolbarGlobalVar].itemCount) {
            itemCount = pimcore.document.editables[this.toolbarGlobalVar].itemCount;
        }

        if(typeof this.config.group != "undefined") {
            var groupMenu;
            var groupItemCount = 0;
            var isExistingGroup;
            var brickKey;
            var groups = Object.keys(this.config.group);

            for (var g=0; g<groups.length; g++) {
                groupMenu = null;
                isExistingGroup = false;
                if(groups[g].length > 0) {

                    if(pimcore.document.editables[this.toolbarGlobalVar] != false) {
                        if(pimcore.document.editables[this.toolbarGlobalVar]["groups"][groups[g]]) {
                            groupMenu = pimcore.document.editables[this.toolbarGlobalVar]["groups"][groups[g]];
                            isExistingGroup = true;
                        }
                    }

                    if(!groupMenu) {
                        groupMenu = new Ext.Button({
                            xtype: "button",
                            text: groups[g],
                            textAlign: "left",
                            hideOnClick: false,
                            width: areaBlockToolbarSettings.buttonWidth,
                            menu: [],
                            menuAlign: 'tl-tr',
                            listeners: {
                                mouseover: function (el) {
                                    el.showMenu();
                                }
                            }
                        });
                    }

                    groupsInThisArea[groups[g]] = groupMenu;

                    for (var i=0; i<this.config.types.length; i++) {
                        if(in_array(this.config.types[i].type,this.config.group[groups[g]])) {
                            itemCount++;
                            brickKey = groups[g] + " - " + this.config.types[i].type;
                            button = this.getToolBarButton(this.config.types[i], brickKey, itemCount, "menu");
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
            for (var i=0; i<this.config.types.length; i++) {
                var brick = this.config.types[i];
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
        if(pimcore.document.editables[this.toolbarGlobalVar] == false) {

            var toolbar = new Ext.Window({
                title: areaBlockToolbarSettings.title,
                width: areaBlockToolbarSettings.width,
                border:false,
                shadow: false,
                resizable: false,
                autoHeight: true,
                draggable: false,
                header: false,
                style: "position:fixed;",
                collapsible: false,
                cls: "pimcore_areablock_toolbar",
                closable: false,
                x: -1000,
                y: 1,
                items: buttons
            });

            toolbar.show();

            pimcore.document.editables[this.toolbarGlobalVar] = {
                toolbar: toolbar,
                groups: groupsInThisArea,
                bricks: bricksInThisArea,
                areablocks: [this],
                itemCount: buttons.length
            };

            window.editWindow.areaToolbarTrigger.show();
            window.editWindow.areaToolbarTrigger.areaToolbarElement = toolbar;

            // click outside, hide toolbar
            Ext.getBody().on('click', function (event) {
                if(!toolbar.getEl().isAncestor(event.target)) {
                    window.editWindow.areaToolbarTrigger.toggle(false);
                    toolbar.setLocalX(-1000);
                }
            });
        } else {
            pimcore.document.editables[this.toolbarGlobalVar].toolbar.add(buttons);
            pimcore.document.editables[this.toolbarGlobalVar].bricks =
                                    array_merge(pimcore.document.editables[this.toolbarGlobalVar].bricks, bricksInThisArea);
            pimcore.document.editables[this.toolbarGlobalVar].groups =
                                    array_merge(pimcore.document.editables[this.toolbarGlobalVar].groups, groupsInThisArea);
            pimcore.document.editables[this.toolbarGlobalVar].itemCount += buttons.length;
            pimcore.document.editables[this.toolbarGlobalVar].areablocks.push(this);
            pimcore.document.editables[this.toolbarGlobalVar].toolbar.updateLayout();
        }

    },

    getToolBarButton: function (brick, key, itemCount, type) {

        if(pimcore.document.editables[this.toolbarGlobalVar] != false) {
            if(in_array(key, pimcore.document.editables[this.toolbarGlobalVar].bricks)) {
                return;
            }
        }

        var areaBlockToolbarSettings = this.config["areablock_toolbar"];
        var maxButtonCharacters = areaBlockToolbarSettings.buttonMaxCharacters;

        var button = {
            xtype: "button",
            textAlign: "left",
            icon: brick.icon,
            cls: 'pimcore_cursor_move',
            text: brick.name.length > maxButtonCharacters ? brick.name.substr(0,maxButtonCharacters) + "..."
                : brick.name,
            width: areaBlockToolbarSettings.buttonWidth,
            handler: function () {
                Ext.MessageBox.alert(t("info"), t("area_brick_assign_info_message"));
            },
            listeners: {
                "afterrender": function (brick, v) {

                    let menuLink = v.getEl().down('a', true);
                    if(menuLink) {
                        // the menu item has a <a> tag, with href=#, which causes dnd to not work properly
                        // and also shows the link target next to the mouse pointer while dragging
                        // -> removing the href attribute fixes the issue
                        menuLink.removeAttribute('href');
                    }

                    v.dragZone = new Ext.dd.DragZone(v.getEl(), {
                        getDragData: function(e) {
                            var sourceEl = v.getEl().dom;
                            if (sourceEl) {
                                var d = sourceEl.cloneNode(true);
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

                            // hide control bars
                            Ext.get(this.id).query('.pimcore_area_buttons', false).forEach(function (el) {
                                el.hide();
                            });

                            // create drop zones
                            var areablocks = pimcore.document.editables[this.toolbarGlobalVar].areablocks;
                            for(var i=0; i<areablocks.length; i++) {
                                if(in_array(brick.type, areablocks[i].allowedTypes)) {
                                    areablocks[i].startDragDrop();
                                }
                            }
                        }.bind(this),
                        afterDragDrop: function () {
                            var areablocks = pimcore.document.editables[this.toolbarGlobalVar].areablocks;
                            for(var i=0; i<areablocks.length; i++) {
                                areablocks[i].endDragDrop();
                            }

                            Ext.menu.Manager.hideAll();
                        }.bind(this),
                        beforeDragOut: function (target) {
                            return target ? true : false;
                        },
                        afterInvalidDrop: function () {
                            var areablocks = pimcore.document.editables[this.toolbarGlobalVar].areablocks;
                            for(var i=0; i<areablocks.length; i++) {
                                areablocks[i].endDragDrop();
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
        var hidden = false;
        for (var i = 0; i < this.elements.length; i++) {
            if (this.elements[i]) {
                if (this.elements[i].key) {
                    hidden = false;
                    if(this.visibilityButtons[this.elements[i].key]) {
                        hidden = this.visibilityButtons[this.elements[i].key].pressed;
                    }

                    data.push({
                        key: this.elements[i].key,
                        type: this.elements[i].type,
                        hidden: hidden
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

pimcore.document.editables.areablocktoolbar = false;
