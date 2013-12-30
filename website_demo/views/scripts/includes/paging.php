<?php
// made by Twitter user @remisouverain
//my_pagination_control.phtml
if ($this->pageCount): ?>
	<ul class="pagination">
	<!-- <li class="desc disabled"><a href="#"><?= $this->firstItemNumber; ?>-<?= $this->lastItemNumber; ?> / <?= $this->totalItemCount; ?></a></li> -->
	
	<?php if (isset($this->previous)): ?>
		<li class="first"><a href="<?= $this->url(array('page' => $this->first)); ?>"><?= $this->first; ?> &larr;</a></li>
	<?php endif; ?>
	
	<?php /*if (isset($this->previous) && $this->previous!=$this->first): ?>
		<li class="disabled"><a href="#">...</a></li>
		<li class="previous"><a href="<?= $this->url(array('page' => $this->previous)); ?>"><?= $this->previous; ?></a></li>
	<?php endif; */?>
	
	
<?php foreach ($this->pagesInRange as $page)
{
	$class = '';
	if ($page == $this->current) $class = 'active';
	if( ($this->first < $page) && ($page < $this->last) || $page == $this->current)
	{
?>
		<li class="<?= $class; ?>"><a href="<?= $this->url(array('page' => $page)); ?>"><?= $page; ?></a></li>
<?php
	}
}
?>
	
	<?php /*if (isset($this->next) && $this->next!=$this->last): ?>
		<li class="next"><a href="<?= $this->url(array('page' => $this->next)); ?>"><?= $this->next; ?></a></li>
		<li class="disabled"><a href="#">...</a></li>
	<?php endif; */?>
	
	<?php if (isset($this->next)): ?>
		<li class="last"><a href="<?= $this->url(array('page' => $this->last)); ?>">&rarr; <?= $this->last; ?></a></li>
	<?php endif; ?>

	</ul>
<?php endif; ?>
