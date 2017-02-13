<?php

use Symfony\Component\HttpKernel\Controller\ControllerReference;

if($this->editmode) {
        // add some wrapping HTML to make it looking nicer in the editmode
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="/website/static/bootstrap/css/bootstrap.css" rel="stylesheet">
        <link href="/website/static/css/global.css" rel="stylesheet">
        <link href="/website/static/css/editmode.css?_dc=<?= time(); ?>" rel="stylesheet">
    </head>

    <body>
        <div style="max-width: 300px;">
            <div class="sidebar">
<?php } ?>




<div class="teasers">
    <?php while($this->block("teasers")->loop()) { ?>
        <?= $this->snippet("teaser"); ?>
    <?php } ?>
</div>

<?php if($this->editmode) { ?>
    <br />
    <hr />
    <div class="alert alert-info" style="margin-top: 30px">
        <h3>How many blog articles should be listed (set 0 to hide the box):</h3>
        <br />
        <?= $this->select("blogArticles", [
            "width" => 70,
            "store" => [[1,1],[2,2],[3,3]]
        ]); ?>
    </div>
<?php } else {
        $count = $this->select("blogArticles")->getData();
        if($count) {
            echo $this['actions']->render(
                new ControllerReference('WebsiteDemoBundle:Blog:sidebarBox', [
                    'items' => (int) $count
                ])
            );
        }
    }
?>





<?php if($this->editmode) { ?>
            </div>
        </div>

    </body>
    </html>
<?php } ?>
