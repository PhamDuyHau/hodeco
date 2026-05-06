/**
 * HDA Admin Scripts
 *
 * Entry point — global admin pages.
 */

import './utils/jquery-plugins.js';

import { initContactLinkRepeater } from './modules/admin/contact-link-repeater.js';
import { initEditorToggle } from './modules/admin/editor-toggle.js';
import { initMediaUpload } from './modules/admin/media-upload.js';

jQuery(function ($) {
	// Notice dismiss handler
	$(document).on('click', '.notice-dismiss', function () {
		$(this).closest('.notice.is-dismissible')?.fadeOutAndRemove(500);
	});

	// Initialize modules
	initContactLinkRepeater($);
	initEditorToggle();
	initMediaUpload($);
});
