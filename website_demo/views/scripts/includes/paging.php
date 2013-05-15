<?php
// made by Twitter user @remisouverain
//my_pagination_control.phtml
if ($this->pageCount): ?>
<div class="pagination paginationControl pagination-left">
	<ul>
	<!-- <li class="desc disabled"><a href="#"><?php echo $this->firstItemNumber; ?>-<?php echo $this->lastItemNumber; ?> / <?php echo $this->totalItemCount; ?></a></li> -->
	
	<?php if (isset($this->previous)): ?>
		<li class="first"><a href="<?php echo $this->url(array('page' => $this->first)); ?>"><?php echo $this->first; ?> &larr;</a></li>
	<?php endif; ?>
	
	<?php /*if (isset($this->previous) && $this->previous!=$this->first): ?>
		<li class="disabled"><a href="#">...</a></li>
		<li class="previous"><a href="<?php echo $this->url(array('page' => $this->previous)); ?>"><?php echo $this->previous; ?></a></li>
	<?php endif; */?>
	
	
<?php foreach ($this->pagesInRange as $page)
{
	$class = '';
	if ($page == $this->current) $class = 'active';
	if( ($this->first < $page) && ($page < $this->last) || $page == $this->current)
	{
?>
		<li class="<?php echo $class; ?>"><a href="<?php echo $this->url(array('page' => $page)); ?>"><?php echo $page; ?></a></li>
<?php
	}
}
?>
	
	<?php /*if (isset($this->next) && $this->next!=$this->last): ?>
		<li class="next"><a href="<?php echo $this->url(array('page' => $this->next)); ?>"><?php echo $this->next; ?></a></li>
		<li class="disabled"><a href="#">...</a></li>
	<?php endif; */?>
	
	<?php if (isset($this->next)): ?>
		<li class="last"><a href="<?php echo $this->url(array('page' => $this->last)); ?>">&rarr; <?php echo $this->last; ?></a></li>
	<?php endif; ?>

	</ul>
</div>
<?php endif; ?>
