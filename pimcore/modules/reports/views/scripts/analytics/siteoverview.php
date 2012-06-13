<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    
    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/reports.css" />
    
    <script type="text/javascript" src="/pimcore/static/js/lib/prototype.js"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load('visualization', '1', {packages: ['corechart']});
    </script>
    
    
    <script type="text/javascript">
        
        var data = <?php echo Zend_Json::encode($this->data); ?>;
        var dailyData = <?php echo Zend_Json::encode($this->dailyData); ?>;

        
        function getScreenWidth () {
            var screenWidth = $$("body")[0].getWidth()-60;
            return screenWidth;
        }
        
        function drawMainChart () {
            
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'x');
            data.addColumn('number', '<?php echo $this->translate("pageviews"); ?>');
            data.addColumn('number', '<?php echo $this->translate("unique_pageviews"); ?>')
            data.addColumn('number', '<?php echo $this->translate("visits"); ?>');
            <?php foreach ($this->dailyData as $d) { ?>
                data.addRow(["<?php echo $d["date"] ?>", <?php echo $d["pageviews"] ?>, <?php echo $d["uniquepageviews"] ?>, <?php echo $d["visits"] ?>]);
            <?php } ?>
            
            // Create and draw the visualization.
            new google.visualization.AreaChart(document.getElementById('mainChartContainer')).
                draw(data, {curveType: "function",
                    width: getScreenWidth(), 
                    height: 400,
                    vAxis: {maxValue: 10},
                    vAxis: {
                        title: "<?php echo $this->translate("pageviews"); ?> / <?php echo $this->translate("visits"); ?>"
                    },
                    colors: ["#678612","#AA0912","#315678"]
                });
        }
        
        function drawVisualization() {
            drawMainChart();
            //drawGauges();
        }
        
    </script>
    
    <script type="text/javascript">
      google.setOnLoadCallback(drawVisualization);
    </script>
</head>


<body>
    
    <div id="report">
        <div class="fullsize">
            <div id="mainChartContainer"></div>
        </div>
        <hr />
        <div class="fullsize">
            <div class="columns2">
                <div class="smallchart">
                    <div class="chart">
                        <img src="<?php echo Pimcore_Helper_ImageChart::lineSmall($this->dailyDataGrouped["visits"]); ?>" />
                    </div>
                    <div class="value"><?php echo $this->data["visits"] ?></div>
                    <div class="label"><?php echo $this->translate("visits"); ?></div>
                </div> 
                <div class="smallchart">
                    <div class="chart">
                        <img src="<?php echo Pimcore_Helper_ImageChart::lineSmall($this->dailyDataGrouped["pageviews"]); ?>" />
                    </div>
                    <div class="value"><?php echo $this->data["pageviews"] ?></div>
                    <div class="label"><?php echo $this->translate("pageviews"); ?></div>
                </div> 
                <div class="smallchart">
                    <div class="chart">
                        <img src="<?php echo Pimcore_Helper_ImageChart::lineSmall($this->dailyDataGrouped["uniquepageviews"]); ?>" />
                    </div>
                    <div class="value"><?php echo $this->data["uniquepageviews"] ?></div>
                    <div class="label"><?php echo $this->translate("unique_pageviews"); ?></div>
                </div> 
                
            </div>
            <div class="columns2">
                <div class="smallchart">
                    <div class="chart">
                        <img src="<?php echo Pimcore_Helper_ImageChart::lineSmall($this->dailyDataGrouped["pagespervisit"]); ?>" />
                    </div>
                    <div class="value"><?php echo round($this->data["pagespervisit"],2) ?></div>
                    <div class="label"><?php echo $this->translate("pagespervisit"); ?></div>
                </div>
                <div class="smallchart">
                    <div class="chart">
                        <img src="<?php echo Pimcore_Helper_ImageChart::lineSmall($this->dailyDataGrouped["timeonsite"]); ?>" />
                    </div>
                    <div class="value"><?php echo $this->data["timeonsite"]; ?></div>
                    <div class="label"><?php echo $this->translate("average_timeonsite"); ?></div>
                </div>
                <div class="smallchart">
                    <div class="chart">
                        <img src="<?php echo Pimcore_Helper_ImageChart::lineSmall($this->dailyDataGrouped["bouncerate"]); ?>" />
                    </div>
                    <div class="value"><?php echo round($this->data["bouncerate"],2) ?></div>
                    <div class="label"><?php echo $this->translate("bouncerate"); ?></div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>