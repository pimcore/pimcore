

<?php $this->template("/includes/content-headline.php"); ?>


<?php echo $this->areablock("content"); ?>

<?php if($this->editmode) { ?>
    <div class="editable-roundup">

        <h2>Checkbox</h2>
        <?php echo $this->checkbox("myCheckbox") ?>

        <div class="clearfix"></div>

        <h2>Date</h2>
        <?php echo $this->date("myDate"); ?>

        <h2>Single Relation</h2>
        <?php echo $this->href("myHref"); ?>

        <h2>Image</h2>
        <?php echo $this->image("myImage"); ?>

        <h2>Input</h2>
        <?php echo $this->input("myInput"); ?>

        <h2>Link</h2>
        <?php echo $this->link("myLink"); ?>

        <h2>Multiple Relations</h2>
        <?php echo $this->multihref("myMultiHref"); ?>

        <h2>Multi-Select</h2>
        <?php echo $this->multiselect("myMultiselect", array(
            "width" => 200,
            "height" => 100,
            "store" => array(
                array("value1", "Text 1"),
                array("value2", "Text 2"),
                array("value3", "Text 3"),
                array("value4", "Text 4"),
            )
        )) ?>

        <h2>Numeric</h2>
        <?php echo $this->numeric("myNumeric"); ?>

        <h2>Renderlet (drop an asset folder)</h2>
        <?php echo $this->renderlet("myRenderlet", array(
            "controller" => "content",
            "action" => "gallery-renderlet"
        )); ?>

        <h2>Select</h2>
        <?php echo $this->select("mySelect",array(
            "store" => array(
                array("option1", "Option One"),
                array("option2", "Option Two"),
                array("option3", "Option Three")
            )
        )); ?>

        <h2>Snippet (drop a document snippet here)</h2>
        <?php echo $this->snippet("mySnippet") ?>

        <h2>Table (of course you can create tables in the wysiwyg too)</h2>
        <?php echo $this->table("tableName",array(
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

       <h2>Textarea</h2>
       <?php echo $this->textarea("myTextarea") ?>

       <h2>Video</h2>
       <?php echo $this->video("myVideo", array(
            "html5" => true,
            "thumbnail" => "content",
            "height" => 380
       )); ?>

        <h2>WYSIWYG</h2>
        <?php echo $this->wysiwyg("myWysiwyg"); ?>
    </div>
<?php } ?>