// offcanvas/fx-offcanvas.js
import './fx-offcanvas.scss';

import { $, $$, on, off, closest } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import { getOverlay } from './fx-overlay.js';
import { openOffCanvas, closeOffCanvas } from './fx-offcanvas.core.js';

const OC = 'data-fx-off-canvas';
const OPEN = 'data-open';
const CLOSE = 'data-close';

const openHandlers = createWeakStore();
const closeHandlers = createWeakStore();
let overlayHandler = null;

const FxOffCanvas = {
	initAll(root = document) {
		const overlay = getOverlay();

		// OPEN
		$$(`[${OPEN}]`, root).forEach((btn) => {
			const id = btn.getAttribute(OPEN);
			const panel = document.getElementById(id);
			if (!panel || !panel.hasAttribute(OC)) return;

			const h = (e) => {
				e.preventDefault();
				openOffCanvas(panel, overlay);
				btn.setAttribute('aria-expanded', 'true');
			};

			openHandlers.set(btn, h);
			on(btn, 'click', h);
		});

		// CLOSE
		$$(`[${CLOSE}]`, root).forEach((btn) => {
			const sel = btn.getAttribute(CLOSE);
			const panel = sel ? $(sel) : closest(btn, `[${OC}]`);
			if (!panel) return;

			const h = (e) => {
				e.preventDefault();
				closeOffCanvas(panel, overlay);
			};

			closeHandlers.set(btn, h);
			on(btn, 'click', h);
		});

		// OVERLAY CLICK -> CLOSE ALL (singleton)
		if (!overlayHandler) {
			overlayHandler = () => {
				$$(`[${OC}]`).forEach((p) => closeOffCanvas(p, overlay));
			};
			on(overlay, 'click', overlayHandler);
		}
	},

	destroyAll(root = document) {
		$$(`[${OPEN}]`, root).forEach((btn) => {
			const h = openHandlers.get(btn);
			if (h) {
				off(btn, 'click', h);
				openHandlers.delete(btn);
			}
		});

		$$(`[${CLOSE}]`, root).forEach((btn) => {
			const h = closeHandlers.get(btn);
			if (h) {
				off(btn, 'click', h);
				closeHandlers.delete(btn);
			}
		});

		// Only remove overlay handler when destroying entire document
		if (root === document && overlayHandler) {
			off(getOverlay(), 'click', overlayHandler);
			overlayHandler = null;
		}
	},
};

export default FxOffCanvas;
