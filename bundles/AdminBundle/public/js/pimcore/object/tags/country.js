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

pimcore.registerNS("pimcore.object.tags.country");
/**
 * @private
 */
pimcore.object.tags.country = Class.create(pimcore.object.tags.select, {

    type: "country",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;
        this.fieldConfig.width = this.fieldConfig.width || 300;
    },

    getGridColumnConfig:function (field) {
        var renderer = function (key, value, metaData, record) {
            if (value && Ext.isObject(value)) {
                value = value.value;
            }
            this.applyPermissionStyle(key, value, metaData, record);

            if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                try {
                    metaData.tdCls += " grid_value_inherited";
                } catch (e) {
                    console.log(e);
                }
            }

            for(var i=0; i < field.layout.options.length; i++) {
                if(field.layout.options[i]["value"] == value) {
                    return replace_html_event_attributes(strip_tags(field.layout.options[i]["key"], 'div,span,b,strong,em,i,small,sup,sub'));
                }
            }

            if (value) {
                return replace_html_event_attributes(strip_tags(value, 'div,span,b,strong,em,i,small,sup,sub'));
            }
        }.bind(this, field.key);

        return {
            text: t(field.label),
            sortable: true,
            dataIndex: field.key,
            renderer: renderer,
            editor: this.getGridColumnEditor(field)
        };
    }
});
