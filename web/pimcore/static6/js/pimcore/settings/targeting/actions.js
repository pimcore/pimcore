/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

/* ACTION TYPES */

pimcore.registerNS("pimcore.settings.targeting.actions");
pimcore.settings.targeting.actions = (function () {
    var actions = {
        redirect: Class.create(pimcore.settings.targeting.action.abstract, {
            getName: function () {
                return t("redirect");
            },

            getPanel: function (panel, data) {
                var id = Ext.id();

                return new Ext.form.FormPanel({
                    id: id,
                    forceLayout: true,
                    style: "margin: 10px 0 0 0",
                    bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
                    tbar: pimcore.settings.targeting.actions.getTopBar(this, id, panel),
                    items: [
                        {
                            xtype: "textfield",
                            width: 450,
                            fieldLabel: "URL",
                            name: "url",
                            value: data.url,
                            fieldCls: "input_drop_target",
                            listeners: {
                                "render": function (el) {
                                    new Ext.dd.DropZone(el.getEl(), {
                                        reference: this,
                                        ddGroup: "element",

                                        getTargetFromEvent: function (e) {
                                            return this.getEl();
                                        }.bind(el),

                                        onNodeOver: function (target, dd, e, data) {
                                            return Ext.dd.DropZone.prototype.dropAllowed;
                                        },

                                        onNodeDrop: function (target, dd, e, data) {
                                            var nodeData = data.records[0].data;
                                            if (nodeData.elementType === "document") {
                                                this.setValue(nodeData.path);
                                                return true;
                                            }

                                            return false;
                                        }.bind(el)
                                    });
                                }
                            }
                        },
                        {
                            xtype: "hidden",
                            name: "type",
                            value: "redirect"
                        }
                    ]
                });
            }
        }),

        event: Class.create(pimcore.settings.targeting.action.abstract, {
            getName: function () {
                return t("event");
            },

            getPanel: function (panel, data) {
                var id = Ext.id();

                return new Ext.form.FormPanel({
                    id: id,
                    forceLayout: true,
                    style: "margin: 10px 0 0 0",
                    bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
                    tbar: pimcore.settings.targeting.actions.getTopBar(this, id, panel),
                    items: [
                        {
                            xtype: "textfield",
                            name: "key",
                            width: 300,
                            fieldLabel: t("key"),
                            value: data.key
                        },
                        {
                            xtype: "textfield",
                            name: "value",
                            width: 200,
                            fieldLabel: t("value"),
                            value: data.value
                        },
                        {
                            xtype: "hidden",
                            name: "type",
                            value: "event"
                        }
                    ]
                });
            }
        }),

        codesnippet: Class.create(pimcore.settings.targeting.action.abstract, {
            getName: function () {
                return t("code_snippet");
            },

            getPanel: function (panel, data) {
                var id = Ext.id();

                return new Ext.form.FormPanel({
                    id: id,
                    forceLayout: true,
                    style: "margin: 10px 0 0 0",
                    bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
                    tbar: pimcore.settings.targeting.actions.getTopBar(this, id, panel),
                    items: [
                        {
                            xtype: "textarea",
                            width: 600,
                            height: 200,
                            fieldLabel: t("code"),
                            name: "code",
                            value: data.code
                        },
                        {
                            xtype: 'combo',
                            fieldLabel: t('element_css_selector'),
                            name: "selector",
                            disableKeyFilter: true,
                            store: [["body", "body"], ["head", "head"]],
                            triggerAction: "all",
                            mode: "local",
                            width: 350,
                            value: data.selector
                        },
                        {
                            xtype: 'combo',
                            fieldLabel: t('insert_position'),
                            name: "position",
                            store: [["beginning", t("beginning")], ["end", t("end")], ["replace", t("replace")]],
                            triggerAction: "all",
                            typeAhead: false,
                            editable: false,
                            forceSelection: true,
                            mode: "local",
                            width: 350,
                            value: data.position
                        },
                        {
                            xtype: "hidden",
                            name: "type",
                            value: "codesnippet"
                        }
                    ]
                });
            }
        }),

        persona: Class.create(pimcore.settings.targeting.action.abstract, {
            getName: function () {
                return t('associate_target_group') + " (" + t("personas") + ")";
            },

            getPanel: function (panel, data) {
                var id = Ext.id();

                return new Ext.form.FormPanel({
                    id: id,
                    forceLayout: true,
                    style: "margin: 10px 0 0 0",
                    bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
                    tbar: pimcore.settings.targeting.actions.getTopBar(this, id, panel),
                    items: [
                        {
                            xtype: "combo",
                            name: "personaId",
                            displayField: 'text',
                            valueField: "id",
                            store: pimcore.globalmanager.get("personas"),
                            editable: false,
                            width: 400,
                            triggerAction: 'all',
                            listWidth: 200,
                            mode: "local",
                            value: data.personaId,
                            emptyText: t("select_a_persona")
                        },
                        {
                            xtype: "hidden",
                            name: "type",
                            value: "persona"
                        }
                    ]
                });
            }
        })
    };

    return {
        register: function (name, action) {
            actions[name] = action;
        },

        create: function (name) {
            var actionClass = this.get(name);

            return new actionClass();
        },

        get: function (name) {
            if ('undefined' === typeof actions[name]) {
                throw new Error('Action ' + name + ' is not defined', name);
            }

            return actions[name];
        },

        getKeys: function () {
            return Object.keys(actions);
        },

        getTopBar: function (action, index, parent) {
            return [
                {
                    iconCls: action.getIconCls(),
                    disabled: true
                },
                {
                    xtype: "tbtext",
                    text: "<b>" + action.getName() + "</b>"
                },
                "->",
                {
                    iconCls: "pimcore_icon_delete",
                    handler: function (index, parent) {
                        parent.actionsContainer.remove(Ext.getCmp(index));
                    }.bind(window, index, parent)
                }
            ];
        }
    };
}());
