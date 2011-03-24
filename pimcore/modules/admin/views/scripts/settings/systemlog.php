<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>


    <style type="text/css">

        body {
            font-family: Tahoma, Arial, Verdana;
            font-size: 11px;
        }

        .line {
            border-bottom: 1px solid #ccc;
            padding: 2px 0 2px 0;
        }

        .loglevel_0 {
            color: #e10000;
        }
        .loglevel_1 {
            color: #ff0000;
        }
        .loglevel_2 {
            color: #ff0000;
        }
        .loglevel_3 {
            color: #e15500;
        }
        .loglevel_4 {
            color: #d78900;
        }
        .loglevel_5 {
            color: #efa900;
        }
        .loglevel_6 {
            color: #393939;
        }
        .loglevel_7 {
            color: #009704;
        }

    </style>

</head>

<body>

<?php foreach ($this->lines as $line) { ?>
    <?php

        preg_match("/\(([0-9]+)\)\:/",$line,$matches);
        if($matches[1]) {
            $class = "loglevel_" . $matches[1];
        }
    ?>
    <div class="line <?php echo $class; ?>"><?php echo $line; ?></div>

<?php } ?>

<script type="text/javascript">

    var syslog = parent.pimcore.globalmanager.get("systemlog");
    syslog.frame = window;

    if (syslog.lastScrollposition) {
        if (syslog.lastScrollposition.top > 100) {
            window.scrollTo(syslog.lastScrollposition.left, syslog.lastScrollposition.top);
        }
    }

    syslog.isLoadedComplete();

</script>


</body>
</html>