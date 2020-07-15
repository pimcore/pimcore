/**
 * This plugin provides drag and drop functionality for a {@link Ext.grid.View GridView}.
 *
 * A specialized instance of {@link Ext.dd.DragZone DragZone} and {@link Ext.dd.DropZone 
 * DropZone} are attached to the grid view.  The DropZone will participate in drops 
 * from DragZones having the same {@link #ddGroup} including drops from within the same 
 * grid.
 *
 * Note that where touch gestures are available, the `longpress` gesture will initiate
 * the drag in order that the `touchstart` may still be used to initiate a scroll.
 *
 * On platforms which implement the [Pointer Events standard](https://www.w3.org/TR/pointerevents/)
 * (IE), the `touchstart` event is usually claimed by the platform, however, this plugin
 * uses the `longpress` event to trigger drags, so `touchstart` will not initiate a scroll.
 * On these platforms, a two finger drag gesture will scroll the content, or a single
 * finger drag on an empty area of the view will scroll the content.
 *
 * During the drop operation a data object is passed to a participating DropZone's drop 
 * handlers.  The drag data object has the following properties:
 *
 * - **copy:** {@link Boolean} <br> The value of {@link #copy}.  Or `true` if 
 * {@link #allowCopy} is true **and** the control key was pressed as the drag operation 
 * began.
 * 
 * - **view:** {@link Ext.grid.View GridView} <br> The source grid view from which the 
 * drag originated
 * 
 * - **ddel:** HTMLElement <br> The drag proxy element which moves with the cursor
 * 
 * - **item:** HTMLElement <br> The grid view node upon which the mousedown event was 
 * registered
 * 
 * - **records:** {@link Array} <br> An Array of {@link Ext.data.Model Model}s 
 * representing the selected data being dragged from the source grid view
 *
 * By adding this plugin to a view, two new events will be fired from the client 
 * grid view as well as its owning Grid: `{@link #beforedrop}` and `{@link #drop}`.
 *
 *     @example
 *     var store = Ext.create('Ext.data.Store', {
 *         fields: ['name'],
 *         data: [
 *             ["Lisa"],
 *             ["Bart"],
 *             ["Homer"],
 *             ["Marge"]
 *         ],
 *         proxy: {
 *             type: 'memory',
 *             reader: 'array'
 *         }
 *     });
 *     
 *     Ext.create('Ext.grid.Panel', {
 *         store: store,
 *         enableLocking: true,
 *         columns: [{
 *             header: 'Name',
 *             dataIndex: 'name',
 *             flex: true
 *         }],
 *         viewConfig: {
 *             plugins: {
 *                 gridviewdragdrop: {
 *                     dragText: 'Drag and drop to reorganize'
 *                 }
 *             }
 *         },
 *         height: 200,
 *         width: 400,
 *         renderTo: Ext.getBody()
 *     });
 */
Ext.define('Ext.grid.plugin.DragDrop', {
    extend: 'Ext.plugin.Abstract',
    alias: 'plugin.gridviewdragdrop',

    uses: [
        'Ext.view.DragZone',
        'Ext.grid.ViewDropZone'
    ],

    /**
     * @event beforedrop
     * **This event is fired through the {@link Ext.grid.View GridView} and its owning 
     * {@link Ext.grid.Panel Grid}. You can add listeners to the grid or grid {@link 
     * Ext.grid.Panel#viewConfig view config} object**
     *
     * Fired when a drop gesture has been triggered by a mouseup event in a valid drop 
     * position in the grid view.
     * 
     * Returning `false` to this event signals that the drop gesture was invalid and 
     * animates the drag proxy back to the point from which the drag began.
     * 
     * The dropHandlers parameter can be used to defer the processing of this event. For 
     * example, you can force the handler to wait for the result of a message box 
     * confirmation or an asynchronous server call (_see the details of the dropHandlers 
     * property for more information_).
     *  
     *     grid.on('beforedrop', function(node, data, overModel, dropPosition, dropHandlers) {
     *         // Defer the handling
     *         dropHandlers.wait = true;
     *         Ext.MessageBox.confirm('Drop', 'Are you sure', function(btn){
     *             if (btn === 'yes') {
     *                 dropHandlers.processDrop();
     *             } else {
     *                 dropHandlers.cancelDrop();
     *             }
     *         });
     *     });
     * 
     * Any other return value continues with the data transfer operation unless the wait 
     * property is set.
     *
     * @param {HTMLElement} node The {@link Ext.grid.View grid view} node **if any** over 
     * which the cursor was positioned.
     *
     * @param {Object} data The data object gathered at mousedown time by the 
     * cooperating {@link Ext.dd.DragZone DragZone}'s {@link Ext.dd.DragZone#getDragData 
     * getDragData} method.  It contains the following properties:
     * @param {Boolean} data.copy The value of {@link #copy}.  Or `true` if 
     * {@link #allowCopy} is true **and** the control key was pressed as the drag 
     * operation began.
     * @param {Ext.grid.View} data.view The source grid view from which the drag 
     * originated
     * @param {HTMLElement} data.ddel The drag proxy element which moves with the cursor
     * @param {HTMLElement} data.item The grid view node upon which the mousedown event 
     * was registered
     * @param {Ext.data.Model[]} data.records An Array of Models representing the 
     * selected data being dragged from the source grid view
     *
     * @param {Ext.data.Model} overModel The Model over which the drop gesture took place
     *
     * @param {String} dropPosition `"before"` or `"after"` depending on whether the 
     * cursor is above or below the mid-line of the node.
     *
     * @param {Object} dropHandlers
     * This parameter allows the developer to control when the drop action takes place. 
     * It is useful if any asynchronous processing needs to be completed before 
     * performing the drop. This object has the following properties:
     * 
     * @param {Boolean} dropHandlers.wait Indicates whether the drop should be deferred. 
     * Set this property to true to defer the drop.
     * @param {Function} dropHandlers.processDrop A function to be called to complete 
     * the drop operation.
     * @param {Function} dropHandlers.cancelDrop A function to be called to cancel the 
     * drop operation.
     */

    /**
     * @event drop
     * **This event is fired through the {@link Ext.grid.View GridView} and its owning 
     * {@link Ext.grid.Panel Grid}. You can add listeners to the grid or grid {@link 
     * Ext.grid.Panel#viewConfig view config} object**
     * 
     * Fired when a drop operation has been completed and the data has been moved or 
     * copied.
     *
     * @param {HTMLElement} node The {@link Ext.grid.View GridView} node **if any** over 
     * which the cursor was positioned.
     *
     * @param {Object} data The data object gathered at mousedown time by the 
     * cooperating {@link Ext.dd.DragZone DragZone}'s {@link Ext.dd.DragZone#getDragData 
     * getDragData} method.  It contains the following properties:
     * @param {Boolean} data.copy The value of {@link #copy}.  Or `true` if 
     * {@link #allowCopy} is true **and** the control key was pressed as the drag 
     * operation began.
     * @param {Ext.grid.View} data.view The source grid view from which the drag 
     * originated
     * @param {HTMLElement} data.ddel The drag proxy element which moves with the cursor
     * @param {HTMLElement} data.item The grid view node upon which the mousedown event 
     * was registered
     * @param {Ext.data.Model[]} data.records An Array of Models representing the 
     * selected data being dragged from the source grid view
     *
     * @param {Ext.data.Model} overModel The Model over which the drop gesture took 
     * place.
     *
     * @param {String} dropPosition `"before"` or `"after"` depending on whether the 
     * cursor is above or below the mid-line of the node.
     */

    /**
     * @cfg {Boolean} [copy=false]
     * Set as `true` to copy the records from the source grid to the destination drop 
     * grid.  Otherwise, dragged records will be moved.
     * 
     * **Note:** This only applies to records dragged between two different grids with 
     * unique stores.
     * 
     * See {@link #allowCopy} to allow only control-drag operations to copy records.
     */

    /**
     * @cfg {Boolean} [allowCopy=false]
     * Set as `true` to allow the user to hold down the control key at the start of the 
     * drag operation and copy the dragged records between grids.  Otherwise, dragged 
     * records will be moved.
     * 
     * **Note:** This only applies to records dragged between two different grids with 
     * unique stores.
     * 
     * See {@link #copy} to enable the copying of all dragged records.
     */

    /**
     * @cfg {String} dragText
     * The text to show while dragging.
     *
     * Two placeholders can be used in the text:
     *
     * - `{0}` The number of selected items.
     * - `{1}` 's' when more than 1 items (only useful for English).
     * @locale
     */
    dragText: '{0} selected row{1}',

    /**
     * @cfg {String} [ddGroup=gridDD]
     * A named drag drop group to which this object belongs. If a group is specified, then both
     * the DragZones and DropZone used by this plugin will only interact with other drag drop
     * objects in the same group.
     */
    ddGroup: "GridDD",

    /**
     * @cfg {String} [dragGroup]
     * The {@link #ddGroup} to which the DragZone will belong.
     *
     * This defines which other DropZones the DragZone will interact with. Drag/DropZones
     * only interact with other Drag/DropZones which are members of the same {@link #ddGroup}.
     */

    /**
     * @cfg {String} [dropGroup]
     * The {@link #ddGroup} to which the DropZone will belong.
     *
     * This defines which other DragZones the DropZone will interact with. Drag/DropZones
     * only interact with other Drag/DropZones which are members of the same {@link #ddGroup}.
     */

    /**
     * @cfg {Boolean} enableDrop
     * `false` to disallow the View from accepting drop gestures.
     */
    enableDrop: true,

    /**
     * @cfg {Boolean} enableDrag
     * `false` to disallow dragging items from the View.
     */
    enableDrag: true,

    /**
     * `true` to register this container with the Scrollmanager for auto scrolling during drag
     * operations. A {@link Ext.dd.ScrollManager} configuration may also be passed.
     * @cfg {Object/Boolean} containerScroll
     */
    containerScroll: false,

    /**
     * @cfg {Object} [dragZone]
     * A config object to apply to the creation of the {@link #property-dragZone DragZone}
     * which handles for drag start gestures.
     *
     * Template methods of the DragZone may be overridden using this config.
     */

    /**
     * @cfg {Object} [dropZone]
     * A config object to apply to the creation of the {@link #property-dropZone DropZone}
     * which handles mouseover and drop gestures.
     *
     * Template methods of the DropZone may be overridden using this config.
     */

    /**
     * @property {Ext.view.DragZone} dragZone
     * An {@link Ext.view.DragZone DragZone} which handles mousedown and dragging of records
     * from the grid.
     */

    /**
     * @property {Ext.grid.ViewDropZone} dropZone
     * An {@link Ext.grid.ViewDropZone DropZone} which handles mouseover and dropping records
     * in any grid which shares the same {@link #dropGroup}.
     */

    init: function(view) {
        Ext.applyIf(view, {
            copy: this.copy,
            allowCopy: this.allowCopy
        });

        view.on('render', this.onViewRender, this, { single: true });
    },

    /**
     * @private
     * Component calls destroy on all its plugins at destroy time.
     */
    destroy: function() {
        var me = this;

        me.dragZone = me.dropZone = Ext.destroy(me.dragZone, me.dropZone);
        me.callParent();
    },

    enable: function() {
        var me = this;

        if (me.dragZone) {
            me.dragZone.unlock();
        }

        if (me.dropZone) {
            me.dropZone.unlock();
        }

        me.callParent();
    },

    disable: function() {
        var me = this;

        if (me.dragZone) {
            me.dragZone.lock();
        }

        if (me.dropZone) {
            me.dropZone.lock();
        }

        me.callParent();
    },

    onViewRender: function(view) {
        var me = this,
            ownerGrid = view.ownerCt.ownerGrid || view.ownerCt,
            dragZone = me.dragZone || {};

        ownerGrid.relayEvents(view, ['beforedrop', 'drop']);

        if (me.enableDrag) {
            if (me.containerScroll) {
                dragZone.scrollEl = view.getEl();
                dragZone.containerScroll = true;
            }

            me.dragZone = new Ext.view.DragZone(Ext.apply({
                view: view,
                ddGroup: me.dragGroup || me.ddGroup,
                dragText: me.dragText
            }, dragZone));
        }

        if (me.enableDrop) {
            me.dropZone = new Ext.grid.ViewDropZone(Ext.apply({
                view: view,
                ddGroup: me.dropGroup || me.ddGroup
            }, me.dropZone));
        }
    }
});
