/**
 * Filter tabs navigation.
 *
 * @param {jQuery} $ jQuery reference.
 */
export function initFilterTabs($) {
	const $filterTabs = $('.filter-tabs');
	$filterTabs.each(function () {
		const $el = $(this);
		const $nav = $el.find('.tabs-nav');
		const $content = $el.find('.tabs-content');
		const $tabs = $nav.find('a');
		const initialHash = window.location.hash;

		const activateTab = (hash) => {
			const $tab = $nav.find(`a[href="${hash}"]`);
			$nav.find('a').removeClass('current');
			$content.find('.tabs-panel').hide();

			if ($tab.length) {
				$tab.addClass('current');
				$(hash).show();
			} else {
				$tabs.first().addClass('current');
				$content.find('.tabs-panel').first().show();
				window.history.replaceState(null, null, window.location.pathname + window.location.search);
			}
		};

		activateTab(initialHash || $tabs.first().attr('href'));

		$nav.on('click', 'a', function (e) {
			e.preventDefault();
			const hash = $(this).attr('href');
			window.location.hash = hash;
			activateTab(hash);
			$('html, body').animate({ scrollTop: $el.offset().top - $('header').outerHeight() }, 300);
		});

		$(window).on('hashchange', function () {
			activateTab(window.location.hash || $tabs.first().attr('href'));
		});
	});
}
