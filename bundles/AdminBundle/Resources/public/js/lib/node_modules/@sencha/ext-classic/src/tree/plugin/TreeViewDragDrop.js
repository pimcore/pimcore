/**
 * This plugin provides drag and drop functionality for a {@link Ext.tree.View TreeView}.
 * 
 * A specialized instance of {@link Ext.dd.DragZone DragZone} and {@link Ext.dd.DropZone 
 * DropZone} are attached to the tree view.  The DropZone will participate in drops 
 * from DragZones having the same {@link #ddGroup} including drops from within the same 
 * tree.
 * 
 * During the drop operation a data object is passed to a participating DropZone's drop 
 * handlers.  The drag data object has the following properties:
 *
 * - **copy:** {@link Boolean} <br> The value of {@link #copy}.  Or `true` if {@link #allowCopy}
 * is true **and** the control key was pressed as the drag operation began.
 * 
 * - **view:** {@link Ext.tree.View TreeView} <br> The source tree view from which the 
 * drag originated
 * 
 * - **ddel:** HTMLElement <br> The drag proxy element which moves with the cursor
 * 
 * - **item:** HTMLElement <br> The tree view node upon which the mousedown event was 
 * registered
 * 
 * - **records:** {@link Array} <br> An Array of {@link Ext.data.Model Model}s 
 * representing the selected data being dragged from the source tree view.
 *
 * By adding this plugin to a view, two new events will be fired from the client 
 * tree view as well as its owning Tree: `{@link #beforedrop}` and `{@link #drop}`.
 *
 *     var store = Ext.create('Ext.data.TreeStore', {
 *         root: {
 *             expanded: true,
 *             children: [{
 *                 text: "detention",
 *                 leaf: true
 *             }, {
 *                 text: "homework",
 *                 expanded: true,
 *                 children: [{
 *                     text: "book report",
 *                     leaf: true
 *                 }, {
 *                     text: "algebra",
 *                     leaf: true
 *                 }]
 *             }, {
 *                 text: "buy lottery tickets",
 *                 leaf: true
 *             }]
 *         }
 *     });
 *     
 *     Ext.create('Ext.tree.Panel', {
 *         title: 'Simple Tree',
 *         width: 200,
 *         height: 200,
 *         store: store,
 *         rootVisible: false,
 *         renderTo: document.body,
 *         viewConfig: {
 *             plugins: {
 *                 treeviewdragdrop: {
 *                     dragText: 'Drag and drop to reorganize'
 *                 }
 *             }
 *         }
 *     });
 */
Ext.define('Ext.tree.plugin.TreeViewDragDrop', {
    extend: 'Ext.plugin.Abstract',
    alias: 'plugin.treeviewdragdrop',

    uses: [
        'Ext.tree.ViewDragZone',
        'Ext.tree.ViewDropZone'
    ],

    /**
     * @event beforedrop
     * **This event is fired through the {@link Ext.tree.View TreeView} and its owning 
     * {@link Ext.tree.Panel Tree}. You can add listeners to the tree or tree {@link 
     * Ext.tree.Panel#viewConfig view config} object**
     *
     * Fired when a drop gesture has been triggered by a mouseup event in a valid drop 
     * position in the tree view.
     * 
     * Returning `false` to this event signals that the drop gesture was invalid and 
     * animates the drag proxy back to the point from which the drag began.
     * 
     * The dropHandlers parameter can be used to defer the processing of this event. For 
     * example, you can force the handler to wait for the result of a message box 
     * confirmation or an asynchronous server call (_see the details of the dropHandlers 
     * property for more information_).
     *  
     *     tree.on('beforedrop', function(node, data, overModel, dropPosition, dropHandlers) {
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
     * @param {HTMLElement} node The {@link Ext.tree.View tree view} node **if any** over 
     * which the cursor was positioned.
     *
     * @param {Object} data The data object gathered at mousedown time by the 
     * cooperating {@link Ext.dd.DragZone DragZone}'s {@link Ext.dd.DragZone#getDragData 
     * getDragData} method.  It contains the following properties:
     * @param {Boolean} data.copy The value of {@link #copy}.  Or `true` if 
     * {@link #allowCopy} is true **and** the control key was pressed as the drag 
     * operation began.
     * @param {Ext.tree.View} data.view The source tree view from which the drag 
     * originated
     * @param {HTMLElement} data.ddel The drag proxy element which moves with the cursor
     * @param {HTMLElement} data.item The tree view node upon which the mousedown event 
     * was registered
     * @param {Ext.data.TreeModel[]} data.records An Array of Models representing the 
     * selected data being dragged from the source tree view
     *
     * @param {Ext.data.TreeModel} overModel The Model over which the drop gesture took place
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
     * **This event is fired through the {@link Ext.tree.View TreeView} and its owning 
     * {@link Ext.tree.Panel Tree}. You can add listeners to the tree or tree {@link 
     * Ext.tree.Panel#viewConfig view config} object**
     * 
     * Fired when a drop operation has been completed and the data has been moved or 
     * copied.
     *
     * @param {HTMLElement} node The {@link Ext.tree.View tree view} node **if any** over 
     * which the cursor was positioned.
     *
     * @param {Object} data The data object gathered at mousedown time by the 
     * cooperating {@link Ext.dd.DragZone DragZone}'s {@link Ext.dd.DragZone#getDragData 
     * getDragData} method.  It contains the following properties:
     * @param {Boolean} data.copy The value of {@link #copy}.  Or `true` if 
     * {@link #allowCopy} is true **and** the control key was pressed as the drag 
     * operation began.
     * @param {Ext.tree.View} data.view The source tree view from which the drag 
     * originated
     * @param {HTMLElement} data.ddel The drag proxy element which moves with the cursor
     * @param {HTMLElement} data.item The tree view node upon which the mousedown event 
     * was registered
     * @param {Ext.data.TreeModel[]} data.records An Array of Models representing the 
     * selected data being dragged from the source tree view
     *
     * @param {Ext.data.TreeModel} overModel The Model over which the drop gesture took 
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
     * 
     * **NOTE:** The node's {@link Ext.tree.Panel#cfg-displayField text} will be shown 
     * when a single node is dragged unless `dragText` is a simple text string.
     * @locale
     */
    dragText: '{0} selected node{1}',

    /**
     * @cfg {Boolean} allowParentInserts
     * Allow inserting a dragged node between an expanded parent node and its first child
     * that will become a sibling of the parent when dropped.
     */
    allowParentInserts: false,

    /**
     * @cfg {Boolean} allowContainerDrops
     * True if drops on the tree container (outside of a specific tree node) are allowed.
     */
    allowContainerDrops: false,

    /**
     * @cfg {Boolean} appendOnly
     * True if the tree should only allow append drops (use for trees which are sorted).
     */
    appendOnly: false,

    /**
     * @cfg {String} [ddGroup=TreeDD]
     * A named drag drop group to which this object belongs. If a group is specified, then both
     * the DragZones and DropZone used by this plugin will only interact with other drag drop
     * objects in the same group.
     */
    ddGroup: "TreeDD",

    /**
     * True to register this container with the Scrollmanager for auto scrolling during
     * drag operations. A {@link Ext.dd.ScrollManager} configuration may also be passed.
     * @cfg {Object/Boolean} containerScroll
     */
    containerScroll: false,

    /**
     * @cfg {String} [dragGroup]
     * The ddGroup to which the {@link #property-dragZone DragZone} will belong.
     *
     * This defines which other DropZones the DragZone will interact with. Drag/DropZones
     * only interact with other Drag/DropZones which are members of the same ddGroup.
     */

    /**
     * @cfg {String} [dropGroup]
     * The ddGroup to which the {@link #property-dropZone DropZone} will belong.
     *
     * This defines which other DragZones the DropZone will interact with. Drag/DropZones
     * only interact with other Drag/DropZones which are members of the same {@link #ddGroup}.
     */

    /**
     * @cfg {Boolean} [sortOnDrop=false]
     * Configure as `true` to sort the target node into the current tree sort order after
     * the dropped node is added.
     */

    /**
     * @cfg {Number} [expandDelay=1000]
     * The delay in milliseconds to wait before expanding a target tree node while dragging
     * a droppable node over the target.
     */
    expandDelay: 1000,

    /**
     * @cfg {Boolean} enableDrop
     * Set to `false` to disallow the View from accepting drop gestures.
     */
    enableDrop: true,

    /**
     * @cfg {Boolean} enableDrag
     * Set to `false` to disallow dragging items from the View.
     */
    enableDrag: true,

    /**
     * @cfg {String} [nodeHighlightColor=c3daf9]
     * The color to use when visually highlighting the dragged or dropped node (default value
     * is light blue). The color must be a 6 digit hex value, without a preceding '#'.
     * See also {@link #nodeHighlightOnDrop} and
     * {@link #nodeHighlightOnRepair}.
     */
    nodeHighlightColor: 'c3daf9',

    /**
     * @cfg {Boolean} nodeHighlightOnDrop
     * Whether or not to highlight any nodes after they are
     * successfully dropped on their target. Defaults to the value of `Ext.enableFx`.
     * See also {@link #nodeHighlightColor} and {@link #nodeHighlightOnRepair}.
     */
    nodeHighlightOnDrop: Ext.enableFx,

    /**
     * @cfg {Boolean} nodeHighlightOnRepair
     * Whether or not to highlight any nodes after they are
     * repaired from an unsuccessful drag/drop. Defaults to the value of `Ext.enableFx`.
     * See also {@link #nodeHighlightColor} and {@link #nodeHighlightOnDrop}.
     */

    /**
     * @cfg {String} [displayField=text]
     * The name of the model field that is used to display the text for the nodes
     */
    displayField: 'text',

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

        view.on('render', this.onViewRender, this, {
            single: true
        });
    },

    destroy: function() {
        var me = this;

        me.dragZone = me.dropZone = Ext.destroy(me.dragZone, me.dropZone);
        me.callParent();
    },

    onViewRender: function(view) {
        var me = this,
            ownerGrid = view.ownerCt.ownerGrid || view.ownerCt,
            scrollEl;

        ownerGrid.relayEvents(view, ['beforedrop', 'drop']);

        if (me.enableDrag) {
            if (me.containerScroll) {
                scrollEl = view.getEl();
            }

            me.dragZone = new Ext.tree.ViewDragZone(Ext.apply({
                view: view,
                ddGroup: me.dragGroup || me.ddGroup,
                dragText: me.dragText,
                displayField: me.displayField,
                repairHighlightColor: me.nodeHighlightColor,
                repairHighlight: me.nodeHighlightOnRepair,
                scrollEl: scrollEl
            }, me.dragZone));
        }

        if (me.enableDrop) {
            me.dropZone = new Ext.tree.ViewDropZone(Ext.apply({
                view: view,
                ddGroup: me.dropGroup || me.ddGroup,
                allowContainerDrops: me.allowContainerDrops,
                appendOnly: me.appendOnly,
                allowParentInserts: me.allowParentInserts,
                expandDelay: me.expandDelay,
                dropHighlightColor: me.nodeHighlightColor,
                dropHighlight: me.nodeHighlightOnDrop,
                sortOnDrop: me.sortOnDrop,
                containerScroll: me.containerScroll
            }, me.dropZone));
        }
    }
}, function() {
    var proto = this.prototype;

    proto.nodeHighlightOnDrop = proto.nodeHighlightOnRepair = Ext.enableFx;
});
