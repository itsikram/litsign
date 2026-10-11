/*
 * Paste into the browser console on a page of the site (logged out), after
 * copying selectors.json (made by cssparse.py) to the web root. Adds the ids of
 * every Bootstrap rule that matches an element on the page to localStorage;
 * run it on each page type, then read the ids with:
 *   JSON.parse(localStorage.critKeep).sort((a, b) => a - b).join(',')
 * and save them to keep.txt. See README.md.
 */
(async () => {
	const rules = await (await fetch('/selectors.json')).json();
	// Pseudo-elements and state pseudo-classes are dropped before matching, so a
	// rule like ".btn:hover" is kept whenever a .btn exists.
	const dynamic = /::?-[\w-]+(\([^)]*\))?|::[\w-]+(\([^)]*\))?|:(hover|focus|focus-visible|focus-within|active|visited|link|checked|disabled|enabled|invalid|valid|indeterminate|placeholder-shown|autofill|read-only|read-write|target|default|required|optional|user-invalid|user-valid)(?![\w-])/g;

	function split(list) {
		const out = [];
		let depth = 0;
		let current = '';
		for (const ch of list) {
			if (ch === '(') depth++;
			if (ch === ')') depth--;
			if (ch === ',' && !depth) {
				out.push(current);
				current = '';
			} else {
				current += ch;
			}
		}
		out.push(current);
		return out;
	}

	function matches(list) {
		for (let selector of split(list)) {
			selector = selector.replace(dynamic, '').trim();
			if (!selector || /[>+~]$/.test(selector)) selector += '*';
			try {
				if (document.querySelector(selector)) return true;
			} catch (e) {
				return true; // Keep anything the browser cannot test.
			}
		}
		return false;
	}

	const keep = new Set(JSON.parse(localStorage.critKeep || '[]'));
	for (const rule of rules) {
		if (!keep.has(rule.id) && matches(rule.s)) keep.add(rule.id);
	}
	localStorage.critKeep = JSON.stringify([...keep]);
	return keep.size;
})();
