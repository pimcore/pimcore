<?php
    /**
     * @var \Pimcore\Model\Element\AbstractElement $element
     */
    $element = $this->element;
    $this->get("translate")->setDomain("admin");
?>
<div class="data-table <?= $this->cls ?>">
    <table>
        <?php if($element instanceof \Pimcore\Model\DataObject\Concrete) { ?>
            <tr>
                <th><?= $this->translate('class') ?></th>
                <td><?= $element->getClassName() ?> [<?= $element->getClassId() ?>]</td>
            </tr>
        <?php } ?>

        <?php if($element instanceof \Pimcore\Model\Asset) { ?>
            <tr>
                <th><?= $this->translate('mimetype') ?></th>
                <td><?= $element->getMimetype() ?></td>
            </tr>
        <?php } ?>

        <?php if($element->getProperty('language')) { ?>
            <tr>
                <th><?= $this->translate('language') ?></th>
                <td style="padding-left: 40px; background: url(<?= \Pimcore\Tool::getLanguageFlagFile($element->getProperty('language'), false); ?>) left top no-repeat; background-size: 31px 21px;">
                    <?php
                    $locales = \Pimcore\Tool::getSupportedLocales();
                    ?>
                    <?= $locales[$element->getProperty('language')] ?>
                </td>
            </tr>
        <?php } ?>

        <?php if($element instanceof \Pimcore\Model\Document\Page) { ?>
            <?php if($element->getTitle()) { ?>
                <tr>
                    <th><?= $this->translate('title') ?></th>
                    <td><?= $element->getTitle() ?></td>
                </tr>
            <?php } ?>

            <?php if($element->getDescription()) { ?>
                <tr>
                    <th><?= $this->translate('description') ?></th>
                    <td><?= $element->getDescription() ?></td>
                </tr>
            <?php } ?>

            <?php if($element->getProperty('navigation_name')) { ?>
                <tr>
                    <th><?= $this->translate('name') ?></th>
                    <td><?= $element->getProperty('navigation_name') ?></td>
                </tr>
            <?php } ?>
        <?php } ?>


        <?php
        $owner = \Pimcore\Model\User::getById($element->getUserOwner());
        ?>
        <?php if($owner) { ?>
            <tr>
                <th><?= $this->translate('userowner') ?></th>
                <td><?= $owner->getName() ?></td>
            </tr>
        <?php } ?>
        <?php
        $editor = \Pimcore\Model\User::getById($element->getUserModification());
        ?>
        <?php if($editor) { ?>
            <tr>
                <th><?= $this->translate('usermodification') ?></th>
                <td><?= $editor->getName() ?></td>
            </tr>
        <?php } ?>

        <tr>
            <th><?= $this->translate('creationdate') ?></th>
            <td><?= date('Y-m-d H:i', $element->getCreationDate()) ?></td>
        </tr>
        <tr>
            <th><?= $this->translate('modificationdate') ?></th>
            <td><?= date('Y-m-d H:i', $element->getModificationDate()) ?></td>
        </tr>
    </table>
</div>
