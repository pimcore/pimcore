<?php if($this->products) { ?>
    
    <div class="headline">
        <h4>Recently viewed products</h4>
    </div>

    <div class="teasers">
        <?php foreach ($this->products as $product) { ?>
            <?= $this->partial("/_shared/productCell.php", array("product" => $product, "config" => $this->config, "document" => $this->document, "cellClass" => "leftteaser")); ?>
        <?php } ?>
    </div>

<?php } ?>