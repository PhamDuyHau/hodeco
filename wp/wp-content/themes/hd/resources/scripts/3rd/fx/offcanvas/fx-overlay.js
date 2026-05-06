// offcanvas/fx-overlay.js

const CLASS = 'js-off-canvas-overlay';
const LOCK = 'is-off-canvas-open';

let overlay = null;

export const getOverlay = () => {
	if (!overlay) {
		overlay = document.createElement('div');
		overlay.className = `${CLASS} is-overlay-fixed`;
		document.body.appendChild(overlay);
	}
	return overlay;
};

export const lockScroll = () => {
	document.body.classList.add(LOCK);
};

export const unlockScroll = () => {
	document.body.classList.remove(LOCK);
};
