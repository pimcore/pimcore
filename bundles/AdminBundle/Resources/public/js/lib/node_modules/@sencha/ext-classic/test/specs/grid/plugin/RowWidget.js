topSuite("Ext.grid.plugin.RowWidget",
    ['Ext.grid.Panel', 'Ext.Button', 'Ext.app.ViewModel', 'Ext.grid.column.*', 'Ext.mixin.Watchable'],
function() {
    var itNotIE8 = Ext.isIE8 ? xit : it,
        dummyData = [
            ['3m Co', 71.72, 0.02, 0.03, '9/1 12:00am', 'Manufacturing'],
            ['Alcoa Inc', 29.01, 0.42, 1.47, '9/1 12:00am', 'Manufacturing'],
            ['Altria Group Inc', 83.81, 0.28, 0.34, '9/1 12:00am', 'Manufacturing'],
            ['American Express Company', 52.55, 0.01, 0.02, '9/1 12:00am', 'Finance'],
            ['American International Group, Inc.', 64.13, 0.31, 0.49, '9/1 12:00am', 'Services'],
            ['AT&T Inc.', 31.61, -0.48, -1.54, '9/1 12:00am', 'Services'],
            ['Boeing Co.', 75.43, 0.53, 0.71, '9/1 12:00am', 'Manufacturing'],
            ['Caterpillar Inc.', 67.27, 0.92, 1.39, '9/1 12:00am', 'Services'],
            ['Citigroup, Inc.', 49.37, 0.02, 0.04, '9/1 12:00am', 'Finance'],
            ['E.I. du Pont de Nemours and Company', 40.48, 0.51, 1.28, '9/1 12:00am', 'Manufacturing'],
            ['Exxon Mobil Corp', 68.1, -0.43, -0.64, '9/1 12:00am', 'Manufacturing'],
            ['General Electric Company', 34.14, -0.08, -0.23, '9/1 12:00am', 'Manufacturing'],
            ['General Motors Corporation', 30.27, 1.09, 3.74, '9/1 12:00am', 'Automotive'],
            ['Hewlett-Packard Co.', 36.53, -0.03, -0.08, '9/1 12:00am', 'Computer'],
            ['Honeywell Intl Inc', 38.77, 0.05, 0.13, '9/1 12:00am', 'Manufacturing'],
            ['Intel Corporation', 19.88, 0.31, 1.58, '9/1 12:00am', 'Computer'],
            ['International Business Machines', 81.41, 0.44, 0.54, '9/1 12:00am', 'Computer'],
            ['Johnson & Johnson', 64.72, 0.06, 0.09, '9/1 12:00am', 'Medical'],
            ['JP Morgan & Chase & Co', 45.73, 0.07, 0.15, '9/1 12:00am', 'Finance'],
            ['McDonald\'s Corporation', 36.76, 0.86, 2.40, '9/1 12:00am', 'Food'],
            ['Merck & Co., Inc.', 40.96, 0.41, 1.01, '9/1 12:00am', 'Medical'],
            ['Microsoft Corporation', 25.84, 0.14, 0.54, '9/1 12:00am', 'Computer'],
            ['Pfizer Inc', 27.96, 0.4, 1.45, '9/1 12:00am', 'Services', 'Medical'],
            ['The Coca-Cola Company', 45.07, 0.26, 0.58, '9/1 12:00am', 'Food'],
            ['The Home Depot, Inc.', 34.64, 0.35, 1.02, '9/1 12:00am', 'Retail'],
            ['The Procter & Gamble Company', 61.91, 0.01, 0.02, '9/1 12:00am', 'Manufacturing'],
            ['United Technologies Corporation', 63.26, 0.55, 0.88, '9/1 12:00am', 'Computer'],
            ['Verizon Communications', 35.57, 0.39, 1.11, '9/1 12:00am', 'Services'],
            ['Wal-Mart Stores, Inc.', 45.45, 0.73, 1.63, '9/1 12:00am', 'Retail'],
            ['Walt Disney Company (The) (Holding Company)', 29.89, 0.24, 0.81, '9/1 12:00am', 'Services']
        ],
        store, expander, grid, view, scroller, bufferedRenderer, columns, i, widget, componentCount,
        lorem = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Sed metus nibh, sodales a, porta at, vulputate eget, dui. Pellentesque ut nisl. Maecenas tortor turpis, interdum non, sodales non, iaculis ac, lacus. Vestibulum auctor, tortor quis iaculis malesuada, libero lectus bibendum purus, sit amet tincidunt quam turpis vel lacus. In pellentesque nisl non sem. Suspendisse nunc sem, pretium eget, cursus a, fringilla vel, urna.<br/><br/>Aliquam commodo ullamcorper erat. Nullam vel justo in neque porttitor laoreet. Aenean lacus dui, consequat eu, adipiscing eget, nonummy non, nisi. Morbi nunc est, dignissim non, ornare sed, luctus eu, massa. Vivamus eget quam. Vivamus tincidunt diam nec urna. Curabitur velit.';

    // add in some dummy descriptions
    for (i = 0; i < dummyData.length; i++) {
        dummyData[i].unshift(i);
        dummyData[i].push(lorem);
    }

    function makeGrid(gridCfg, rowWidgetCfg) {
        gridCfg = gridCfg || {};

        Ext.define('spec.RowWidgetCompany', {
            extend: 'Ext.data.Model',

            fields: [
                {
                    name: 'id'
                },
                { name: 'company' },
                { name: 'price', type: 'float' },
                { name: 'change', type: 'float' },
                { name: 'pctChange', type: 'float' },
                { name: 'lastChange', type: 'date',  dateFormat: 'n/j h:ia' },
                { name: 'industry' },
                // Rating dependent upon performance 0 = best, 2 = worst
                {
                    name: 'rating',
                    type: 'int',
                    convert: function(value, record) {
                        var pct = record.get('pctChange');

                        if (pct < 0) {
                            return 2;
                        }

                        if (pct < 1) {
                            return 1;
                        }

                        return 0;
                    }
                }
            ]
        });
        Ext.define('spec.RowWidgetOrder', {
            extend: 'Ext.data.Model',

            requires: [
                'Ext.data.proxy.Memory',
                'Ext.data.reader.Json'
            ],

            fields: [
                { name: 'id' },
                // Declare an association with Company.
                // Each Company record will be decorated with
                // an "orders" method which yields a store
                // containing associated orders.
                {
                    name: 'companyId',
                    reference: {
                        parent: 'spec.RowWidgetCompany',
                        inverse: {
                            role: 'orders',
                            autoLoad: false
                        }
                    }
                },
                { name: 'productCode' },
                { name: 'quantity', type: 'number' },
                { name: 'date', type: 'date', dateFormat: 'Y-m-d' },
                { name: 'shipped', type: 'boolean' }
            ],

            proxy: {
                type: 'memory',
                reader: {
                    type: 'json'
                },
                data: [{
                    "id": 1,
                    "companyId": 25,
                    "productCode": "4ada2f18-f47f-4de0-80bd-9f7985288066",
                    "quantity": 38,
                    "date": "2015-10-08",
                    "shipped": true
                }, {
                    "id": 2,
                    "companyId": 16,
                    "productCode": "ae571587-9914-4d39-8b61-e3a5d74fe374",
                    "quantity": 21,
                    "date": "2015-10-04",
                    "shipped": false
                }, {
                    "id": 3,
                    "companyId": 18,
                    "productCode": "c20bb4e1-7403-4960-b8e0-73cf3164fb6a",
                    "quantity": 51,
                    "date": "2015-10-11",
                    "shipped": true
                }, {
                    "id": 4,
                    "companyId": 7,
                    "productCode": "1f11be60-45cc-4b61-a0a0-9ce7e1ee0f21",
                    "quantity": 37,
                    "date": "2015-10-11",
                    "shipped": false
                }, {
                    "id": 5,
                    "companyId": 29,
                    "productCode": "ae3fa072-ef44-4c1e-9f99-f79c02b32cac",
                    "quantity": 93,
                    "date": "2015-10-15",
                    "shipped": false
                }, {
                    "id": 6,
                    "companyId": 2,
                    "productCode": "fa15a121-dbe2-4240-bca9-a444b25ad009",
                    "quantity": 42,
                    "date": "2015-10-18",
                    "shipped": true
                }, {
                    "id": 7,
                    "companyId": 14,
                    "productCode": "4671cbbe-1f44-4786-8a19-587844e0375a",
                    "quantity": 59,
                    "date": "2015-10-06",
                    "shipped": true
                }, {
                    "id": 8,
                    "companyId": 5,
                    "productCode": "be3489f3-0b4c-487b-82e4-75e04c74522e",
                    "quantity": 11,
                    "date": "2015-10-11",
                    "shipped": false
                }, {
                    "id": 9,
                    "companyId": 4,
                    "productCode": "e4204450-780c-4f4a-a154-445502d8ae54",
                    "quantity": 42,
                    "date": "2015-10-07",
                    "shipped": true
                }, {
                    "id": 10,
                    "companyId": 11,
                    "productCode": "2c84bc96-ee6a-4529-b4ee-6b45f998b94e",
                    "quantity": 22,
                    "date": "2015-10-07",
                    "shipped": true
                }, {
                    "id": 11,
                    "companyId": 6,
                    "productCode": "928053ba-cff5-463a-b541-76b011eaa31a",
                    "quantity": 9,
                    "date": "2015-10-07",
                    "shipped": true
                }, {
                    "id": 12,
                    "companyId": 14,
                    "productCode": "efb730b5-abb2-4233-9932-88a9dd7890e9",
                    "quantity": 2,
                    "date": "2015-10-12",
                    "shipped": false
                }, {
                    "id": 13,
                    "companyId": 13,
                    "productCode": "975107a1-e573-48f0-8e28-a60cc2d5a7a4",
                    "quantity": 93,
                    "date": "2015-10-11",
                    "shipped": false
                }, {
                    "id": 14,
                    "companyId": 24,
                    "productCode": "5d3b6fea-599b-4b20-841f-f2530db563be",
                    "quantity": 9,
                    "date": "2015-10-07",
                    "shipped": false
                }, {
                    "id": 15,
                    "companyId": 22,
                    "productCode": "c2198f11-b7a9-4608-9fb4-365cf4b1d86c",
                    "quantity": 1,
                    "date": "2015-10-02",
                    "shipped": true
                }, {
                    "id": 16,
                    "companyId": 12,
                    "productCode": "b24ebecb-b311-4143-98bd-870e24fc3ac9",
                    "quantity": 36,
                    "date": "2015-10-10",
                    "shipped": true
                }, {
                    "id": 17,
                    "companyId": 24,
                    "productCode": "bca404d2-bb07-4a72-a48c-56ee21bf4d99",
                    "quantity": 61,
                    "date": "2015-10-09",
                    "shipped": false
                }, {
                    "id": 18,
                    "companyId": 9,
                    "productCode": "00a0e766-58ff-4bfe-9071-c6f7f1461a6f",
                    "quantity": 2,
                    "date": "2015-10-02",
                    "shipped": false
                }, {
                    "id": 19,
                    "companyId": 15,
                    "productCode": "800f9182-3c14-4530-b086-e154a3015ddb",
                    "quantity": 61,
                    "date": "2015-10-06",
                    "shipped": true
                }, {
                    "id": 20,
                    "companyId": 23,
                    "productCode": "f1e3098e-fda6-4d3a-aad5-6fef3287fdda",
                    "quantity": 59,
                    "date": "2015-10-18",
                    "shipped": true
                }, {
                    "id": 21,
                    "companyId": 21,
                    "productCode": "3e61402a-cb2b-4af7-8346-0e6da4a46bbf",
                    "quantity": 25,
                    "date": "2015-10-10",
                    "shipped": false
                }, {
                    "id": 22,
                    "companyId": 16,
                    "productCode": "2a0af36b-5734-4d6f-b792-768accd1854a",
                    "quantity": 91,
                    "date": "2015-10-01",
                    "shipped": true
                }, {
                    "id": 23,
                    "companyId": 12,
                    "productCode": "e7c5936b-7f42-422a-8656-db1868210e74",
                    "quantity": 16,
                    "date": "2015-10-13",
                    "shipped": false
                }, {
                    "id": 24,
                    "companyId": 6,
                    "productCode": "d9a4c94f-5b48-452e-a498-685bd97e0136",
                    "quantity": 12,
                    "date": "2015-10-12",
                    "shipped": true
                }, {
                    "id": 25,
                    "companyId": 20,
                    "productCode": "d288a020-d1f8-4524-b1b6-7db3cceb03da",
                    "quantity": 32,
                    "date": "2015-10-17",
                    "shipped": false
                }, {
                    "id": 26,
                    "companyId": 23,
                    "productCode": "97de6635-d7ec-456c-9bfd-d41544dc80d5",
                    "quantity": 98,
                    "date": "2015-10-15",
                    "shipped": true
                }, {
                    "id": 27,
                    "companyId": 1,
                    "productCode": "b474e520-6b69-4490-a558-da04eba9cd89",
                    "quantity": 80,
                    "date": "2015-10-17",
                    "shipped": false
                }, {
                    "id": 28,
                    "companyId": 2,
                    "productCode": "d520859d-853b-43f9-9736-ef22d320ef9c",
                    "quantity": 70,
                    "date": "2015-10-05",
                    "shipped": true
                }, {
                    "id": 29,
                    "companyId": 4,
                    "productCode": "f8113ed0-f085-46a5-abe9-7036bdc3d181",
                    "quantity": 86,
                    "date": "2015-10-03",
                    "shipped": true
                }, {
                    "id": 30,
                    "companyId": 4,
                    "productCode": "8c3ad2bf-0fa9-4155-b28d-6f74019360d9",
                    "quantity": 88,
                    "date": "2015-10-01",
                    "shipped": true
                }, {
                    "id": 31,
                    "companyId": 11,
                    "productCode": "8bab58c2-9f09-4fd8-a7ff-774e40efad27",
                    "quantity": 60,
                    "date": "2015-10-18",
                    "shipped": false
                }, {
                    "id": 32,
                    "companyId": 0,
                    "productCode": "8a54720d-6a69-43ad-ae1f-d246986b7e0c",
                    "quantity": 65,
                    "date": "2015-10-17",
                    "shipped": false
                }, {
                    "id": 33,
                    "companyId": 14,
                    "productCode": "6a6e4c90-44f1-4c28-91c6-09eaa81ba693",
                    "quantity": 54,
                    "date": "2015-10-15",
                    "shipped": false
                }, {
                    "id": 34,
                    "companyId": 5,
                    "productCode": "aefefa7a-b766-45e0-bf31-50eceb981bc4",
                    "quantity": 93,
                    "date": "2015-10-03",
                    "shipped": true
                }, {
                    "id": 35,
                    "companyId": 15,
                    "productCode": "8f5668dc-2ea0-44ec-8c83-97cd6bba40cb",
                    "quantity": 15,
                    "date": "2015-10-10",
                    "shipped": false
                }, {
                    "id": 36,
                    "companyId": 3,
                    "productCode": "0714d7eb-2f7c-44ec-9e95-8c50230aa9a8",
                    "quantity": 27,
                    "date": "2015-10-05",
                    "shipped": false
                }, {
                    "id": 37,
                    "companyId": 15,
                    "productCode": "f27c1913-b8a8-443e-91d7-a2d07132593c",
                    "quantity": 52,
                    "date": "2015-10-02",
                    "shipped": true
                }, {
                    "id": 38,
                    "companyId": 20,
                    "productCode": "e25995e4-0d41-488b-b583-5d349bc2c2df",
                    "quantity": 72,
                    "date": "2015-10-09",
                    "shipped": true
                }, {
                    "id": 39,
                    "companyId": 5,
                    "productCode": "243889ec-8b0a-434a-a417-a2af75397591",
                    "quantity": 54,
                    "date": "2015-10-13",
                    "shipped": false
                }, {
                    "id": 40,
                    "companyId": 2,
                    "productCode": "4701cf80-5db4-4ca6-9c9b-4dafaf010b43",
                    "quantity": 14,
                    "date": "2015-10-12",
                    "shipped": false
                }, {
                    "id": 41,
                    "companyId": 5,
                    "productCode": "5302455b-197c-467a-aa82-8070fd610a27",
                    "quantity": 48,
                    "date": "2015-10-15",
                    "shipped": false
                }, {
                    "id": 42,
                    "companyId": 27,
                    "productCode": "27e47796-6088-4704-9390-defa5f9bf57f",
                    "quantity": 53,
                    "date": "2015-10-17",
                    "shipped": false
                }, {
                    "id": 43,
                    "companyId": 27,
                    "productCode": "031180d7-f0da-40c1-add1-22389f9d6abe",
                    "quantity": 12,
                    "date": "2015-10-03",
                    "shipped": true
                }, {
                    "id": 44,
                    "companyId": 11,
                    "productCode": "49b185cf-b9f5-4384-b8f3-215893a2d099",
                    "quantity": 48,
                    "date": "2015-10-13",
                    "shipped": false
                }, {
                    "id": 45,
                    "companyId": 10,
                    "productCode": "e3016946-a5f2-4725-9fa9-ecf1aee6182a",
                    "quantity": 70,
                    "date": "2015-10-13",
                    "shipped": true
                }, {
                    "id": 46,
                    "companyId": 10,
                    "productCode": "76d5f887-3ab1-431a-a870-757bf0061799",
                    "quantity": 33,
                    "date": "2015-10-03",
                    "shipped": true
                }, {
                    "id": 47,
                    "companyId": 23,
                    "productCode": "82de1b5c-e4a8-4f6e-a15c-ae90322a8cbd",
                    "quantity": 38,
                    "date": "2015-10-06",
                    "shipped": false
                }, {
                    "id": 48,
                    "companyId": 0,
                    "productCode": "993e950d-4ce4-4163-ae45-15e0e0bb14b0",
                    "quantity": 32,
                    "date": "2015-10-16",
                    "shipped": true
                }, {
                    "id": 49,
                    "companyId": 3,
                    "productCode": "1fe285bc-dc9a-44b1-833b-e3f90885ad7e",
                    "quantity": 72,
                    "date": "2015-10-08",
                    "shipped": false
                }, {
                    "id": 50,
                    "companyId": 2,
                    "productCode": "a9879b6f-b010-4276-bfbe-07cfff6e3d43",
                    "quantity": 19,
                    "date": "2015-10-15",
                    "shipped": true
                }, {
                    "id": 51,
                    "companyId": 11,
                    "productCode": "112da126-5922-42d2-8861-eb876d90ceed",
                    "quantity": 100,
                    "date": "2015-10-01",
                    "shipped": false
                }, {
                    "id": 52,
                    "companyId": 0,
                    "productCode": "ca937b79-9966-46cc-bdce-82a7761f7c7f",
                    "quantity": 13,
                    "date": "2015-10-17",
                    "shipped": false
                }, {
                    "id": 53,
                    "companyId": 9,
                    "productCode": "8ce21bb8-fc2d-43e5-924a-0213b4266242",
                    "quantity": 56,
                    "date": "2015-10-07",
                    "shipped": true
                }, {
                    "id": 54,
                    "companyId": 5,
                    "productCode": "e512122b-71d8-4bc9-ab07-0dd377ef7419",
                    "quantity": 97,
                    "date": "2015-10-02",
                    "shipped": true
                }, {
                    "id": 55,
                    "companyId": 7,
                    "productCode": "a87cd322-4781-4ba3-bf20-64d7783dcf61",
                    "quantity": 56,
                    "date": "2015-10-14",
                    "shipped": true
                }, {
                    "id": 56,
                    "companyId": 1,
                    "productCode": "0fdc6605-fad9-4c76-9af0-7d8624bf60ba",
                    "quantity": 38,
                    "date": "2015-10-02",
                    "shipped": false
                }, {
                    "id": 57,
                    "companyId": 21,
                    "productCode": "3c442ff2-abfe-43a2-88a7-335097df870e",
                    "quantity": 33,
                    "date": "2015-10-09",
                    "shipped": false
                }, {
                    "id": 58,
                    "companyId": 5,
                    "productCode": "85deedb1-9493-4427-9a23-3911b4202aa1",
                    "quantity": 91,
                    "date": "2015-10-05",
                    "shipped": true
                }, {
                    "id": 59,
                    "companyId": 24,
                    "productCode": "81b74525-7b47-4ccd-a177-d4cbb6af4768",
                    "quantity": 57,
                    "date": "2015-10-16",
                    "shipped": true
                }, {
                    "id": 60,
                    "companyId": 13,
                    "productCode": "52cf5cdb-475a-46b3-9ed7-db4343377837",
                    "quantity": 33,
                    "date": "2015-10-12",
                    "shipped": false
                }, {
                    "id": 61,
                    "companyId": 6,
                    "productCode": "4532dcfa-7d6f-4537-bce8-b2e804851810",
                    "quantity": 55,
                    "date": "2015-10-08",
                    "shipped": false
                }, {
                    "id": 62,
                    "companyId": 17,
                    "productCode": "5dd61e1f-b57a-49f5-9756-0e10bdb64e17",
                    "quantity": 81,
                    "date": "2015-10-17",
                    "shipped": true
                }, {
                    "id": 63,
                    "companyId": 22,
                    "productCode": "4041f1f0-2af2-455c-8f32-308839a7e4aa",
                    "quantity": 3,
                    "date": "2015-10-01",
                    "shipped": false
                }, {
                    "id": 64,
                    "companyId": 25,
                    "productCode": "a66ed669-91c2-4735-9180-7f2cdfdbbb22",
                    "quantity": 52,
                    "date": "2015-10-01",
                    "shipped": false
                }, {
                    "id": 65,
                    "companyId": 20,
                    "productCode": "5041e892-a6f1-4802-abb4-65aaf267bcaf",
                    "quantity": 38,
                    "date": "2015-10-10",
                    "shipped": false
                }, {
                    "id": 66,
                    "companyId": 22,
                    "productCode": "3046ced4-a66e-4c55-9b34-fdf48ade73df",
                    "quantity": 67,
                    "date": "2015-10-08",
                    "shipped": false
                }, {
                    "id": 67,
                    "companyId": 27,
                    "productCode": "8300ad8a-100d-4034-8737-a1a15c297d2c",
                    "quantity": 4,
                    "date": "2015-10-11",
                    "shipped": true
                }, {
                    "id": 68,
                    "companyId": 29,
                    "productCode": "01e5e215-7eed-42ba-bda5-b1f4fdfba651",
                    "quantity": 91,
                    "date": "2015-10-08",
                    "shipped": false
                }, {
                    "id": 69,
                    "companyId": 25,
                    "productCode": "9a62a393-98f7-4543-aa48-e998efdae9e0",
                    "quantity": 16,
                    "date": "2015-10-10",
                    "shipped": false
                }, {
                    "id": 70,
                    "companyId": 11,
                    "productCode": "6a5220df-2a1f-46fa-8a10-9674bfb88000",
                    "quantity": 22,
                    "date": "2015-10-14",
                    "shipped": false
                }, {
                    "id": 71,
                    "companyId": 27,
                    "productCode": "b9e2688b-b5c0-4398-8d80-8de8717364eb",
                    "quantity": 94,
                    "date": "2015-10-01",
                    "shipped": false
                }, {
                    "id": 72,
                    "companyId": 10,
                    "productCode": "152887fd-b2c1-4b17-8db6-bdbe925078cb",
                    "quantity": 2,
                    "date": "2015-10-03",
                    "shipped": false
                }, {
                    "id": 73,
                    "companyId": 17,
                    "productCode": "e689c83a-0661-474f-8221-b265e1458351",
                    "quantity": 75,
                    "date": "2015-10-07",
                    "shipped": true
                }, {
                    "id": 74,
                    "companyId": 29,
                    "productCode": "ecbafa1e-8fce-4d42-a5da-c200a8fad281",
                    "quantity": 85,
                    "date": "2015-10-09",
                    "shipped": true
                }, {
                    "id": 75,
                    "companyId": 22,
                    "productCode": "a538a290-9aee-46f2-afd6-1c87f3d17c46",
                    "quantity": 73,
                    "date": "2015-10-05",
                    "shipped": false
                }, {
                    "id": 76,
                    "companyId": 9,
                    "productCode": "b652d7f6-7fad-44e4-b35c-f439e44f9aa8",
                    "quantity": 21,
                    "date": "2015-10-04",
                    "shipped": true
                }, {
                    "id": 77,
                    "companyId": 17,
                    "productCode": "e6d46772-2aeb-4bec-8c69-cf598bd87680",
                    "quantity": 100,
                    "date": "2015-10-13",
                    "shipped": false
                }, {
                    "id": 78,
                    "companyId": 2,
                    "productCode": "a4add82a-af04-4da8-bd0a-79158e107ecc",
                    "quantity": 4,
                    "date": "2015-10-03",
                    "shipped": true
                }, {
                    "id": 79,
                    "companyId": 15,
                    "productCode": "2d922da7-b2d2-4c56-82c7-3645294f17dd",
                    "quantity": 66,
                    "date": "2015-10-09",
                    "shipped": false
                }, {
                    "id": 80,
                    "companyId": 25,
                    "productCode": "676f536a-15ff-4e8d-a554-10c3f1ab1f17",
                    "quantity": 76,
                    "date": "2015-10-16",
                    "shipped": false
                }, {
                    "id": 81,
                    "companyId": 8,
                    "productCode": "595f56e3-79c7-41e8-8415-e6895a1a85eb",
                    "quantity": 72,
                    "date": "2015-10-10",
                    "shipped": true
                }, {
                    "id": 82,
                    "companyId": 14,
                    "productCode": "b2c819cb-2ba9-4f4c-ae71-6ad974c4de44",
                    "quantity": 56,
                    "date": "2015-10-13",
                    "shipped": true
                }, {
                    "id": 83,
                    "companyId": 20,
                    "productCode": "3e6abcef-2398-4e6d-b7c5-6d66fead48fa",
                    "quantity": 97,
                    "date": "2015-10-11",
                    "shipped": false
                }, {
                    "id": 84,
                    "companyId": 14,
                    "productCode": "735250d0-3c0c-4f0b-8de6-6319a063eb19",
                    "quantity": 69,
                    "date": "2015-10-13",
                    "shipped": true
                }, {
                    "id": 85,
                    "companyId": 0,
                    "productCode": "d336e7c2-0448-4693-813f-5a851cc405a2",
                    "quantity": 16,
                    "date": "2015-10-06",
                    "shipped": false
                }, {
                    "id": 86,
                    "companyId": 29,
                    "productCode": "a25c7ba7-0592-449a-b445-4313a040af9e",
                    "quantity": 73,
                    "date": "2015-10-13",
                    "shipped": false
                }, {
                    "id": 87,
                    "companyId": 22,
                    "productCode": "e245cbe1-f212-4dde-bc0f-e450226e1c8b",
                    "quantity": 30,
                    "date": "2015-10-17",
                    "shipped": false
                }, {
                    "id": 88,
                    "companyId": 27,
                    "productCode": "b83bd24b-bd23-4ee5-aeb7-8ea90ce6c29d",
                    "quantity": 3,
                    "date": "2015-10-04",
                    "shipped": true
                }, {
                    "id": 89,
                    "companyId": 25,
                    "productCode": "cea3b7c8-6622-4d2a-8fff-141f71d754f4",
                    "quantity": 39,
                    "date": "2015-10-13",
                    "shipped": true
                }, {
                    "id": 90,
                    "companyId": 9,
                    "productCode": "7d0af54a-4bb6-4a3a-87ee-02d7b1d2e325",
                    "quantity": 90,
                    "date": "2015-10-01",
                    "shipped": false
                }, {
                    "id": 91,
                    "companyId": 11,
                    "productCode": "9b0635e2-b69c-4ad3-9e04-8bec4743da05",
                    "quantity": 24,
                    "date": "2015-10-03",
                    "shipped": true
                }, {
                    "id": 92,
                    "companyId": 20,
                    "productCode": "e044d02f-5a25-4b73-a811-ae59dd50d691",
                    "quantity": 65,
                    "date": "2015-10-06",
                    "shipped": false
                }, {
                    "id": 93,
                    "companyId": 3,
                    "productCode": "deed5afb-9345-47b1-b17a-10c0732322e6",
                    "quantity": 67,
                    "date": "2015-10-05",
                    "shipped": false
                }, {
                    "id": 94,
                    "companyId": 8,
                    "productCode": "193f8eaa-e32b-47bc-94d9-41b788a21393",
                    "quantity": 50,
                    "date": "2015-10-06",
                    "shipped": true
                }, {
                    "id": 95,
                    "companyId": 25,
                    "productCode": "5dc3c2b6-376f-4eba-881a-3a176a27d03c",
                    "quantity": 87,
                    "date": "2015-10-03",
                    "shipped": true
                }, {
                    "id": 96,
                    "companyId": 11,
                    "productCode": "9bcd6569-aa74-4da0-8417-bf975141e0d1",
                    "quantity": 60,
                    "date": "2015-10-17",
                    "shipped": true
                }, {
                    "id": 97,
                    "companyId": 0,
                    "productCode": "94ea0790-4ced-437d-8c45-12eed25b4e6d",
                    "quantity": 60,
                    "date": "2015-10-11",
                    "shipped": true
                }, {
                    "id": 98,
                    "companyId": 19,
                    "productCode": "042a63a6-f3f8-4154-86c0-b45ebd044b9f",
                    "quantity": 9,
                    "date": "2015-10-15",
                    "shipped": false
                }, {
                    "id": 99,
                    "companyId": 2,
                    "productCode": "eff2e580-dc1b-4faa-bef0-76a2a1bb50a3",
                    "quantity": 77,
                    "date": "2015-10-16",
                    "shipped": true
                }, {
                    "id": 100,
                    "companyId": 5,
                    "productCode": "d6d751d9-c70e-48dc-b09d-3a3587d308e6",
                    "quantity": 32,
                    "date": "2015-10-01",
                    "shipped": false
                }]
            }
        });
        store = new Ext.data.Store({
            model: 'spec.RowWidgetCompany',
            data: dummyData,
            autoDestroy: true
        });

        expander = new Ext.grid.plugin.RowWidget(Ext.apply({
            widget: {
                xtype: 'button',
                isExpanderButton: true,
                bind: '{record.company}'
            }
        }, rowWidgetCfg || {}));

        columns = gridCfg.columns || [
            { text: "Company", flex: 1, dataIndex: 'company' },
            { text: "Price", renderer: Ext.util.Format.usMoney, dataIndex: 'price' },
            { text: "Change", dataIndex: 'change' },
            { text: "% Change", dataIndex: 'pctChange' },
            { text: "Last Updated", renderer: Ext.util.Format.dateRenderer('m/d/Y'), dataIndex: 'lastChange' }
        ];

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: columns,
            viewConfig: {
                forceFit: true
            },
            width: 600,
            height: 300,
            plugins: expander,
            title: 'Widget Rows, Collapse and Force Fit',
            renderTo: document.body,
            leadingBufferZone: 1,
            trailingBufferZone: 1
        }, gridCfg));

        view = grid.getView();
        scroller = view.isLockingView ? view.normalView.getScrollable() : view.getScrollable();
        bufferedRenderer = view.bufferedRenderer;
    }

    function getElementBottom(el) {
        return el.dom.getBoundingClientRect().bottom;
    }

    function getRowBodyTr(index, locked) {
        view = locked ? expander.lockedView : expander.view;

        return Ext.fly(view.all.item(index).down('.' + Ext.baseCSSPrefix + 'grid-rowbody-tr', true));
    }

    beforeEach(function() {
        componentCount = Ext.ComponentQuery.query('*').length;
    });

    afterEach(function() {
        Ext.destroy(grid);
        store = expander = grid = columns = null;
        Ext.undefine('spec.RowWidgetCompany');
        Ext.undefine('spec.RowWidgetOrder');
        Ext.data.Model.schema.clear();

        // Check that all components have been cleaned up
        expect(Ext.ComponentQuery.query('*').length).toBe(componentCount);
    });

    describe("RowWidget", function() {
        it("should not expand in response to mousedown", function() {
            makeGrid();

            jasmine.fireMouseEvent(grid.view.el.query('.x-grid-row-expander')[0], 'mousedown');

            expect(getRowBodyTr(0).isVisible()).toBe(false);

            jasmine.fireMouseEvent(grid.view.el.query('.x-grid-row-expander')[0], 'mouseup');
        });

        it("should expand on click", function() {
            makeGrid();
            var yRange = scroller.getSize().y,
                layoutCounter = grid.view.componentLayoutCounter;

            jasmine.fireMouseEvent(grid.view.el.query('.x-grid-row-expander')[0], 'click');

            expect(getRowBodyTr(0).isVisible()).toBe(true);

            // Scroller's scroll range must have increased as a result of row expansion
            expect(scroller.getSize().y).toBeGreaterThan(yRange);

            // Expanding ust lay out in case it triggers overflow
            expect(grid.view.componentLayoutCounter).toBe(layoutCounter + 1);

            // Check that the widget is of the correct type and rendered and updated correctly.
            widget = expander.getWidget(grid.view, store.getAt(0));
            expect(widget.isButton).toBe(true);
            expect(widget === Ext.Component.from(grid.view.all.item(0).down('.' + Ext.baseCSSPrefix + 'grid-rowbody', true).firstChild)).toBe(true);

            // Flush the VM's data so we can work synchronously
            widget.lookupViewModel().notify();
            expect(widget.getText()).toBe(store.getAt(0).get('company'));
        });

        it("should collapse on click", function() {
            makeGrid();

            // start with row 0 expanded
            expander.toggleRow(0, store.getAt(0));
            var layoutCounter = grid.view.componentLayoutCounter;

            jasmine.fireMouseEvent(grid.view.el.query('.x-grid-row-expander')[0], 'click');

            expect(getRowBodyTr(0).isVisible()).toBe(false);

            // Collapsing ust lay out in case it triggers underflow
            expect(grid.view.componentLayoutCounter).toBe(layoutCounter + 1);
        });

        it('should only create widgets for the rendered viewSize', function() {
            makeGrid();

            // We intend to check that only widgets necessary to display the rendered block are created.
            var widgetCount = Ext.ComponentQuery.query('*').length;

            var viewSize = grid.bufferedRenderer.viewSize,
                storeCount = store.getCount(),
                item = 0,
                checkScrollEnd,
                node;

            waitsFor(checkScrollEnd = function(done) {
                // Click all rendered expanders until we hit the end of the rendered block
                // eslint-disable-next-line no-cond-assign
                while (node = view.all.item(item)) {
                    jasmine.fireMouseEvent(node.query('.x-grid-row-expander')[0], 'click');
                    item++;
                }

                if (item === storeCount) {
                    return done();
                }

                // When we hit the end of the rendered block, ask that the required
                // row be scrolled into view.
                grid.ensureVisible(item, {
                    callback: function() {
                        checkScrollEnd(done);
                    }
                });
            }, 'grid to scroll to end');
            // Wait up to 30 seconds for all rows to be expanded.

            runs(function() {
                // The total component count should be the initial count plus one row widget for every RENDERED row.
                // So that's "viewSize" widgets created.
                expect(Ext.ComponentQuery.query('*').length).toBe(widgetCount + viewSize + grid.freeRowContexts.length);
            });
        });

        it("should keep the widget in place when a column updates", function() {
            makeGrid();

            var rec = store.getAt(0);

            expander.toggleRow(0, rec);

            var btn = grid.down('[isExpanderButton]');

            expect(btn.el.parent(null, true)).toHaveCls('x-grid-rowbody');
            rec.set('company', 'Foo');
            expect(expect(btn.el.parent(null, true)).toHaveCls('x-grid-rowbody'));
        });

        describe("with scrollIntoViewOnExpand", function() {
            it("should scroll the full row body into view", function() {
                var viewBottom, rowBottom;

                makeGrid(null, {
                    scrollIntoViewOnExpand: true
                });

                expander.toggleRow(12, store.getAt(12));
                // measure position of row vs. height of view
                viewBottom = getElementBottom(view.el);
                rowBottom = getElementBottom(getRowBodyTr(12));
                // row body should be scrolled into view
                expect(rowBottom).not.toBeGreaterThan(viewBottom);
            });

            describe("with locked columns", function() {
                function makeLockedGrid(tall) {
                    var smallWidget = {
                            xtype: 'button',
                            bind: '{record.company}'
                        },
                        tallWidget = {
                            xtype: 'button',
                            bind: '{record.company}',
                            height: 100
                        };

                    makeGrid({
                        columns: [
                            { text: "Company", width: 200, dataIndex: 'company', locked: true },
                            { text: "Price", renderer: Ext.util.Format.usMoney, dataIndex: 'price' },
                            { text: "Change", dataIndex: 'change' }
                        ]
                    }, {
                        scrollIntoViewOnExpand: true,
                        widget: tall ? tallWidget : smallWidget,
                        lockedWidget: tall ? smallWidget : tallWidget
                    });
                }

                it("should be able to focus a component in the normal view", function() {
                    makeLockedGrid(false);

                    expander.toggleRow(1, store.getAt(1));
                    widget = expander.getWidget(grid.normalGrid.view, store.getAt(1));

                    waitsFor(function() {
                        return widget.rendered;
                    });

                    runs(function() {
                        jasmine.fireMouseEvent(widget, 'click');
                    });

                    waitsFor(function() {
                        return widget.hasFocus;
                    });

                    runs(function() {
                        expect(Ext.fly(widget.ownerCmp.getRow(1)).selectNode('.x-grid-cell')).not.toHaveCls('x-grid-item-focused');
                    });
                });

                it("should use the lockedWidget content (when it is taller) to determine scroll distance", function() {
                    var viewBottom, rowBottom;

                    makeLockedGrid(false);

                    expander.toggleRow(12, store.getAt(12));

                    waits(200);
                    runs(function() {
                        // measure position of row vs. height of view
                        viewBottom = getElementBottom(expander.lockedView.el);
                        rowBottom = getElementBottom(getRowBodyTr(12, true));
                        // row body should be scrolled into view
                        expect(rowBottom).not.toBeGreaterThan(viewBottom);
                    });
                });

                it("should use the widget content (when it is taller) to determine scroll distance", function() {
                    var viewBottom, rowBottom;

                    makeLockedGrid(true);

                    expander.toggleRow(12, store.getAt(12));

                    waits(200);
                    runs(function() {
                        // measure position of row vs. height of view
                        viewBottom = getElementBottom(expander.normalView.el);
                        rowBottom = getElementBottom(getRowBodyTr(12, false));
                        // row body should be scrolled into view
                        expect(rowBottom).not.toBeGreaterThan(viewBottom);
                    });
                });
            });
        });

        describe("with a lockedWidget", function() {
            beforeEach(function() {
                makeGrid({
                    syncRowHeight: false,
                    columns: [
                        { text: "Company", width: 200, dataIndex: 'company', locked: true },
                        { text: "Price", renderer: Ext.util.Format.usMoney, dataIndex: 'price' },
                        { text: "Change", dataIndex: 'change' },
                        { text: "% Change", dataIndex: 'pctChange' },
                        { text: "Last Updated", renderer: Ext.util.Format.dateRenderer('m/d/Y'), dataIndex: 'lastChange' }
                    ]
              }, {
                    widget: {
                        xtype: 'button',
                        defaultBindProperty: 'company',
                        bind: '{record.company}',
                        setCompany: function(name) {
                            this.setText(name);
                        }
                    },
                    lockedWidget: {
                        xtype: 'component',
                        defaultBindProperty: 'industry',
                        bind: '{record.industry}',
                        setIndustry: function(name) {
                            this.setHtml(name);
                        }
                    }
                });
            });

            it("should not expand in response to mousedown", function() {
                jasmine.fireMouseEvent(grid.lockedGrid.view.el.query('.x-grid-row-expander')[0], 'mousedown');

                expect(getRowBodyTr(0, true).isVisible()).toBe(false);

                jasmine.fireMouseEvent(grid.lockedGrid.view.el.query('.x-grid-row-expander')[0], 'mouseup');
            });

            it("should expand on click", function() {
                jasmine.fireMouseEvent(grid.lockedGrid.view.el.query('.x-grid-row-expander')[0], 'click');

                expect(getRowBodyTr(0, true).isVisible()).toBe(true);

                expect(grid.lockedGrid.view.body.getHeight()).toBe(grid.normalGrid.view.body.getHeight());
            });

            it("should collapse on click", function() {
                // start with row 0 expanded
                expander.toggleRow(0, store.getAt(0));

                // click to collapse
                jasmine.fireMouseEvent(grid.lockedGrid.view.el.query('.x-grid-row-expander')[0], 'click');

                // The rowbody row of item 0 should not be visible
                expect(getRowBodyTr(0, true).isVisible()).toBe(false);

                // Check the content of the rowbody in the locked side.
                // The lockedWidget specifies that it be a component with the textual content being the industry field.
                widget = expander.getWidget(grid.lockedGrid.view, store.getAt(0));
                widget.lookupViewModel().notify();
                expect(widget.isComponent).toBe(true);
                expect(widget === Ext.Component.from(grid.lockedGrid.view.all.item(0).down('.' + Ext.baseCSSPrefix + 'grid-rowbody', true).firstChild)).toBe(true);
                expect(widget.el.dom.textContent || widget.el.dom.innerText).toBe(store.getAt(0).get('industry'));

                // Check thetwo rows (one on each side) are synched in height
                // The lockedWidget specifies that it be the industry field.
                expect(grid.lockedGrid.view.all.item(0).getHeight()).toBe(grid.normalGrid.view.all.item(0).getHeight());
            });
        });

        describe('striping rows', function() {
            describe('normal grid', function() {
                it("should place the altRowCls on the view row's ancestor row", function() {
                    // The .x-grid-item-alt class is now placed on the view *item*. The row table.
                    // See EXTJSIV-612.
                    makeGrid();

                    var node = grid.view.getNode(store.getAt(1));

                    expect(Ext.fly(node).hasCls('x-grid-item-alt')).toBe(true);
                });
            });

            describe('locked grid', function() {
                it("should place the altRowCls on the view row's ancestor row", function() {
                    // The .x-grid-item-alt class is now placed on the view *item*. The row table.
                    // See EXTJSIV-612.
                    makeGrid({
                        columns: [
                            { text: 'Company', dataIndex: 'company', locked: true },
                            { text: 'Price', dataIndex: 'price', locked: true },
                            { text: 'Change', dataIndex: 'change' },
                            { text: '% Change', dataIndex: 'pctChange' },
                            { text: 'Last Updated', dataIndex: 'lastChange' }
                        ]
                    });

                    var lockedNode = grid.view.getNode(store.getAt(1)),
                        normalNode = grid.normalGrid.view.getNode(store.getAt(1));

                    expect(Ext.fly(lockedNode).hasCls('x-grid-item-alt')).toBe(true);
                    expect(Ext.fly(normalNode).hasCls('x-grid-item-alt')).toBe(true);
                });

                it("should sync row heights when buffered renderer adds new rows during scroll", function() {
                    makeGrid({
                        leadingBufferZone: 2,
                        trailingBufferZone: 2,
                        height: 100,
                        columns: [
                            { text: 'Company', dataIndex: 'company', locked: true },
                            { text: 'Price', dataIndex: 'price', locked: true },
                            { text: 'Change', dataIndex: 'change' },
                            { text: '% Change', dataIndex: 'pctChange' },
                            { text: 'Last Updated', dataIndex: 'lastChange' }
                        ]
                    });

                    // Get the expander elements to click on
                    var expanders = grid.view.el.query('.x-grid-row-expander'),
                        lockedView = grid.lockedGrid.view,
                        normalView = grid.normalGrid.view,
                        lockedBR = lockedView.bufferedRenderer,
                        normalBR = normalView.bufferedRenderer,
                        item0CollapsedHeight = lockedView.all.item(0, true).offsetHeight,
                        item0ExpandedHeight;

                    // Expand first row
                    jasmine.fireMouseEvent(expanders[0], 'click');

                    item0ExpandedHeight = lockedView.all.item(0, true).offsetHeight;

                    // item 0 should have expanded
                    expect(item0ExpandedHeight).toBeGreaterThan(item0CollapsedHeight);

                    // Locked side's item 0 should have synced height
                    expect(normalView.all.item(0, true).offsetHeight).toBe(item0ExpandedHeight);

                    normalView.setScrollY(1000);

                    waits(500);
                    runs(function() {
                        // Everything must be in sync
                        expect(normalBR.bodyTop).toBe(lockedBR.bodyTop);
                        expect(normalBR.scrollTop).toBe(lockedBR.scrollTop);
                        expect(normalBR.position).toBe(lockedBR.position);
                        expect(normalBR.rowHeight).toBe(lockedBR.rowHeight);
                        expect(normalBR.bodyHeight).toBe(lockedBR.bodyHeight);
                        expect(normalBR.viewClientHeight).toBe(lockedBR.viewClientHeight);

                        normalView.setScrollY(0);
                    });

                    waits(500);
                    runs(function() {
                        // We must be at position zero
                        expect(lockedBR.bodyTop).toBe(0);
                        expect(lockedBR.scrollTop).toBe(0);
                        expect(lockedBR.position).toBe(0);

                        // Everything must be in sync
                        expect(normalBR.bodyTop).toBe(lockedBR.bodyTop);
                        expect(normalBR.scrollTop).toBe(lockedBR.scrollTop);
                        expect(normalBR.position).toBe(lockedBR.position);
                        expect(normalBR.rowHeight).toBe(lockedBR.rowHeight);
                        expect(normalBR.bodyHeight).toBe(lockedBR.bodyHeight);
                        expect(normalBR.viewClientHeight).toBe(lockedBR.viewClientHeight);

                        // We scrolled the normal view, and the locked view should have had its newly rendered row 0 height synced
                        expect(lockedView.all.item(0, true).offsetHeight).toBe(item0ExpandedHeight);
                    });
                });
            });
        });

        it('should work when defined in a subclass', function() {
            // The point of this spec is to demonstrate that the RowWidget plugin, which depends on the
            // RowBody grid feature, will still be properly constructed and rendered when defined in initComponent
            // in a subclass of grid (really, anything that has panel.Table as an ancestor class).
            //
            // The bug was that the plugin configured in the derived class' initComponent would not be properly
            // rendered since it would be created AFTER the table view was created (and the view needs to know
            // about all its features at construction time). Thus, checking its features length is sufficient to
            // show that it's been fixed.
            // See EXTJSIV-EXTJSIV-11927.
            makeGrid({
                xhooks: {
                    initComponent: function() {
                        Ext.apply(this, {
                            store: [],
                            columns: [],
                            plugins: [{
                                ptype: 'rowwidget',
                                widget: {
                                    xtype: 'button',
                                    defaultBindProperty: 'company',
                                    setCompany: function(company) {
                                        this.setText(company.get('company'));
                                    }
                                }
                            }]
                        });

                        this.callParent(arguments);
                    }
                }
            });

            expect(grid.view.features.length).toBe(1);
        });

        it('should insert a colspan attribute on the rowwrap cell equal to the number of grid columns', function() {
            makeGrid({
                columns: [
                    { text: 'Company', dataIndex: 'company' },
                    { text: 'Price', dataIndex: 'price' },
                    { text: 'Change', dataIndex: 'change' },
                    { text: '% Change', dataIndex: 'pctChange' },
                    { text: 'Last Updated', dataIndex: 'lastChange' }
                ]
            });

            // Grid columns + row expander column = 5.
            // There is a real cell below the expnder cell.
            expect(parseInt(grid.body.down('.x-grid-cell-rowbody', true).getAttribute('colspan'), 10)).toBe(5);
        });

        itNotIE8('should expand the buffered rendering scroll range when at the bottom and the row is expanded', function() {
            makeGrid({
                leadingBufferZone: 2,
                trailingBufferZone: 2,
                height: 100
            });

            expect(bufferedRenderer).toBeDefined();

            // Scroll until last row visible
            jasmine.waitsForScroll(scroller, function(scroller, x, y) {
                if (view.all.endIndex === store.getCount() - 1) {
                    return true;
                }

                scroller.scrollBy(0, 25);
            }, 'scroll until last record is rendered', 20000);

            runs(function() {
                // Get the expander elements to click on
                var expanders = view.el.query('.x-grid-row-expander'),
                    scroller = view.getScrollable(),
                    scrollHeight = scroller.getSize().y;

                // Expand last row
                jasmine.fireMouseEvent(expanders[expanders.length - 1], 'click');

                // Scroll range must have increased.
                expect(scroller.getSize().y).toBeGreaterThan(scrollHeight);
            });
        });

        describe('locking grid', function() {
            describe('no initial locked columns', function() {
                beforeEach(function() {
                    makeGrid({
                        enableLocking: true
                    });
                });

                it('should add the expander column to the normal grid', function() {
                    expect(expander.expanderColumn.up('tablepanel')).toBe(grid.normalGrid);
                });

                it('should hide the locked grid', function() {
                    expect(grid.lockedGrid.hidden).toBe(true);
                });

                it('should move the expander column to the locked grid when first column is locked', function() {
                    // Pass in an active header. Don't use the first column in the stack (it's the rowwidget column)!
                    grid.lock(grid.columnManager.getColumns()[1]);

                    expect(expander.expanderColumn.up('tablepanel')).toBe(grid.lockedGrid);
                });
            });

            describe('has locked columns', function() {
                beforeEach(function() {
                    makeGrid({
                        columns: [
                            { text: 'Company', locked: true, dataIndex: 'company' },
                            { text: 'Price', dataIndex: 'price' },
                            { text: 'Change', dataIndex: 'change' },
                            { text: '% Change', dataIndex: 'pctChange' },
                            { text: 'Last Updated', dataIndex: 'lastChange' }
                        ]
                    });
                });

                it('should add the expander column to the locked grid', function() {
                    expect(expander.expanderColumn.up('tablepanel')).toBe(grid.lockedGrid);
                });

                it('should not hide the locked grid', function() {
                    expect(grid.lockedGrid.hidden).toBe(false);
                });

                it('should move the expander column to the normal grid when there are no locked columns', function() {
                    // Pass in an active header. Don't use the first column in the stack (it's the rowwidget column)!
                    grid.unlock(grid.columnManager.getColumns()[1]);

                    expect(grid.lockedGrid);
                    expect(expander.expanderColumn.up('tablepanel')).toBe(grid.normalGrid);
                });
            });
        });
    });

    describe("reconfigure", function() {
        it("should should place widgets when setting a new store", function() {
            makeGrid();
            expander.toggleRow(0, store.getAt(0));
            var newStore = new Ext.data.Store({
                model: 'spec.RowWidgetCompany',
                data: [{
                    company: 'Foo'
                }]
            });

            grid.setStore(newStore);
            expander.toggleRow(0, newStore.getAt(0));
            newStore.sort('company');
            var body = grid.el.dom.querySelector('.x-grid-rowbody');

            expect(body.querySelector('.x-btn')).not.toBeNull();
        });
    });

    describe('Embedded grid', function() {
        var loadSpy;

        beforeEach(function() {
            makeGrid({
                leadingBufferZone: 10,
                trailingBufferZone: 10,
                height: 200
            }, {
                widget: {
                    xtype: 'container',
                    bind: {},
                    layout: {
                        type: 'hbox',
                        align: 'stretchmax'
                    },
                    items: [{
                        xtype: 'component',
                        html: lorem,
                        maxWidth: 300
                    }, {
                        xtype: 'grid',
                        flex: 1,
                        autoLoad: true,
                        bind: {
                            store: '{record.orders}',
                            title: 'Orders for {record.name}'
                        },
                        columns: [{
                            text: 'Order Id',
                            dataIndex: 'id',
                            width: 75
                        }, {
                            text: 'Procuct code',
                            dataIndex: 'productCode',
                            width: 290
                        }, {
                            text: 'Quantity',
                            dataIndex: 'quantity',
                            xtype: 'numbercolumn',
                            width: 75,
                            align: 'right'
                        }, {
                            xtype: 'datecolumn',
                            format: 'Y-m-d',
                            width: 120,
                            text: 'Date',
                            dataIndex: 'date'
                        }, {
                            text: 'Shipped',
                            xtype: 'checkcolumn',
                            dataIndex: 'shipped',
                            width: 75
                        }]
                    }]
                }
            });
            loadSpy = spyOn(Ext.data.ProxyStore.prototype, 'load').andCallThrough();
        });

        itNotIE8('should work', function() {
            var layoutCount = view.componentLayoutCounter,
                scrollRange = scroller.getSize().y;

            jasmine.fireMouseEvent(view.el.query('.x-grid-row-expander')[0], 'click');

            // Must have been one layout, and scroll range must have expanded
            expect(view.componentLayoutCounter).toBe(layoutCount + 1);
            expect(scroller.getSize().y).toBeGreaterThan(scrollRange);
            expect(loadSpy.callCount).toBe(1);

            layoutCount = view.componentLayoutCounter;
            scrollRange = scroller.getSize().y;

            expect(view.componentLayoutCounter).toBe(layoutCount);

            jasmine.fireMouseEvent(view.el.query('.x-grid-row-expander')[5], 'click');

            // Must have been one layout, and scroll range must have expanded
            expect(view.componentLayoutCounter).toBe(layoutCount + 1);
            expect(scroller.getSize().y).toBeGreaterThan(scrollRange);
            expect(loadSpy.callCount).toBe(2);

            layoutCount = view.componentLayoutCounter;
            scrollRange = scroller.getSize().y;

            expect(view.componentLayoutCounter).toBe(layoutCount);

            jasmine.waitsForScroll(scroller, function(s, x, y) {
                if (y === scroller.getMaxUserPosition().y &&
                    (view.all.endIndex === store.getCount() - 1)) {
                    return true;
                }

                scroller.scrollBy(0, 100);
             }, 'scroll to end', 20000);

            // No more loads.
            runs(function() {
                expect(loadSpy.callCount).toBe(2);
            });
        });

        itNotIE8('should correctly resize rendered block when last row expands', function() {
            var lastRow;

            waitsFor(function() {
                if (scroller.getPosition().y === scroller.getMaxUserPosition().y &&
                    view.all.endIndex === store.getCount() - 1) {
                    return true;
                }

                scroller.scrollBy(null, 100);
            }, 'scroll to end', 500);

            runs(function() {
                lastRow = view.all.last(true);
                jasmine.fireMouseEvent(Ext.fly(lastRow).down('.x-grid-row-expander', true), 'click');
            });

            waitsFor(function() {

                if (scroller.getPosition().y === scroller.getMaxUserPosition().y &&
                    view.all.endIndex === store.getCount() - 1) {
                    return true;
                }

                scroller.scrollBy(null, 100);
            }, 'scroll to end after row expansion', 500);

            // Last row should still be the same
            runs(function() {
                expect(view.all.last(true)).toBe(lastRow);
            });
        });
    });
});
