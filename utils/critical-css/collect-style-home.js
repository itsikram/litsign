/*
 * Paste into the browser console on the home page (logged out), after copying
 * style-selectors.json (made by cssparse.py from css/style.css) to the web
 * root. Adds the ids of every style.css rule that matches an element on the
 * page to localStorage.critKeepStyleHome. Run it at phone, tablet and desktop
 * widths, after scrolling to the bottom, and with the mobile menu and an FAQ
 * answer open, then read the ids with:
 *   JSON.parse(localStorage.critKeepStyleHome).sort((a, b) => a - b).join(',')
 * and save them to keep-style-home.txt. See README.md.
 */
(async () => {
	const rules = await (await fetch('/style-selectors.json')).json();
	// Same as collect.js, plus open/active state classes and attributes that
	// scripts toggle, so ".faq.is-open .answer" is kept whenever a .faq exists.
	const dynamic = /::?-[\w-]+(\([^)]*\))?|::[\w-]+(\([^)]*\))?|:(hover|focus|focus-visible|focus-within|active|visited|link|checked|disabled|enabled|invalid|valid|indeterminate|placeholder-shown|autofill|read-only|read-write|target|default|required|optional|user-invalid|user-valid)(?![\w-])/g;
	const state = /\.(active|show|showing|open|opened|in|is-[\w-]+|collapsed|collapsing|expanded|visible|selected|current|sticky|scrolled|fixed|loaded|sk-done|sk-swap|sh-tucked|ssxm-lock|modal-open)(?![\w-])|\[(aria-expanded|open|hidden|aria-hidden|aria-current|aria-selected|aria-pressed|data-state|data-open)[^\]]*\]/g;

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
			selector = selector.replace(dynamic, '').replace(state, '').replace(/:not\(\s*\)/g, '').trim();
			if (!selector || /[>+~]$/.test(selector)) selector += '*';
			try {
				if (document.querySelector(selector)) return true;
			} catch (e) {
				return true; // Keep anything the browser cannot test.
			}
		}
		return false;
	}

	const keep = new Set(JSON.parse(localStorage.critKeepStyleHome || '[]'));
	for (const rule of rules) {
		if (!keep.has(rule.id) && matches(rule.s)) keep.add(rule.id);
	}
	localStorage.critKeepStyleHome = JSON.stringify([...keep]);
	return keep.size;
})();
