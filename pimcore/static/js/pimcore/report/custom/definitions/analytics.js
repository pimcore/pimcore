/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.report.custom.definition.analytics");
pimcore.report.custom.definition.analytics = Class.create({

    element: null,
    sourceDefinitionData: null,

    initialize: function (sourceDefinitionData, key, deleteControl, columnSettingsCallback, store) {
        sourceDefinitionData = sourceDefinitionData ? sourceDefinitionData : {filters: '', sort: '', startDate: '', endDate: '', dimension: '', metric: '', segment: '', profileId: ''};

        if (sourceDefinitionData.startDate) {
            var startDate = new Date();
            startDate.setTime(sourceDefinitionData.startDate);
            sourceDefinitionData.startDate = startDate;
        }

        if (sourceDefinitionData.endDate) {
            var endDate = new Date();
            endDate.setTime(sourceDefinitionData.endDate);
            sourceDefinitionData.endDate = endDate;
        }

        this.sourceDefinitionData = sourceDefinitionData;

        var profileLoaded = false;
        var dimensionLoaded = false;
        var metricLoaded = false;
        var segmentLoaded = false;

        this.profileStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: "/admin/reports/analytics/get-profiles",
            root: "data",
            idProperty: "id",
            fields: ["name", "id"],
            listeners: {
                load: function () {
                    if (profileLoaded) {
                        return;
                    }
                    var dimensions = new Ext.form.ComboBox({
                        name: "profileId",
                        triggerAction: "all",
                        editable: false,
                        fieldLabel: t("profile"),
                        store: this.profileStore,

                        displayField: "name",
                        valueField: "id",
                        width: 300,
                        value: sourceDefinitionData.profileId,
                        listeners: {
                            change: columnSettingsCallback
                        }

                    });

                    this.element.insert(0, dimensions);


                    this.element.doLayout();
                    profileLoaded = true;

                }.bind(this)
            }
        });
        this.profileStore.load();

        this.dimensionStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: "/admin/reports/analytics/get-dimensions",
            root: "data",
            idProperty: "id",
            fields: ["name", "id"],
            listeners: {
                load: function () {

                    var dimensions = new Ext.ux.form.MultiSelect({
                        name: "dimension",
                        triggerAction: "all",
                        editable: false,
                        fieldLabel: t("dimension"),
                        store: this.dimensionStore,

                        displayField: "name",
                        valueField: "id",
                        width: 300,
                        value: sourceDefinitionData.dimension,
                        listeners: {
                            change: columnSettingsCallback
                        }

                    });
                    var index = 0;
                    if (profileLoaded) {
                        index++;
                    }
                    this.element.insert(index, dimensions);


                    this.element.doLayout();
                    dimensionLoaded = true;

                }.bind(this)
            }
        });
        this.dimensionStore.load();

        this.metricsStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: "/admin/reports/analytics/get-metrics",
            root: "data",
            idProperty: "id",
            fields: ["name", "id"],
            listeners: {
                load: function () {


                    var metrics = new Ext.ux.form.MultiSelect({
                        name: "metric",
                        triggerAction: "all",
                        editable: false,
                        fieldLabel: t("metric"),
                        store: this.metricsStore,
                        displayField: "name",
                        valueField: "id",
                        width: 300,
                        value: sourceDefinitionData.metric,
                        listeners: {
                            change: columnSettingsCallback
                        }
                    });

                    var index = 0;
                    if (profileLoaded) {
                        index++;
                    }
                    if (dimensionLoaded) {
                        index++;
                    }

                    this.element.insert(index, metrics);
                    this.element.doLayout();
                    metricLoaded = true;
                }.bind(this)
            }
        });
        this.metricsStore.load();

        this.segementsStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            url: "/admin/reports/analytics/get-segments",
            root: "data",
            idProperty: "id",
            fields: ["name", "id"],
            listeners: {
                load: function () {

                    if (segmentLoaded) {
                        return;
                    }

                    var segments = new Ext.form.ComboBox({
                        name: "segment",
                        triggerAction: "all",
                        editable: false,
                        fieldLabel: t("segment"),
                        store: this.segementsStore,
                        displayField: "name",
                        valueField: "id",
                        width: 300,
                        value: sourceDefinitionData.segment,
                        listeners: {
                            change: columnSettingsCallback
                        }
                    });

                    var index = 0;
                    if (profileLoaded) {
                        index++;
                    }
                    if (dimensionLoaded) {
                        index++;
                    }
                    if (metricLoaded) {
                        index++;
                    }
                    segmentLoaded = true;

                    this.element.insert(index, segments);
                    this.element.doLayout();

                }.bind(this)
            }
        });
        this.segementsStore.load();

        this.element = new Ext.form.FormPanel({
            key: key,
            bodyStyle: "padding:10px;",
            layout: "pimcoreform",
            autoHeight: true,
            border: false,
            tbar: deleteControl, //this.getDeleteControl("SQL", key),
            items: [
                {
                    xtype: "textfield",
                    name: "filters",
                    fieldLabel: "Filters",
                    value: (sourceDefinitionData.filters ),
                    width: 250,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },
                {
                    xtype: "textfield",
                    name: "sort",
                    fieldLabel: "Sort",
                    value: (sourceDefinitionData.sort),
                    width: 250,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },
                {
                    xtype: "datefield",
                    name: "startDate",
                    fieldLabel: "Start date",
                    value: (sourceDefinitionData.startDate),
                    width: 250,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },
                {
                    xtype: "datefield",
                    name: "endDate",
                    fieldLabel: "End date",
                    value: (sourceDefinitionData.endDate),
                    width: 250,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                }

            ]
        });
    },

    getElement: function () {
        return this.element;
    },

    getValues: function () {

        var values = this.element.getForm().getFieldValues();

        if (typeof values.metric == 'undefined' || typeof values.dimension == 'undefined') {
            values = this.sourceDefinitionData;
        }

        values.type = "analytics";
        if (values.startDate) {
            values.startDate = new Date(values.startDate).getTime();
        }
        if (values.endDate) {
            values.endDate = new Date(values.endDate).getTime();
        }

        return values;
    }


});