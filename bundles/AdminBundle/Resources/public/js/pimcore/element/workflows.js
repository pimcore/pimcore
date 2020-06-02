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

pimcore.registerNS("pimcore.element.workflows");
pimcore.element.workflows = Class.create({

    initialize: function(element, type) {
        this.element = element;
        this.type = type;
    },

    getLayout: function () {

        if (this.layout == null) {

            this.store = pimcore.helpers.grid.buildDefaultStore(
                Routing.generate('pimcore_admin_workflow_getworkflowdetailsstore', {ctype: this.type, cid: this.element.id}),
                ['workflowName','placeInfo','graph'],
                0, //no paging needed
                {autoLoad: false}
            );


            var columns = [
                {text: t("workflow"), sortable: false, dataIndex: 'workflowName', flex: 20},
                {text: t("workflow_current_state"), sortable: false, dataIndex: 'placeInfo', flex: 30},
                {text: t("workflow_graph"), sortable: false, dataIndex: 'graph', flex: 90},
            ];


            this.grid = new Ext.grid.GridPanel({
                store: this.store,
                region: "center",
                columns: columns,
                columnLines: true,
                autoExpandColumn: "description",
                stripeRows: true,
                autoScroll: true,
                viewConfig: {
                    forceFit: true
                }
            });


            this.layout = new Ext.Panel( {
                tabConfig: {
                    tooltip: t('workflow_details')
                },
                items: [this.grid],
                iconCls: "pimcore_material_icon_workflow pimcore_material_icon",
                layout: 'border'
            });

            this.layout.on("activate", function () {
                this.store.load();
            }.bind(this));
        }

        return this.layout;
    }

});
