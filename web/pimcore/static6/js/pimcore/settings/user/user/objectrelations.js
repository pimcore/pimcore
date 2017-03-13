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


pimcore.registerNS("pimcore.settings.user.user.objectrelations");
pimcore.settings.user.user.objectrelations = Class.create({

    initialize: function (userPanel) {
        this.userPanel = userPanel;

        this.data = this.userPanel.data;
    },

    getPanel: function () {

        this.objectDependenciesStore = new Ext.data.JsonStore({
            autoDestroy: true,
            proxy: {
                type: 'memory',
                reader: {
                    rootProperty: 'dependencies'
                }
            },
            data: this.data.objectDependencies,
            fields: ['id', 'path', 'subtype']
        });

        this.objectDependenciesGrid = new Ext.grid.GridPanel({
            store: this.objectDependenciesStore,
            columns: [
                {header: "ID", sortable: true, dataIndex: 'id'},
                {header: t("path"), sortable: true, dataIndex: 'path', flex: 1},
                {header: t("subtype"), sortable: true, dataIndex: 'subtype'}
            ],
            columnLines: true,
            stripeRows: true,
            autoHeight: true,
            title: t('user_object_dependencies_description')
        });
        this.objectDependenciesGrid.on("rowclick", function(grid, index){
                var d = grid.getStore().getAt(index).data;
                pimcore.helpers.openObject(d.id, "object");

        });

        this.hiddenNote = new Ext.Panel({
            html:t('hidden_dependencies'),
            cls:'dependency-warning',
            border:false,
            hidden: !this.data.objectDependencies.hasHidden
        });

        this.panel = new Ext.Panel({
            title: t("user_object_dependencies_description"),
            items: [this.hiddenNote, this.objectDependenciesGrid]
        });

        return this.panel;
    }
});