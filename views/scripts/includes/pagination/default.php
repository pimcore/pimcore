<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 02.02.2015
 * Time: 10:36
 *
 * Siehe http://developer.yahoo.com/ypatterns/pattern.php?pattern=searchpagination
 */

$request = Zend_Controller_Front::getInstance()->getRequest();
$params = $this->params ?: [];

if ($this->pageCount): ?>
    <div class="<?= $this->class ?>">
        <!-- Vorheriger Seitenlink -->
        <?php if (isset($this->previous)): ?>
            <a href="<?= $this->url($params + ['page' => $this->previous]); ?>">
                <span class="icon icon-arrowleft"></span>
                <span class="arrow-text"><?= $this->translate('previously') ?></span>
            </a>
        <?php else: ?>
        <?php endif; ?>

        <!-- Anzahl an Seitenlinks -->
        <?php foreach ($this->pagesInRange as $page): ?>
            <a class="number <?= $page == $this->current ? 'active' : '' ?>" href="<?= $this->url($params + ['page' => $page]); ?>">
                <?= $page; ?>
            </a>
        <?php endforeach; ?>

        <!-- Nächster Seitenlink -->
        <?php if (isset($this->next)): ?>
            <a href="<?= $this->url($params + ['page' => $this->next]); ?>">
                <span class="arrow-text"><?= $this->translate('next') ?></span>
                <span class="icon icon-arrowright"></span>
            </a>
        <?php else: ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
