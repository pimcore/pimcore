/**
 * @class Ext.chart.series.CandleStick
 * @extends Ext.chart.series.Cartesian
 * 
 * Creates a candlestick or OHLC Chart.
 *
 * CandleStick series are typically used to plot price movements of a security on an exchange
 * over time. The series can be used with the 'time' axis, but since exchanges often close
 * for weekends, and the price data has gaps for those days, it's more practical to use this series
 * with the 'category' axis to avoid rendering those data gaps. The 'category' axis has no notion
 * of time (and thus gaps) and treats every Date object (value of the 'xField') as a unique
 * category. However, it also means that it doesn't support the 'dateFormat' config,
 * which can be easily remedied with a 'renderer' that formats a Date object for use
 * as an axis label. For example:
 *
 *     @example
 *     new Ext.chart.CartesianChart({
 *         xtype: 'cartesian',
 *         renderTo: document.body,
 *         width: 700,
 *         height: 500,
 *         insetPadding: 20,
 *         innerPadding: '0 20 0 20',
 *
 *         store: {
 *             data: [
 *                 {
 *                     time: new Date('Nov 17 2016'),
 *                     o: 52.40, h: 52.74, l: 52.18, c: 52.29
 *                 },
 *                 {
 *                     time: new Date('Nov 18 2016'),
 *                     o: 51.87, h: 52.22, l: 51.51, c: 52.04
 *                 },
 *                 {
 *                     time: new Date('Nov 21 2016'),
 *                     o: 53.02, h: 53.40, l: 53.02, c: 53.33
 *                 },
 *                 {
 *                     time: new Date('Nov 22 2016'),
 *                     o: 53.48, h: 53.80, l: 53.13, c: 53.70
 *                 },
 *                 {
 *                     time: new Date('Nov 23 2016'),
 *                     o: 52.85, h: 53.39, l: 52.76, c: 53.28
 *                 },
 *                 {
 *                     time: new Date('Nov 25 2016'),
 *                     o: 53.28, h: 53.45, l: 53.20, c: 53.40
 *                 },
 *                 {
 *                     time: new Date('Nov 28 2016'),
 *                     o: 52.51, h: 52.58, l: 51.96, c: 52.00
 *                 },
 *                 {
 *                     time: new Date('Nov 29 2016'),
 *                     o: 51.25, h: 51.98, l: 51.10, c: 51.79
 *                 },
 *                 {
 *                     time: new Date('Nov 30 2016'),
 *                     o: 53.65, h: 54.56, l: 53.60, c: 54.17
 *                 },
 *                 {
 *                     time: new Date('Dec 01 2016'),
 *                     o: 55.26, h: 55.75, l: 54.94, c: 55.13
 *                 }
 *             ]
 *         },
 *         axes: [
 *             {
 *                 type: 'numeric',
 *                 position: 'left'
 *             },
 *             {
 *                 type: 'category',
 *                 position: 'bottom',
 *
 *                 renderer: function (axis, value) {
 *                     return Ext.Date.format(value, 'M j\nY');
 *                 }
 *             }
 *         ],
 *         series: {
 *             type: 'candlestick',
 *
 *             xField: 'time',
 *
 *             openField: 'o',
 *             highField: 'h',
 *             lowField: 'l',
 *             closeField: 'c',
 *
 *             style: {
 *                 barWidth: 10,
 *
 *                 dropStyle: {
 *                     fill: 'rgb(222, 87, 87)',
 *                     stroke: 'rgb(222, 87, 87)',
 *                     lineWidth: 3
 *                 },
 *                 raiseStyle: {
 *                     fill: 'rgb(48, 189, 167)',
 *                     stroke: 'rgb(48, 189, 167)',
 *                     lineWidth: 3
 *                 }
 *             }
 *         }
 *     });
 */
Ext.define('Ext.chart.series.CandleStick', {
    extend: 'Ext.chart.series.Cartesian',
    requires: ['Ext.chart.series.sprite.CandleStick'],
    alias: 'series.candlestick',
    type: 'candlestick',
    seriesType: 'candlestickSeries',

    isCandleStick: true,

    config: {
        /**
         * @cfg {String} openField
         * The store record field name that represents the opening value of the given period.
         */
        openField: null,
        /**
         * @cfg {String} highField
         * The store record field name that represents the highest value of the time interval
         * represented.
         */
        highField: null,
        /**
         * @cfg {String} lowField
         * The store record field name that represents the lowest value of the time interval
         * represented.
         */
        lowField: null,
        /**
         * @cfg {String} closeField
         * The store record field name that represents the closing value of the given period.
         */
        closeField: null
    },

    fieldCategoryY: ['Open', 'High', 'Low', 'Close'],

    themeColorCount: function() {
        return 2;
    }
});
