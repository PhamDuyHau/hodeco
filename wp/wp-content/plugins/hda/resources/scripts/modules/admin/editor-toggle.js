/**
 * Editor module — Block Editor toggle.
 */
export function initEditorToggle() {
	const blockEditorToggle = document.getElementById('use_block_editor_for_post_type_off');
	const blockEditorDependents = document.querySelectorAll('.block-editor-dependent');

	if (!blockEditorToggle || !blockEditorDependents.length) return;

	blockEditorToggle.addEventListener('change', function () {
		blockEditorDependents.forEach((el) => {
			el.classList.toggle('hidden', this.checked);
		});
	});
}
