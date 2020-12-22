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

pimcore.registerNS("pimcore.report.custom.definition.analytics");
pimcore.report.custom.definition.analytics = Class.create({

    element: null,
    sourceDefinitionData: null,

    initialize: function (sourceDefinitionData, key, deleteControl, columnSettingsCallback) {
        sourceDefinitionData = sourceDefinitionData ? sourceDefinitionData : {filters: '', sort: '', startDate: '', relativeStartDate: '', relativeEndDate: '', endDate: '', dimension: '', metric: '', segment: '', profileId: ''};

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

        var dimensionLoaded = false;
        var metricLoaded = false;
        var segmentLoaded = false;

        this.dimensionStore = new Ext.data.Store({
            autoDestroy: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_reports_analytics_getdimensions'),
                reader: {
                    type: 'json',
                    rootProperty: "data",
                    idProperty: "id"
                }
            },
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
                        width: 400,
                        height: 100,
                        value: sourceDefinitionData.dimension,
                        listeners: {
                            change: columnSettingsCallback
                        }

                    });
                    var index = 1;

                    this.element.insert(index, dimensions);


                    this.element.updateLayout();
                    dimensionLoaded = true;

                }.bind(this)
            }
        });
        this.dimensionStore.load();

        this.metricsStore = new Ext.data.JsonStore({
            autoDestroy: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_reports_analytics_getmetrics'),
                reader: {
                    type: 'json',
                    rootProperty: "data",
                    idProperty: "id"
                }
            },
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
                        width: 400,
                        height: 100,
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
                    this.element.updateLayout();
                    metricLoaded = true;
                }.bind(this)
            }
        });
        this.metricsStore.load();

        this.segementsStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_reports_analytics_getsegments'),
                reader: {
                    rootProperty: "data",
                    idProperty: "id"
                }
            },
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
                        width: 400,
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
                    this.element.updateLayout();

                }.bind(this)
            }
        });
        this.segementsStore.load();

        var time = new Date().getTime();

        this.element = new Ext.form.FormPanel({
            key: key,
            bodyStyle: "padding:10px;",
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
                        proxy: {
                            type: 'ajax',
                            url: Routing.generate('pimcore_admin_reports_analytics_getprofiles'),
                            reader: {
                                type: 'json',
                                rootProperty: "data",
                                idProperty: "id"
                            }
                        },

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
                    width: 400,
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
                    width: 350,
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
                    width: 350,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },
                {
                    xtype: 'tbspacer',
                    height: 30
                },
                {
                    xtype: "datefield",
                    name: "startDate",
                    fieldLabel: t("start_date"),
                    value: (sourceDefinitionData.startDate),
                    width: 350,
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
                    width: 350,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },{
                    xtype: 'tbspacer',
                    height: 30
                },
                {
                    xtype: "datefield",
                    name: "endDate",
                    fieldLabel: t("end_date"),
                    value: (sourceDefinitionData.endDate),
                    width: 350,
                    enableKeyEvents: true,
                    listeners: {
                        change: columnSettingsCallback
                    }
                },{
                    xtype: "textfield",
                    name: "relativeEndDate",
                    fieldLabel: t("end_date_relative") + '<br/><small>'+t("relative_date_description")+"</small>",
                    value: (sourceDefinitionData.relativeEndDate),
                    width: 350,
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
