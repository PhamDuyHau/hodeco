/**
 * Database Optimizer — Select All + Run Selected Now.
 */
export function initDbOptimizer() {
	const btn = document.getElementById('hda-db-optimize-btn');
	const statusEl = document.getElementById('hda-db-optimize-status');
	const checks = document.querySelectorAll('.hda-db-check');
	const selectAll = document.getElementById('hda-db-select-all');

	if (!btn || !selectAll || !checks.length) return;

	const nonce = window.hdaDbOptimizer?.nonce || '';

	function updateBtn() {
		const anyChecked = [...checks].some((c) => c.checked);
		const allChecked = [...checks].every((c) => c.checked);

		btn.disabled = !anyChecked;
		selectAll.checked = allChecked;
		selectAll.indeterminate = !allChecked && anyChecked;
	}

	selectAll.addEventListener('change', () => {
		checks.forEach((c) => {
			c.checked = selectAll.checked;
		});
		updateBtn();
	});

	checks.forEach((c) => c.addEventListener('change', updateBtn));
	updateBtn();

	btn.addEventListener('click', () => {
		const tasks = [...checks]
			.filter((c) => c.checked)
			.map((c) => c.name.match(/\[(\w+)\]/)?.[1])
			.filter(Boolean);

		if (!tasks.length) return;

		btn.disabled = true;
		statusEl.textContent = window.hdaDbOptimizer?.i18n?.optimizing || 'Optimizing...';
		statusEl.style.color = '#0073aa';

		const fd = new FormData();
		fd.append('action', 'hda_db_optimize');
		fd.append('_nonce', nonce);
		tasks.forEach((t) => fd.append('tasks[]', t));

		fetch(ajaxurl, { method: 'POST', body: fd })
			.then((r) => r.json())
			.then((data) => {
				if (data.success) {
					const res = data.data.results;
					const summary = Object.entries(res)
						.map(([k, v]) => `${k}: ${v}`)
						.join(', ');
					statusEl.textContent = `✅ ${summary}`;
					statusEl.style.color = '#46b450';

					document.querySelectorAll('.hda-db-count').forEach((el) => {
						el.textContent = '0';
						el.closest('strong').style.color = '#46b450';
					});
				} else {
					statusEl.textContent = `❌ ${data.data?.message || 'Error'}`;
					statusEl.style.color = '#d63638';
				}
				btn.disabled = false;
			})
			.catch(() => {
				statusEl.textContent = '❌ Request failed';
				statusEl.style.color = '#d63638';
				btn.disabled = false;
			});
	});
}
