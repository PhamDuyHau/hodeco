/**
 * Initialize CodeMirror editors.
 */
export function initCodeMirror() {
	if (typeof codemirror_settings === 'undefined') return;

	const codemirrorCss = document.querySelectorAll('.codemirror_css');
	const codemirrorHtml = document.querySelectorAll('.codemirror_html');

	function initialize(elements, settings) {
		elements.forEach((el) => {
			if (!el.CodeMirror) {
				const editorSettings = settings ? { ...settings } : {};
				editorSettings.codemirror = {
					...editorSettings.codemirror,
					indentUnit: 3,
					tabSize: 3,
					autoRefresh: true,
				};
				el.CodeMirror = wp.codeEditor.initialize(el, editorSettings);
			}
		});
	}

	initialize(codemirrorCss, codemirror_settings.codemirror_css);
	initialize(codemirrorHtml, codemirror_settings.codemirror_html);
}
