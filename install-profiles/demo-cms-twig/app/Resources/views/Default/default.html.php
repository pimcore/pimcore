<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Example</title>
</head>

<body>

<style type="text/css">
    body {
        padding:0;
        margin: 0;
        font-family: "Lucida Sans Unicode", Arial;
        font-size: 14px;
    }

    #site {
        margin: 0 auto;
        width: 600px;
        padding: 30px 0 0 0;
        color:#65615E;
    }

    h1, h2, h3 {
        font-size: 18px;
        padding: 0 0 5px 0;
        border-bottom: 1px solid #001428;
        margin-bottom: 5px;
    }

    h3 {
        font-size: 14px;
        padding: 15px 0 5px 0;
        margin-bottom: 5px;
        border-color: #cccccc;
    }

    img {
        border: 0;
    }

    p {
        padding: 0 0 5px 0;
    }

    a {
        color: #000;
    }

    #logo {
        text-align: center;
        padding: 50px 0;
    }

    #logo hr {
        display: block;
        height: 1px;
        overflow: hidden;
        background: #BBB;
        border: 0;
        padding:0;
        margin:30px 0 20px 0;
    }

    .claim {
        text-transform: uppercase;
        color:#BBB;
    }

    #site ul {
        padding: 10px 0 10px 20px;
        list-style: circle;
    }

</style>


<div id="site">
    <div id="logo">
        <a href="http://www.pimcore.org/"><img src="/pimcore/static6/img/logo-gray.svg" style="width: 200px;" /></a>
        <hr />
        <div class="claim">
            THE OPEN-SOURCE ENTERPRISE PLATFORM FOR PIM, CMS, DAM & COMMERCE
        </div>
    </div>
</div>

</body>
</html>
