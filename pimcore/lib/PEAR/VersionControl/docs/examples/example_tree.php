<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004, Clay Loveless                                    |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | This LICENSE is in the BSD license style.                            |
// | http://www.opensource.org/licenses/bsd-license.php                   |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// |  * Redistributions of source code must retain the above copyright    |
// |    notice, this list of conditions and the following disclaimer.     |
// |                                                                      |
// |  * Redistributions in binary form must reproduce the above           |
// |    copyright notice, this list of conditions and the following       |
// |    disclaimer in the documentation and/or other materials provided   |
// |    with the distribution.                                            |
// |                                                                      |
// |  * Neither the name of Clay Loveless nor the names of contributors   |
// |    may be used to endorse or promote products derived from this      |
// |    software without specific prior written permission.               |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
// | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
// | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
// | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Clay Loveless <clay@killersoft.com>                          |
// +----------------------------------------------------------------------+
//
// $Id$
//

/*
    This is a more complex example, that also illustrates the use of 
    HTML_TreeMenu.
    
    In this example, we'll get a recusive list of files in a repository.
    We will then loop through the files and build a dynamic HTML_TreeMenu
    object for easy navigation.
*/

error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'VersionControl/SVN.php';

// Default options
$base_url = 'https://github.com/pear/VersionControl_SVN/trunk';
$base_add = '';
if (isset($_SERVER['PATH_INFO'])) {
    $base_add = $_SERVER['PATH_INFO'];
}

$cmd = '';
$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : 'list';

$options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_ASSOC);
$switches = array('R' => true);
$args = array("{$base_url}{$base_add}");

try {
    // Create svn object with subcommands we'll want
    $svn = VersionControl_SVN::factory(array('list', 'cat'), $options);

    // A quickie sample of browsing a Subversion repository
    if ($base_add != '') {
        $source = $svn->cat->run($args);
        if (substr($base_add, -4) == '.php') {
            highlight_string($source);
        } else {
            echo '<pre>'.htmlentities($source, ENT_NOQUOTES)."</pre>\n";
        }

    } else {

        // TreeMenu setup
        require_once 'HTML/TreeMenu.php';
        // Change icons to appropriate names
        // See HTML_TreeMenu docs for more details.
        $foldericon = 'aquafolder.gif';
        $docicon = 'bbedit_doc.gif';
        $menu = new HTML_TreeMenu();
        $node1 = new HTML_TreeNode(array('text' => 'VersionControl_SVN',
                                        'icon' => $foldericon));

        $list = $svn->list->run($args, $switches);
        foreach ($list['list'][0]['entry'] as $item) {
            $dir = dirname($item['name']);
            if ($item['kind'] !== 'file') {
                $icon = $foldericon;
                $link = '';
            } else {
                $icon = $docicon;
                $link = $_SERVER['PHP_SELF']."/" . $item['name'];
                // don't need the link for the .
                $link = str_replace('/.', '', $link);
            }
            
            if ($dir == '.') {
                // Adding to root level
                $obj = basename($item['name']);
                $$obj = $node1->addItem(new HTML_TreeNode(array('text' => $item['name'], 'icon' => $icon, 'link' => $link)));
            } else {
                // Get parent item
                $parent = basename($dir);
                $obj = basename($item['name']);
                $$obj = $$parent->addItem(new HTML_TreeNode(array('text' => $item['name'], 'icon' => $icon, 'link' => $link)));
            }
        }

        $menu->addItem($node1);

        // Create presentation class
        $treeMenu = new HTML_TreeMenu_DHTML($menu, array('images' => 'images',
                                                        'defaultClass' => 'treeMenuDefault'));

        ?>
    <html>
    <head>
        <title>VersionControl_SVN Source Listing</title>
        <script language="javascript" type="text/javascript" src="TreeMenu.js"></script>
        <style type="text/css">
        body, td, th {
            font-family: verdana,arial,helvetica,sans-serif;
            font-size: 80%;
        }
        .squeeze { line-height: 96%; font-size: xx-small; font-family:Verdana,Geneva,Arial; color: #999999; }
        </style>
    </head>
    <body>
    <h3>VersionControl_SVN Source Listing</h3>
    <?php
    $treeMenu->printMenu();
    ?>

    <p>
    <span class="squeeze">
    Source listing driven by <a href="http://pear.php.net/package/HTML_TreeMenu">HTML_TreeMenu</a> and <a href="VersionControl_SVN_docs/index.html">VersionControl_SVN</a>
    </span>
    </p>
    </body>
    </html>
    <?php
    }
} catch (VersionControl_SVN_Exception $e) {
    echo "<pre>\n";
    print_r($e->getMessage());
    echo "</pre>\n";
}

?>
