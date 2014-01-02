

<?php $this->template("/includes/content-headline.php"); ?>


<?= $this->areablock("content"); ?>

<?php if($this->editmode) { ?>

    <style type="text/css">
        .alert {
            margin-top: 60px;
        }
    </style>

    <div class="editable-roundup">

        <div class="alert alert-info">
            <h3>Checkbox</h3>
        </div>
        <?= $this->checkbox("myCheckbox") ?>

        <div class="clearfix"></div>

        <div class="alert alert-info">
            <h3>Date</h3>
        </div>
        <?= $this->date("myDate"); ?>

        <div class="alert alert-info">
            <h3>Single Relation</h3>
        </div>
        <?= $this->href("myHref"); ?>

        <div class="alert alert-info">
            <h3>Image</h3>
        </div>
        <?= $this->image("myImage"); ?>

        <div class="alert alert-info">
            <h3>Input</h3>
        </div>
        <?= $this->input("myInput"); ?>

        <div class="alert alert-info">
            <h3>Link</h3>
        </div>
        <?= $this->link("myLink"); ?>

        <div class="alert alert-info">
            <h3>Multiple Relations</h3>
        </div>
        <?= $this->multihref("myMultiHref"); ?>

        <div class="alert alert-info">
            <h3>Multi-Select</h3>
        </div>
        <?= $this->multiselect("myMultiselect", array(
            "width" => 200,
            "height" => 100,
            "store" => array(
                array("value1", "Text 1"),
                array("value2", "Text 2"),
                array("value3", "Text 3"),
                array("value4", "Text 4"),
            )
        )) ?>

        <div class="alert alert-info">
            <h3>Numeric</h3>
        </div>
        <?= $this->numeric("myNumeric"); ?>

        <div class="alert alert-info">
            <h3>Renderlet (drop an asset folder)</h3>
        </div>
        <?= $this->renderlet("myRenderlet", array(
            "controller" => "content",
            "action" => "gallery-renderlet"
        )); ?>

        <div class="alert alert-info">
            <h3>Select</h3>
        </div>
        <?= $this->select("mySelect",array(
            "store" => array(
                array("option1", "Option One"),
                array("option2", "Option Two"),
                array("option3", "Option Three")
            )
        )); ?>

        <div class="alert alert-info">
            <h3>Snippet</h3>
            <p>drop a document snippet here</p>
        </div>
        <?= $this->snippet("mySnippet") ?>

        <div class="alert alert-info">
            <h3>Table</h3>
            <p>of course you can create tables in the wysiwyg too</p>
        </div>
        <?= $this->table("tableName",array(
           "width" => 700,
           "height" => 400,
           "defaults" => array(
               "cols" => 6,
               "rows" => 10,
               "data" => array(
                   array("Value 1", "Value 2", "Value 3"),
                   array("this", "is", "test")
               )
           )
       )) ?>

        <div class="alert alert-info">
            <h3>Textarea</h3>
        </div>
       <?= $this->textarea("myTextarea") ?>

        <div class="alert alert-info">
            <h3>Video</h3>
        </div>
       <?= $this->video("myVideo", array(
            "html5" => true,
            "thumbnail" => "content",
            "height" => 380
       )); ?>

        <div class="alert alert-info">
            <h3>WYSIWYG</h3>
        </div>
        <?= $this->wysiwyg("myWysiwyg"); ?>
    </div>
<?php } ?>