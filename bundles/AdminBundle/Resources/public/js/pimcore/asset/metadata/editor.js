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

pimcore.registerNS("pimcore.asset.metadata.editor");
pimcore.asset.metadata.editor = Class.create({

    initialize: function(asset) {
        this.asset = asset;

        var dataProvider = new pimcore.asset.metadata.dataProvider();

        var eventData = {
            dataProvider: dataProvider,
            asset: asset,
            instance: null
        };

        // hook for providing a custom implementation of the asset metadata tab
        // e.g. https://github.com/pimcore/asset-metadata-class-definitions

        pimcore.plugin.broker.fireEvent("preCreateAssetMetadataEditor", this, eventData);
        this.editorInstance = eventData.instance;

        if (!this.editorInstance) {
            // if no panel has been defined by event handler then use the standard grid
            this.editorInstance = new pimcore.asset.metadata.grid({
                asset: this.asset,
                dataProvider: eventData.dataProvider
            });
        }
    },

    getLayout: function() {
        return this.editorInstance.getLayout();
    },

    getValues: function() {
        var values = this.editorInstance.getValues();
        return values;
    }
});