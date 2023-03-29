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

pimcore.registerNS("pimcore.bundle.personalization.document.areatoolbar");
/**
 * @private
 */
pimcore.bundle.personalization.document.areatoolbar = Class.create({

    initialize: function (document , lbar) {
        this.addTargetingPanel(document , lbar);
    },

    addTargetingPanel: function (document , lbar) {
        if (!Ext.Array.contains(['page', 'snippet'], document.getType())) {
            return;
        }

        if (pimcore.globalmanager.get("target_group_store").getCount() === 0) {
            return;
        }

        var cleanupFunction = function () {

            Ext.Ajax.request({
                url: Routing.generate('pimcore_bundle_personalization_clear_targeting_page_editable_data'),
                method: "PUT",
                params: {
                    targetGroup: this["targetGroup"] ? this.targetGroup.getValue() : "",
                    id: document.id
                },
                success: function () {
                    docEdit.reload(true);
                }.bind(this)
            });
        };

        var docEdit = pimcore.globalmanager.get("document_" + document.id).edit;

        this.targetGroupText = Ext.create('Ext.toolbar.TextItem', {
            scale: "medium",
            style: "-webkit-transform: rotate(270deg); -moz-transform: rotate(270deg); -o-transform: rotate(270deg); writing-mode: lr-tb;"
        });

        this.targetGroupStore = Ext.create('Ext.data.JsonStore', {
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_bundle_personalization_targeting_targetgrouplist', {'add-default': true})
            },
            fields: ["id", "text"],
            listeners: {
                load: function () {
                    this.updateTargetGroupText(this.targetGroup.getValue());
                }.bind(this)
            }
        });

        // add target group selection to toolbar
        this.targetGroup = new Ext.form.ComboBox({
            displayField: 'text',
            valueField: "id",
            store: this.targetGroupStore,
            editable: false,
            triggerAction: 'all',
            width: 240,
            listeners: {
                select: function (el) {
                    if (document.isDirty()) {
                        Ext.Msg.confirm(t('warning'), t('you_have_unsaved_changes')
                            + "<br />" + t("continue") + "?",
                            function (btn) {
                                if (btn === 'yes') {
                                    docEdit.reload(true);
                                    this.updateTargetGroupText(this.targetGroup.getValue());
                                }
                            }.bind(this)
                        );
                    } else {
                        docEdit.reload();
                        this.updateTargetGroupText(this.targetGroup.getValue());
                    }
                }.bind(this)
            }
        });

        this.targetGroupStore.load();

        lbar.push("->",
            this.targetGroupText,
            {
                tooltip: t("edit_content_for_target_group"),
                iconCls: "pimcore_icon_target_groups",
                arrowVisible: false,
                menuAlign: "tl",
                menu: [this.targetGroup]
            },
            {
                tooltip: t("clear_content_of_selected_target_group"),
                iconCls: "pimcore_icon_cleanup",
                handler: cleanupFunction.bind(this)
            }
        );
    },
    updateTargetGroupText: function (targetgroup) {
        var record = this.targetGroupStore.getById(targetgroup);

        if (record) {
            this.targetGroupText.update('&nbsp;&nbsp;<img src="/bundles/pimcoreadmin/img/flat-color-icons/manager.svg" style="height: 16px;" align="absbottom" />&nbsp;&nbsp;'
                + record.data.text);
        } else {
            this.targetGroupText.update('');
        }
    },

});
