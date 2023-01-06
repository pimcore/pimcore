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
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.object.classes.layout.text");
/**
 * @private
 */
pimcore.object.classes.layout.text = Class.create(pimcore.object.classes.layout.layout, {

    type: "text",

    initialize: function (treeNode, initData) {
        this.type = "text";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("text");
    },

    getIconClass: function () {
        return "pimcore_icon_text";
    },

    getLayout: function ($super) {
        $super();

        this.previewPanel = new Ext.Panel({
            layout: 'fit',
            height: 500,
            html: '<iframe src="about:blank" style="width: 100%; height: 100%;" frameborder="0" id="text-layout-preview_' + this.id + '"></iframe>',
        });

        this.speicificSettingsForm = new Ext.form.FormPanel({
            title: t("specific_settings"),
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            style: "margin: 10px 0 10px 0",
            items: [
                {
                    xtype: "checkbox",
                    fieldLabel: t("border"),
                    name: "border",
                    checked: this.datax.border,
                },
                {
                    xtype: "hiddenfield",
                    name: "className",
                    value: this.treeNode.getOwnerTree().getRootNode().data.className,
                },
                {
                    xtype: "tabpanel",
                    activeTab: 0,
                    monitorResize: true,
                    deferredRender: true,
                    border: false,
                    bodyStyle: "padding: 10px",
                    forceLayout: true,
                    hideMode: "offsets",
                    enableTabScroll: true,
                    items: [
                        {
                            xtype: "form",
                            title: 'Configuration',
                            region: 'center',
                            items: [
                                {
                                    xtype: "textfield",
                                    fieldLabel: t("rendering_class"),
                                    value: this.datax.renderingClass,
                                    width: 600,
                                    name: "renderingClass"
                                },
                                {
                                    xtype: "textfield",
                                    fieldLabel: t("rendering_data"),
                                    width: 600,
                                    value: this.datax.renderingData,
                                    name: "renderingData"
                                },
                                {
                                    xtype: 'container',
                                    style: 'padding-top:10px;',
                                    html: 'You can use the following markup (in source edit mode) to make custom alerts: <br> <pre>&lt;div class=&quot;alert alert-success&quot;&gt;Your Message&lt;/div&gt;</pre>The following contextual classes are available: <pre>alert-primary, alert-secondary, alert-success, alert-danger, alert-warning, alert-info</pre> <pre>You can also use Twig syntax:</pre> <pre>Additional Data {{data}}</pre>'
                                },
                                {
                                    xtype: "htmleditor",
                                    cls: 'objectlayout_element_text',
                                    height: 300,
                                    value: this.datax.html,
                                    name: "html",
                                    enableSourceEdit: true,
                                    enableFont: false,
                                    listeners: {
                                        initialize: function (el) {
                                            var head = el.getDoc().head;
                                            var link = document.createElement("link");

                                            link.type = "text/css";
                                            link.rel = "stylesheet";
                                            link.href = '/bundles/pimcoreadmin/css/admin.css';

                                            head.appendChild(link);
                                            el.getEditorBody().classList.add('objectlayout_element_text');
                                        }
                                    }
                                }
                            ]
                        },
                        {
                            title: 'Preview',
                            xtype: 'panel',
                            region: 'center',
                            items: [
                                {
                                    xtype: "displayfield",
                                    fieldLabel: t("drag_object_preview"),
                                    width: 600,
                                    cls: 'pimcore_droptarget_display_edit',
                                    fieldBodyCls: 'pimcore_droptarget_display x-form-trigger-wrap',
                                    name: "previewObject",
                                    editable: true,
                                    listeners: {
                                        render: function (el) {
                                            // add drop zone
                                            new Ext.dd.DropZone(el.getEl(), {
                                                reference: this,
                                                ddGroup: "element",
                                                getTargetFromEvent: function(e) {
                                                    return this.getEl();
                                                }.bind(el),

                                                onNodeOver: function (target, dd, e, data) {
                                                    if (data.records.length === 1 && data.records[0].data.type == 'object') {
                                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                                    }
                                                }.bind(this),

                                                onNodeDrop: function (target, dd, e, data) {
                                                    if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                                        return false;
                                                    }

                                                    data = data.records[0].data;

                                                    if (data) {
                                                        this.setValue(data.path);

                                                        return true;
                                                    } else {
                                                        return false;
                                                    }
                                                }.bind(el)
                                            });
                                        }.bind(this),
                                        change: this.loadPreview.bind(this)
                                    }
                                },
                                this.previewPanel
                            ],
                            listeners: {
                                activate: this.loadPreview.bind(this)
                            }
                        }
                    ]
                }
            ]
        });

        this.layout.add(this.speicificSettingsForm);

        return this.layout;
    },

    loadPreview: function () {
        let params = this.speicificSettingsForm.getForm().getFieldValues();
        var url = Routing.generate('pimcore_admin_dataobject_class_textlayoutpreview', params);

        try {
            Ext.get('text-layout-preview_' + this.id).dom.src = url;
        } catch (e) {
            console.log(e);
        }
    }
});