<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 02.02.2015
 * Time: 10:36
 *
 * Siehe http://developer.yahoo.com/ypatterns/pattern.php?pattern=searchpagination
 */

$params = $this->params ?: [];

if ($this->pageCount): ?>
    <ul class="pagination">
        <?php if (isset($this->previous)): ?>
            <li>
                <a href="<?= $this->url($params + ['page' => $this->previous]); ?>">
                    <span class="glyphicon glyphicon-chevron-left"></span>
                </a>
            </li>
        <?php endif; ?>

        <?php foreach ($this->pagesInRange as $page): ?>
            <li class="<?= $page == $this->current ? 'active' : '' ?>">
                <a href="<?= $this->url($params + ['page' => $page]); ?>">
                    <?= $page; ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if (isset($this->next)): ?>
            <li>
                <a href="<?= $this->url($params + ['page' => $this->next]); ?>">
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
<?php endif; ?>
