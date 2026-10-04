<?php
/**
 * Draft text for the Privacy Policy and Shipping & Returns pages. The
 * migration in inc/seo.php saves these as DRAFTS for the owner to review,
 * complete (every [PLACEHOLDER]) and publish; they are not legal advice.
 *
 * @package litsign
 */

/**
 * Privacy Policy draft, describing what the site's code actually collects.
 */
function wholesale_policy_privacy_draft()
{
	return <<<'HTML'
<p><strong>[DRAFT FOR REVIEW. Complete every [PLACEHOLDER], have it reviewed, then publish. Delete this line before publishing.]</strong></p>
<p>Last updated: [PLACEHOLDER: date]</p>

<h2>Who we are</h2>
<p>This website, storefrontsignonline.com, is operated by Storefront Sign Online LLC, also doing business as Lit Sign Manufacturing ("we", "us"). You can reach us at <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a>, by phone at 866-436-2101, or by mail at 707 S. Grady Way, Suite 600, Renton, WA 98057.</p>

<h2>Information we collect</h2>
<ul>
<li><strong>Orders:</strong> your name, email address, phone number, billing and shipping addresses, the products and options you choose, and any artwork or logo files you upload.</li>
<li><strong>Quote and contact forms:</strong> your name, business name, phone number, email address, ZIP code, project details and any logo file you attach.</li>
<li><strong>Accounts:</strong> if you create an account, your email address and a password (stored in encrypted form), plus your order history.</li>
<li><strong>Newsletter:</strong> your email address, if you subscribe.</li>
<li><strong>Reviews:</strong> your name, email address, rating and review text, if you leave a review.</li>
<li><strong>Live chat:</strong> messages you send through the chat window, which is provided by Tidio.</li>
<li><strong>Usage data:</strong> pages visited, device and browser information, and how you arrived at the site, collected through the cookies and tools described below.</li>
</ul>

<h2>Payments</h2>
<p>Card payments are processed by Elavon Converge. Your card details are sent to the payment processor to authorize the payment; we do not store your full card number.</p>

<h2>Cookies and advertising tools</h2>
<ul>
<li><strong>Site cookies</strong> keep your cart, your login and your checkout details working.</li>
<li><strong>Google Analytics</strong> helps us understand how visitors use the site.</li>
<li><strong>Google Ads conversion tracking</strong> tells us when a visit from one of our ads leads to an order, a quote request or a call. When you arrive from an ad, we remember the ad's click ID in a first-party cookie for up to 90 days and save it with your order or quote request.</li>
<li><strong>Enhanced conversions:</strong> when you place an order or request a quote, your email address, phone number and address are sent to Google in hashed (encoded) form so Google can match the conversion to an ad click. Google does not use this data to sell to other advertisers.</li>
<li><strong>Tidio</strong> provides the live chat window.</li>
</ul>
<p>You can opt out of personalized Google ads at <a href="https://adssettings.google.com">adssettings.google.com</a> and out of Google Analytics with Google's <a href="https://tools.google.com/dlpage/gaoptout">opt-out browser add-on</a>. Blocking cookies in your browser may stop the cart and checkout from working.</p>

<h2>How we use your information</h2>
<ul>
<li>To make, ship and support your order, and to send order confirmations and status updates.</li>
<li>To answer quote requests, calls, chats and messages.</li>
<li>To send our newsletter, only if you subscribe; every email has an unsubscribe link.</li>
<li>To measure and improve our website and advertising.</li>
<li>To prevent fraud and meet legal obligations.</li>
</ul>

<h2>Who we share it with</h2>
<p>We do not sell your personal information. We share it only with companies that help us run the business: our payment processor, shipping carriers, Google (analytics, advertising and business email), Tidio (live chat), our website host, and [PLACEHOLDER: any production partner that receives order details to make your sign]. We may also disclose information when the law requires it.</p>

<h2>How long we keep it</h2>
<p>[PLACEHOLDER: for example, order records for as long as tax and accounting rules require, and quote requests for [X] years.]</p>

<h2>Your choices and rights</h2>
<p>You can ask us to show, correct or delete the personal information we hold about you by emailing <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a>. [PLACEHOLDER: add any state-specific rights that apply to your business, such as California.]</p>

<h2>Children</h2>
<p>This site is for businesses and is not directed to children under 13. We do not knowingly collect information from children.</p>

<h2>Changes</h2>
<p>We may update this policy. The date at the top shows when it last changed.</p>
HTML;
}

/**
 * Shipping & Returns draft, restating the site's existing Terms & Conditions
 * and shipping information in one place.
 */
function wholesale_policy_shipping_draft()
{
	return <<<'HTML'
<p><strong>[DRAFT FOR REVIEW. Check every statement against how you work today, then publish. Delete this line before publishing.]</strong></p>

<h2>Made to order</h2>
<p>Every sign is made to order. Production begins after you approve your order, and your estimated ship date is shown at checkout.</p>

<h2>Shipping options</h2>
<ul>
<li>After production, choose standard (3 to 6 business days), 3-day, 2-day or overnight shipping at checkout.</li>
<li>Adhesive products ordered by 4pm PST ship the next business day. Same-day service is available if ordered by 12pm PST.</li>
<li>Pickup is not available; every order ships directly to you.</li>
<li>Channel letters ship ready to install, with a wiring diagram and installation pattern.</li>
</ul>

<h2>Cancellations</h2>
<p>Orders cannot be stopped or cancelled once they are approved for production, and approved orders are not refundable.</p>

<h2>Problems with your order</h2>
<p>If something is wrong with your order, report it within 5 business days of delivery by emailing <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a> or calling 866-436-2101 or 866-436-2066 (Option 1).</p>
<ul>
<li>We will open a claim and may ask for photos of the problem.</li>
<li>In some cases we may ask you to ship the item back. If a defect is confirmed, we may reimburse that return shipping.</li>
<li>Rush production and expedited shipping charges are not refundable for defective products, unless the carrier delivers a damaged order or fails to deliver it.</li>
<li>Turnaround and shipping for reprints depend on production capacity.</li>
</ul>

<h2>Shipping damage</h2>
<p>Inspect the packaging before you sign for delivery. If the box is damaged, check the contents before accepting the shipment and contact us right away. [PLACEHOLDER: confirm who files carrier claims, you or the customer.]</p>

<h2>Warranty</h2>
<p>Every electrical product we sell, including our channel letters, is UL listed, made in the USA and covered by a five-year warranty from Storefront Sign Online LLC. [PLACEHOLDER: what the warranty covers and excludes, and how to make a claim.]</p>

<p>Full terms: see our <a href="/terms-conditions/">Terms &amp; Conditions</a>.</p>
HTML;
}
