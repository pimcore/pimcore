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

    initialize: function (sourceDefinitionData, key, deleteControl, columnSettingsCallback) {
        sourceDefinitionData = sourceDefinitionData ? sourceDefinitionData : {filters: '', sort: '', startDate: '', relativeStartDate: '', relativeEndDate: '', endDate: '', relativeStartDate: '', dimension: '', metric: '', segment: '', profileId: ''};

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
                    var index = 1;

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

                    var index = 1;

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
                        mode: "local",
                        width: 300,
                        value: sourceDefinitionData.segment,
                        listeners: {
                            change: columnSettingsCallback
                        }
                    });

                    var index = 1;

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

        var time = new Date().getTime();

        this.element = new Ext.form.FormPanel({
            key: key,
            bodyStyle: "padding:10px;",
            layout: "pimcoreform",
            autoHeight: true,
            border: false,
            tbar: deleteControl, //this.getDeleteControl("SQL", key),
            labelWidth: 200,
            items: [
                {
                    xtype: "combo",
                    name: "profileId",
                    fieldLabel: t('profile'),
                    id: "custom_reports_analytics_" + time + "_profileId",
                    typeAhead: true,
                    displayField: 'name',
                    mode: 'local',

                    store: new Ext.data.JsonStore({
                        autoDestroy: true,
                        autoLoad: true,
                        url: "/admin/reports/analytics/get-profiles",
                        root: "data",
                        idProperty: "id",
                        fields: ["name", "id"],
                        listeners: {
                            load: function () {
                                Ext.getCmp("custom_reports_analytics_" + time + "_profileId").setValue(sourceDefinitionData.profileId);
                            }.bind(this, time, sourceDefinitionData)
                        }
                    }),
                    valueField: 'id',
                    forceSelection: true,
                    triggerAction: 'all',
                    width: 300,
                    value: sourceDefinitionData.profileId,
                    listeners: {
                        change: columnSettingsCallback
                    }

                },
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
                    xtype: 'spacer',
                    height: 30
                },
                {
                    xtype: "datefield",
                    name: "startDate",
                    fieldLabel: t("start_date"),
                    value: (sourceDefinitionData.startDate),
                    width: 250,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },
                {
                    xtype: "textfield",
                    name: "relativeStartDate",
                    fieldLabel: t("start_date_relative") + '<br/><small>'+t("relative_date_description")+"</small>",
                    value: (sourceDefinitionData.relativeStartDate),
                    width: 250,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },{
                    xtype: 'spacer',
                    height: 30
                },
                {
                    xtype: "datefield",
                    name: "endDate",
                    fieldLabel: t("end_date"),
                    value: (sourceDefinitionData.endDate),
                    width: 250,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },{
                    xtype: "textfield",
                    name: "relativeEndDate",
                    fieldLabel: t("end_date_relative") + '<br/><small>'+t("relative_date_description")+"</small>",
                    value: (sourceDefinitionData.relativeEndDate),
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