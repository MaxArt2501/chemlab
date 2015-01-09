$(function() {
	$(".item-description").each(function() {
		var $this = $(this);
		$this.html(textile.convert($this.text()));
	});
});