<?php
$article = $this->object;
/* @var $article Website_Object_Artikel */
?>
<link href="/static/css/print-catalog/global.css" rel="stylesheet" type="text/css" />
<link href="/static/css/print-catalog/aktionsangebot.css" rel="stylesheet" type="text/css" />

<style type="text/css">
    .page.cat_1 {
        background:none;
    }
    .rightPage .product .textCol table {
        font-size: 11px;
    }
</style>


<div class="page cat_1 rightPage">
    <?php
    $arrTips = array();
    if($article->getExtOekotipp() == 'Ja')
    {
        $tip = Object_Artikelauszeichnung::getById(64795);
        $url = $tip->getImage()->getThumbnail(array('height' => 45));
        $arrTips[] = '<img src="' . $url . '" alt="Ökotipp" class="display_type_oekotipp" height="45">';
    }
    if($article->getExtNachfuellbar() == 'Ja')
    {
        $tip = Object_Artikelauszeichnung::getById(64796);
        $url = $tip->getImage()->getThumbnail(array('height' => 45));
        $arrTips[] = '<img src="' . $url . '" alt="Nachfüllbar" class="display_type_nachfuellbar" height="45">';
    }
    ?>
    <div class="product small <?= count($arrTips) > 0 ? 'hasDisplayTypes greenbox' : '' ?>"><!-- greenbox | hasDisplayTypes -->
        <div class="textCol">
            <div class="productName"><?= $article->getBez() ?>, <?= $article->getManufacturerName() ?></div>
            <div class="description colorRed">
                <div class="text">
                    <div class="imageWrapper">
                        <?php if($picture = $article->getExtEinzelabbildung()): ?>
                            <img src="<?= $picture->getThumbnail(array('width' => 150, 'height' => 160)) ?>" />
                        <?php endif; ?>
                        <?php if(count($arrTips) > 0): ?>
                        <div class="displayTypes">
                            <?php
                            foreach($arrTips as $tip) {
                                echo $tip;
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    $text = $article->getExtTextKatalog();
                    if(!$text)
                    {
                        $text = $article->getExtTextOnline();
                    }
                    ?>
                    <?= $text ?>
                </div>
            </div>

            <div class="logos">
                <?php
                $price = number_format($article->getVkprs(), 2, ',', '.');
                list($a, $b) = explode(',', $price);
                ?>
                <span class="price"><span class="first"><?= $a ?></span>,<sup><?= $b ?></sup></span>
                <?php
                foreach($article->getLogos() as $logo)
                {
                    echo '<img src="' . $logo->getThumbnail('print_catalog_logo') . '" />';
                }
                ?>
            </div>

            <div class="productData">
                <?php
                $configArray = Elements\OutputDataConfigToolkit\Service::getOutputDataConfig($article, "print_catalog");
                $fields = array();
                foreach($configArray as $x) {
                    if(($x->getLabel()) != '') {
                        $fields[] = $x;
                    }
                }
                ?>
                <table class="basetable">
                    <thead>
                        <tr>
                            <?php foreach($fields as $field): ?>
                                <th><?= $field->getLabel() ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if($article->getX_articleType() == 'articlegroup')
                    {
                        $arrArticles = $article->getChilds();
                    }
                    else if($article->getParent()->getX_articleType() == 'articlegroup')
                    {
                        $arrArticles = $article->getParent()->getChilds();
                    }
                    else
                    {
                        $arrArticles = array($article);
                    }
                    ?>
                    <?php foreach($arrArticles as $item): /* @var Object_Artikel $item */ ?>
                        <tr class="even">
                            <?php foreach($fields as $field): ?>
                                <td>
                                    <?php
                                    $get = 'get' . $field->getAttribute();
                                    if(method_exists($item, $get))
                                    {
                                        echo $item->$get();
                                    }
                                    else if($field->getAttribute() == 'price')
                                    {
                                        echo number_format($item->getVkprs(), 2, ',', '.');
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
