<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>


<?php
// made by Twitter user @remisouverain
if ($this->pageCount): ?>
	<ul class="pagination">
	<!-- <li class="desc disabled"><a href="#"><?= $this->firstItemNumber; ?>-<?= $this->lastItemNumber; ?> / <?= $this->totalItemCount; ?></a></li> -->

	<?php if (isset($this->previous)): ?>
		<li class="first"><a href="<?= $this->pimcoreUrl(['page' => $this->first]); ?>"><?= $this->first; ?> &larr;</a></li>
	<?php endif; ?>



<?php foreach ($this->pagesInRange as $page)
{
	$class = '';
	if ($page == $current) $class = 'active';
	if( ($this->first < $page) && ($page < $this->last) || $page == $current)
	{
?>
		<li class="<?= $class; ?>"><a href="<?= $this->pimcoreUrl(['page' => $page]); ?>"><?= $page; ?></a></li>
<?php
	}
}
?>

	<?php if (isset($this->next)): ?>
		<li class="last"><a href="<?= $this->pimcoreUrl(['page' => $this->last]); ?>">&rarr; <?= $this->last; ?></a></li>
	<?php endif; ?>

	</ul>
<?php endif; ?>
