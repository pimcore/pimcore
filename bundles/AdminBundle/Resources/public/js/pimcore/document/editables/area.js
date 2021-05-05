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

pimcore.registerNS("pimcore.document.editables.area");
pimcore.document.editables.area = Class.create(pimcore.document.area_abstract, {

    initialize: function(id, name, config, data, inherited) {

        this.id = id;
        this.name = name;
        this.elements = [];
        this.config = this.parseConfig(config);

        //editable dialog box button
        try {
            var dialogBoxDiv = Ext.get(id).query('.pimcore_area_dialog[data-name="' + this.name + '"]')[0];
            if (dialogBoxDiv) {
                var dialogBoxButton = new Ext.Button({
                    cls: "pimcore_block_button_dialog",
                    iconCls: "pimcore_icon_white_edit",
                    listeners: {
                        "click": this.openEditableDialogBox.bind(this, Ext.get(id), dialogBoxDiv)
                    }
                });
                dialogBoxButton.render(dialogBoxDiv);
            }
        } catch (e) {
            console.log(e);
        }

    },

    setInherited: function ($super, inherited) {
        // disable masking for this datatype (overwrite), because it's actually not needed, otherwise call $super()
        this.inherited = inherited;
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
        return "area";
    }
});