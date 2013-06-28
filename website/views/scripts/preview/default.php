<?php
$article = $this->object;
/* @var $article Website_Object_Artikel */
?>
<style type="text/css">
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    padding: 0px;
    margin: 0px;
}
h1 {
    font-weight: normal;
    color: #007ac1;
    font-size: 1.8em;
}
a, a:visited {
    color: #007ac1;
}

.tabs {
    list-style: none;
    margin: 0px auto;
    padding: 0px;
    width: 610px;
}
.tabs .tab {
    margin: 0px;
    padding: 0px;
    width: 200px;
    display: inline-block;
    background: #DBE6EC;
}
.tabs .tab:first-child {
    -webkit-border-bottom-left-radius: 5px;
    -moz-border-radius-bottomleft: 5px;
    border-bottom-left-radius: 5px;
}
.tabs .tab:last-child {
    -webkit-border-bottom-right-radius: 5px;
    -moz-border-radius-bottomright: 5px;
    border-bottom-right-radius: 5px;
}
.tabs .tab.active {
    background: #C2CBCE;
}
.tabs .tab .link {
    padding: 5px 0px;
    width: 100%;
    display: inline-block;
    text-align: center;
    text-decoration: none;
}
.tabs .tab.active .link {
    font-weight: bold;
}

.preview {
    width: 740px;
    position: relative;
    margin: 40px auto;
    overflow: hidden;
    /*border: 1px dotted #ccc;*/
    /*padding: 10px;*/
}
</style>

<ul class="tabs">
    <li class="tab <?= $this->render == 'web' ? 'active': '' ?>"><a class="link" href="?render=web">Web</a></li>
    <li class="tab <?= $this->render == 'print' ? 'active': '' ?>"><a class="link" href="?render=print">Print</a></li>
    <li class="tab <?= $this->render == 'printyear' ? 'active': '' ?>"><a class="link" href="?render=printyear">Print Jahreskatalog</a></li>
</ul>

<div class="preview">
    <?= $this->render(sprintf('preview/includes/%s.php', $this->render)) ?>
</div>
