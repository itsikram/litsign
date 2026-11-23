# Bootstrap subset for the first paint

`css/bootstrap-critical.css` holds only the Bootstrap 5.3.3 rules that match
elements on the site's public pages (about 8 KB gzipped instead of 31 KB).
`inc/performance.php` (`wholesale_critical_bootstrap_tag()`) prints it in place
of `css/bootstrap.min.css` on content and landing pages and adds the full file
after the page loads. Product, cart, checkout, account and builder pages keep
the full file. If `bootstrap.min.css` is ever newer than the subset, the theme
falls back to the full file automatically.

Regenerate after adding Bootstrap classes to templates (until then, a missing
rule only applies once the page has loaded):

1. `python cssparse.py ../../css/bootstrap.min.css selectors.json` and copy
   `selectors.json` to the web root.
2. Logged out, open each page type (home, landing pages, category, location,
   industry, contact, shop, search, 404) and run `collect.js` in the console.
3. Save the ids it prints to `keep.txt`, then
   `python build.py ../../css/bootstrap.min.css ../../css/bootstrap-critical.css`.
4. Delete `selectors.json` from the web root.

To check a page: with the page loaded, disable the `#bootsrap-css` link in
DevTools; nothing on the page should move or change colour.

# Home page subset of style.css

`css/style-home-critical.css` holds the `css/style.css` rules that match
elements on the home page (about 7 KB gzipped instead of 27 KB).
`wholesale_critical_home_style_tag()` in `inc/performance.php` prints it in
place of `style.css` on the front page and adds the full file after load.
Whenever `style.css` is newer than the subset, the home page loads the full
file again (safe, just slower), so rebuild after editing `style.css`:

1. `python cssparse.py ../../css/style.css style-selectors.json` and copy
   `style-selectors.json` to the web root (`C:\xampp\htdocs\`).
2. Logged out, open the home page and run `localStorage.removeItem('critKeepStyleHome')`.
   Then, at phone (412px), tablet (820px) and desktop (1366px) widths, scroll
   to the bottom, open the mobile menu and an FAQ answer, and run
   `collect-style-home.js` each time.
3. Save the ids it prints to `keep-style-home.txt`, then
   `python build_style_home.py ../../css/style.css ../../css/style-home-critical.css`.
4. Delete `style-selectors.json` from the web root.
5. Check: paste `verify.js` into the console of the home page and run
   `await verifySubset('custom-style-css', document.getElementById('custom-style-critical-css').href)`
   at each width. It must report `diffs: 0` (2026-10-27 build: 0 of about
   1,970 elements at 412, 820 and 1366px, and with the menu and FAQ open).
