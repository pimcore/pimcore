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

pimcore.registerNS("pimcore.document.editables.area");
/**
 * @private
 */
pimcore.document.editables.area = Class.create(pimcore.document.area_abstract, {

    initialize: function($super, id, name, config, data, inherited) {
        $super(id, name, config, data, inherited);

        this.datax = data ?? {};

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
        if(this.config['type'] !== undefined){
            this.datax['type'] = this.config['type'];
        }

        return this.datax;
    },

    getType: function () {
        return "area";
    }
});