jQuery(document).ready(function ($) {
	$('.tabs-nav .item').on('click', function (e) {
		e.preventDefault();
		var $this = $(this);
		var targetId = $this.attr('data-tab');
		$('.tabs-nav .item').removeClass('active');
		$this.addClass('active');
		var $contentContainer = $('.tabs-content');
		$contentContainer.find('.main-content').removeClass('active');
		$('#' + targetId).addClass('active');
	});
});
