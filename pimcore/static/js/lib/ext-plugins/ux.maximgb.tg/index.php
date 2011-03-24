<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html 
    xmlns="http://www.w3.org/1999/xhtml" 
    xml:lang="en" 
    lang="en">
<head>
 <title>Ext.ux.maximgb.tg - the treegrid component for ExtJS 3.x, demo page.</title>
 <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />  
 <link rel="stylesheet" type="text/css" href="index.css" />
</head>
<body id="help-panel">
 <?php include "../donate.html"; ?>
 <h1>Ext.ux.maximgb.tg - the tree grid component for ExtJS 3.x, demo page.</h1>
 <h2>Examples:</h2>
 <ul>
  <li>
   <a href="examples/client_expander/index.html" title="Client side tree grid with level coloring example.">
    Client side tree grid with level coloring example.
   </a>
  </li>
  <li>
   <a href="examples/server_ns/index.html" title="Server paging nested set tree grid example.">
    Server paging nested set tree grid example.
   </a>
  </li>
  <li>
   <a href="examples/server_al/index.html" title="Server paging adjacency list tree grid example.">
    Server paging adjacency list tree grid example.
   </a>
  </li>
  <li>
   <a href="examples/client_editable/index.html" title="Client side edtiable tree grid.">
    Client side editable tree grid example.
   </a>
  </li>
  <li>
    <strong>Paging feature explanation:</strong><br/>
    If the tree grid has no selection then paging toolbar shows information related to grid's root nodes set, 
    e.g. if it shows "Displaying 1 - 10 of 20" then there are 20 root nodes (nodes with root id === null) which are
    divided into two pages. If the tree grid has a selection then the paging toolbar shows information related to 
    selected node's child node set, node must be expanded for node's paging related information is loaded. 
    After node expanding paging toolbar shows selected node's child node set related information and the paging
    facility are applyed to that node's child node set only. To start paging at the root nodes level one must 
    deselect all nodes.
  </li>
 </ul>
 
 <h2>Licence.</h2>    
  <a href="license.txt" title="Click to view the license">BSD license</a>
 </p>
    
 <h2>Author.</h2>
 <p>
  Maxim Bazhenov (aka MaximGB), <br/>
  if you have either a reasonable enchancement proposal
  or a job offer (I am a freelancer) or both fell free to 
  <a href="http://extjs.com/forum/member.php?u=6010" alt="Go to ExtJS forums">contact me</a> via ExtJS's forums
 </p>

 <h2>Download</h2>
 <p>
  <a href="ux.maximgb.tg.zip" title="Download ux.maximgb.tg extension for ExtJS 3.x">
   ux.maximgb.tg.zip
  </a>
 </p>

 <h2>Changes from ExtJS 2.0 version</h2>
 <ul>
  <li>No more breadcrumbs header, thought it might be added later as optional component.</li>
  <li>Ext.ux.maximgb.tg.EditorGridPanel class added, so TreeGrid gained the cell editing feature as well.</li>
  <li>Phing (http://phing.info) build file added to distribution.</li>
 </ul>
    
 <hr/>
 <h2>Change history</h2>
 <ul>
  <li>
   <b>01.08.2009</b><br/>
   - initial release.
  </li>
  <li>
   <b>01.10.2009</b><br/>
   - Fixed remote sorting related bug, when grid's store 'remoteSort' configuration option was set to 'true'
     store was sending active node parameter (anode) with a value from the previous request thus records read
     from the server response were added to store instead of replacing entire store contents.
  </li>
  <li>
   <b>14.10.2009</b><br/>
   - Removed CSS rule forcing grid scroller to show scrollbars always.
  </li>
  <li>
   <b>15.04.2010</b><br/>
   - Fixed small bug in GridView, column renderers now should support scope.
  </li>
 </ul>
 
</body>
</html>