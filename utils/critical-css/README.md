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
