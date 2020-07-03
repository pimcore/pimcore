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

pimcore.registerNS("pimcore.document.snippets.settings");
pimcore.document.snippets.settings = Class.create(pimcore.document.settings_abstract, {

    getLayout: function () {

        if (this.layout == null) {

            this.layout = new Ext.FormPanel({
                title: t('settings'),
                border: false,
                autoScroll: true,
                bodyStyle:'padding:0 10px 0 10px;',
                iconCls: "pimcore_material_icon_settings pimcore_material_icon",
                items: [
                    this.getControllerViewFields(),
                    this.getPathAndKeyFields(),
                    this.getContentMasterFields()
                ]
            });
        }

        return this.layout;
    }

});
