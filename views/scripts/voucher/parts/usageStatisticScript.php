<? if (is_array($this->usage)) { ?>
    <script>
        /**
         * Line Chart for Usage Statistic
         *
         * @type {{labels: string[], datasets: {label: string, fillColor: string, strokeColor: string, pointColor: string, pointStrokeColor: string, pointHighlightFill: string, pointHighlightStroke: string, data: null[]}[]}}
         */
        var lineChartData = {
            labels: [
                <? foreach($this->usage as $date => $usage){ ?>
                "<?= $date ?>",
                <? } ?>
            ],
            datasets: [
                {
                    label: "Usage Statistic",
                    fillColor: "rgba(220,220,220,0.2)",
                    strokeColor: "rgba(220,220,220,1)",
                    pointColor: "rgba(220,220,220,1)",
                    pointStrokeColor: "#fff",
                    pointHighlightFill: "#fff",
                    pointHighlightStroke: "rgba(220,220,220,1)",
                    data: [
                        <? foreach($this->usage as $date => $usage){ ?>
                        <?= $usage ?>,
                        <? } ?>
                    ]
                }
            ]
        };

        var pieData = [
            {
                value: <?=$this->statistics['usageCount']?>,
                color: "<?=$this->colors['used']?>",
                highlight: "#FE6B4F",
                label: "Used"
            },
            {
                value: <?=$this->statistics['freeCount']?>,
                color: "<?=$this->colors['free']?>",
                highlight: "#5AD2D2",
                label: "Free"
            },
            {
                value: <?=$this->statistics['reservedCount']?>,
                color: "<?=$this->colors['reserved']?>",
                highlight: "#FEC770",
                label: "Reserved"
            }
        ];


        /**
         * Init Statistics canvas on tab click.
         */
        $('#statistic-tab').on('click', function () {
            window.setTimeout(function () {

                var usage = document.getElementById("canvas-usage").getContext("2d");

                var usageChart = new Chart(usage).Line(lineChartData, {
                    responsive: true,
                    showTooltips: false
                });

                var tokens = document.getElementById("canvas-token").getContext("2d");
                var tokenChart = new Chart(tokens).Pie(pieData, {
                    responsive: true
                });

                $('#statistic-tab').unbind('click');
            }, 50);

        });

    </script>
<? } ?>