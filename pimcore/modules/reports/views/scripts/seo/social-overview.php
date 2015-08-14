<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style type="text/css">

        body {
            background: #000;
            color: #fff;
            font-family: "Lucida Grande", "Lucida Sans Unicode", Arial;
            padding: 0;
            margin: 0;
        }

        #container {
            padding:0 0 0 0;
            width: 90%;
            margin: 0 auto;
        }

        #container h1 {
            padding: 0 0 0 30px;
            margin: 30px 0 10px 0;
            font-size: 30px;
        }

        #container h1.facebook {
            background: url(/pimcore/static/img/icon/facebook-like.png) left center no-repeat;
        }

        #container h1.gplus {
            background: url(/pimcore/static/img/icon/google-plus-icon.png) left center no-repeat;
        }

        .statistics-list {
            margin: 0 0 20px 0;
            background: #323232;
            padding: 0 0 15px 0;
        }

        .statistics-list .text {
            padding: 10px;
        }

        .statistics-list .overview {
            float: left;
            width: 250px;
            padding: 15px 0 0 0;
        }

        .statistics-list .overview .amount {
            font-size: 50px;
            text-align: center;
        }

        .statistics-list .overview .trend .triangle {
            margin: 0 auto;
            width: 0;
            height: 0;
            border-left: 30px solid transparent;
            border-right: 30px solid transparent;
        }

        .statistics-list .overview .trend .up {
        	border-bottom: 30px solid #007700;
        }

        .statistics-list .overview .trend .down {
        	border-top: 30px solid #f00;
        }

        .statistics-list .overview .sparkline {
            padding: 15px 0 0 0;
            text-align: center;
        }

        .statistics-list .list {
            margin-left: 270px;
            padding: 10px 0 0 0;
        }

        .statistics-list .list h2 {
            font-size: 20px;
            margin: 0;
            padding: 0;
            text-align: right;
        }

        .statistics-list .list table {
            border-collapse: collapse;
            width: 98%;

        }

        .statistics-list .list td  {
            border-top: 1px solid #d7d8d7;
            font-size: 14px;
        }

        .statistics-list .list td, .statistics-list .list th {
            text-align: left;
            padding: 2px;
        }

        .clear {
            display:block;
            clear: both;
        }

    </style>

</head>

<body>


<div id="container">

    <?php
        $fbTrendUp = true;
        if(count($this->summary["timeline"]["facebook"]) > 2) {
            $today = $this->summary["timeline"]["facebook"][0]-$this->summary["timeline"]["facebook"][1];
            $yesterday = $this->summary["timeline"]["facebook"][1]-$this->summary["timeline"]["facebook"][2];
            if($today < $yesterday) {
                $fbTrendUp = false;
            }
        }
    ?>
    <h1 class="facebook">Facebook Shares</h1>
    <div class="statistics-list">
        <?php if(!empty($this->summary["timeline"]["facebook"])) { ?>
            <div class="overview">
                <?php if ($fbTrendUp) { ?>
                    <div class="trend">
                        <div class="triangle up"></div>
                    </div>
                <?php } ?>
                <div class="amount"><?php echo $this->summary["timeline"]["facebook"][0]; ?></div>
                <?php if (!$fbTrendUp) { ?>
                    <div class="trend">
                        <div class="triangle down"></div>
                    </div>
                <?php } ?>
                <div class="sparkline">
                    <img src="https://chart.googleapis.com/chart?chf=bg,s,323232&cht=lc&chs=180x40&chd=t:<?php echo implode(",",$this->summary["timeline"]["facebook"]); ?>&chds=<?php echo min($this->summary["timeline"]["facebook"]); ?>,<?php echo max($this->summary["timeline"]["facebook"]); ?>&" />
                </div>
            </div>
            <div class="list">
                <table>
                    <tr>
                        <th colspan="2"><?php echo $this->translate("top_shares_pages"); ?></th>
                    </tr>
                    <?php foreach ($this->summary["top"]["facebook"] as $top) { ?>
                        <tr>
                            <td valign="top" style="padding-right: 5px;"><?php echo $top["shares"]; ?></td>
                            <td><?php echo $top["url"]; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <div class="text">
                <?php echo $this->translate("sorry_no_data_available"); ?>
            </div>
        <?php } ?>
        <span class="clear"></span>
    </div>

    <?php
        $poTrendUp = true;
        if(count($this->summary["timeline"]["plusone"]) > 2) {
            $today = $this->summary["timeline"]["plusone"][0]-$this->summary["timeline"]["plusone"][1];
            $yesterday = $this->summary["timeline"]["plusone"][1]-$this->summary["timeline"]["plusone"][2];
            if($today < $yesterday) {
                $poTrendUp = false;
            }
        }
    ?>
    <h1 class="gplus">Google +1</h1>
    <div class="statistics-list">
        <?php if(!empty($this->summary["timeline"]["plusone"])) { ?>
            <div class="overview">
                <?php if ($poTrendUp) { ?>
                    <div class="trend">
                        <div class="triangle up"></div>
                    </div>
                <?php } ?>
                <div class="amount"><?php echo $this->summary["timeline"]["plusone"][0]; ?></div>
                <?php if (!$poTrendUp) { ?>
                    <div class="trend">
                        <div class="triangle down"></div>
                    </div>
                <?php } ?>
                <div class="sparkline">
                    <img src="https://chart.googleapis.com/chart?chf=bg,s,323232&cht=lc&chs=180x40&chd=t:<?php echo implode(",",$this->summary["timeline"]["plusone"]); ?>&chds=<?php echo min($this->summary["timeline"]["plusone"]); ?>,<?php echo max($this->summary["timeline"]["plusone"]); ?>&" />
                </div>
            </div>
            <div class="list">
                <table>
                    <tr>
                        <th colspan="2"><?php echo $this->translate("top_shares_pages"); ?></th>
                    </tr>
                    <?php foreach ($this->summary["top"]["plusone"] as $top) { ?>
                        <tr>
                            <td valign="top" style="padding-right: 5px;"><?php echo $top["shares"]; ?></td>
                            <td><?php echo $top["url"]; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <div class="text">
                <?php echo $this->translate("sorry_no_data_available"); ?>
            </div>
        <?php } ?>
        <span class="clear"></span>
    </div>

</div>


</body>
</html>