Controller = function()
{
	function createGrid()
	{
    // create the data store
    var record = Ext.data.Record.create([
   		{name: 'company'},
     	{name: 'price', type: 'float'},
     	{name: 'change', type: 'float'},
     	{name: 'pct_change', type: 'float'},
     	{name: 'last_change', type: 'date', dateFormat: 'n/j h:ia'},
     	{name: '_id', type: 'int'},
     	{name: '_level', type: 'int'},
     	{name: '_lft', type: 'int'},
     	{name: '_rgt', type: 'int'},
     	{name: '_is_leaf', type: 'bool'}
   	]);
    var store = new Ext.ux.maximgb.tg.NestedSetStore({
    	autoLoad : true,
    	url: 'pager.php',
			reader: new Ext.data.JsonReader(
				{
					id: '_id',
					root: 'data',
					totalProperty: 'total',
					successProperty: 'success'
				}, 
				record
			)
    });
    // create the Grid
    var grid = new Ext.ux.maximgb.tg.GridPanel({
      store: store,
      master_column_id : 'company',
      columns: [
				{id:'company',header: "Company", width: 160, sortable: true, dataIndex: 'company'},
        {header: "Price", width: 75, sortable: true, renderer: 'usMoney', dataIndex: 'price'},
        {header: "Change", width: 75, sortable: true, renderer: change, dataIndex: 'change'},
        {header: "% Change", width: 75, sortable: true, renderer: pctChange, dataIndex: 'pct_change'},
        {header: "Last Updated", width: 85, sortable: true, renderer: Ext.util.Format.dateRenderer('m/d/Y'), dataIndex: 'last_change'}
      ],
      stripeRows: true,
      autoExpandColumn: 'company',
      title: 'Nested set server grid.',
      bbar: new Ext.ux.maximgb.tg.PagingToolbar({
      	store: store,
      	displayInfo: true,
      	pageSize: 10
      })
    });
    var vp = new Ext.Viewport({
    	layout : 'fit',
    	items : grid
    });
    grid.getSelectionModel().selectFirstRow();
	}
	
	// example of custom renderer function
  function change(val)
  {
    if (val > 0) {
      val = '<span style="color:green;">' + val + '</span>';
    } 
    else if(val < 0) {
			val = '<span style="color:red;">' + val + '</span>';
    }
    return val;
  }

  // example of custom renderer function
  function pctChange(val)
  {
    if (val > 0) {
      val = '<span style="color:green;">' + val + '%</span>';
    } 
    else if(val < 0) {
      val = '<span style="color:red;">' + val + '%</span>';
    }
    return val;
  }

	return {
		init : function()
		{
			createGrid();
		}
	}
}();

Ext.onReady(Controller.init);