// offcanvas/fx-offcanvas.core.js

import Events from '../../events.js';
import { trigger } from '../../dom.js';
import { lockScroll, unlockScroll } from './fx-overlay.js';
import { bindSwipe, unbindSwipe } from './fx-swipe.js';

export const isOpen = (panel) => panel.classList.contains('is-open');

export const openOffCanvas = (panel, overlay) => {
	if (isOpen(panel)) return;

	panel.classList.add('is-open');
	panel.classList.remove('is-closed');
	overlay.classList.add('is-visible', 'is-closable');

	if (panel.dataset.contentScroll === 'false') lockScroll();
	bindSwipe(panel, overlay, () => closeOffCanvas(panel, overlay));

	Events.emit('fx:offcanvas:open', { el: panel });
	trigger(panel, 'fx.offcanvas.opened', { el: panel });
};

export const closeOffCanvas = (panel, overlay) => {
	if (!isOpen(panel)) return;

	panel.classList.remove('is-open');
	panel.classList.add('is-closed');
	overlay.classList.remove('is-visible');

	unlockScroll();
	unbindSwipe(panel);

	Events.emit('fx:offcanvas:close', { el: panel });
	trigger(panel, 'fx.offcanvas.closed', { el: panel });
};
