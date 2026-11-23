<?php
/**
 * Trade Terms and Conditions, adapted from the supplier's published terms.
 * Source: https://www.b2sign.com/terms-and-conditions (checked November 11, 2026).
 * B2's business identity, domain, phone and email are replaced with ours.
 * Product-specific warranty periods and coverage remain on product pages.
 *
 * @package litsign
 */

$sections = array(
	'agreement' => 'TERMS AND CONDITIONS',
	'copyright' => 'Copyrighted Materials for Limited Use',
	'use' => 'Use of Site',
	'cancellation' => 'Order Cancellation Policy',
	'claims' => 'Returns/Refund/Reprint Policy',
	'proofs' => 'Customer Supplied Proofs',
	'color' => 'Color Proof Accuracy',
	'indemnity' => 'Indemnity',
	'sms' => 'SMS TERMS AND CONDITIONS',
	'refusal' => 'Right of Refusal',
);

get_header();
?>
<main id="primary" class="tw-page tw-terms">
    <section class="tw-hero">
        <div class="tw-wrap"><?php wholesale_trade_render_crumbs(); ?></div>
        <div class="tw-wrap tw-legal__intro">
            <p class="tw-eyebrow">Trade terms</p>
            <h1 class="tw-hero__title">Terms and Conditions</h1>
        </div>
    </section>
    <div class="tw-wrap tw-legal">
        <aside class="tw-legal__aside">
            <details class="tw-legal__toc" id="tw-legal-toc" open>
                <summary>On this page</summary>
                <nav aria-label="Terms and Conditions sections">
                    <ol>
                        <?php foreach ($sections as $id => $title) : ?>
                            <li><a href="#<?php echo esc_attr($id); ?>"><?php echo esc_html($title); ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </details>
            <script>if(window.matchMedia('(max-width: 991px)').matches){document.getElementById('tw-legal-toc').removeAttribute('open');}</script>
        </aside>
        <article class="tw-legal__body">
            <h2 class="tw-legal__heading" id="agreement">TERMS AND CONDITIONS</h2>
            <p>Welcome to storefrontsignwholesale.com. We provide our Website as a service to our customers. Please read these Terms and Conditions carefully. These Terms and Conditions govern your access and use of storefrontsignwholesale.com. By accessing or using storefrontsignwholesale.com you agree to be bound by all of the Terms and Conditions and to any additional guidelines, restrictions, or rules that may be posted in connection with specific sections or services of this Website. storefrontsignwholesale.com reserves the right to make changes to this Site and to these Terms and Conditions at any time without prior notice. Your continued use of this Site is and shall be deemed to be your agreement to be bound by any such modifications, alterations or updates.  </p>
            <p>You also agree that we may provide all legal communications and notices to you electronically by posting them on storefrontsignwholesale.com or, at our election, by sending an e-mail to the e-mail address you provided to us when you registered at our Website. You may withdraw your consent to receive communications from us electronically by emailing us at Info@StorefrontSignWholesale.com. However, if you withdraw your consent to receive our communications electronically, you must discontinue your use of your account at storefrontsignwholesale.com.</p>
            <h2 class="tw-legal__heading" id="copyright">Copyrighted Materials for Limited Use</h2>
            <p>storefrontsignwholesale.com contains graphics, photographs, images, document layouts, artwork, text, fonts, software tools, and other information (referred to herein as &quot;Content&quot;). This Site and all Content are the copyrighted property of storefrontsignwholesale.com and/or its subsidiaries (referred to herein as &quot;storefrontsignwholesale.com&quot;) or the copyrighted property of parties from whom storefrontsignwholesale.com has licensed such property. All rights in this Site and its Content are reserved worldwide. It is strictly prohibited to retain the copy, distribute, publish, or use any portion of the Content except as expressly allowed in these Terms and Conditions. storefrontsignwholesale.com reserves the right to add, delete, or modify any part of the Content at any time without prior notice.</p>
            <h2 class="tw-legal__heading" id="use">Use of Site</h2>
            <p>You are granted permission to access and use this Site and its Content for the sole purpose of preparing, evaluating, and ordering products or services solely through storefrontsignwholesale.com (referred to herein as &quot;Products&quot;). No other download, retention, use, publication, or distribution of any portion of the Content is authorized or permitted. Obtaining Products from storefrontsignwholesale.com does not entitle you to use any portion of Content apart from the finished Products as they are supplied by storefrontsignwholesale.com. You agree to use this Site in a responsible manner that is in full compliance with these Terms and Conditions and with your local laws and regulations, including import and export regulations. Without limitation, no portion of Content may be utilized as a trademark or service mark, for any pornographic use, for any unlawful purpose or use, to defame any person, to violate any person&#39;s right of privacy or publicity, to infringe upon any copyright, trade name, trademark, service mark or other intellectual property right of any person or entity. You agree that you will not use the Site to produce Products that are offensive, unlawful, harassing, libelous, threatening, harmful, obscene, malicious or otherwise objectionable. storefrontsignwholesale.com may terminate its service to customers found to be using storefrontsignwholesale.com to engage in undesirable activities. You are solely responsible for your use of Content in combination with any other images, graphics, text or other materials you incorporate into your Products. You agree that you will not include any text, image, design, trademark, service mark, or any copyrighted work of any third party in your Products unless you have obtained the appropriate authorizations from the owners. You warrant that your Products do not infringe upon any rights of any third party, including copyright, trademark, right of publicity or privacy, and will not libel or defame any third party, and you have all required rights or permissions necessary to incorporate third party material into your Products. By placing an order thru storefrontsignwholesale.com, you warrant that you have all necessary permission, right and authority to place the order and you authorize storefrontsignwholesale.com to produce the Products on your behalf.</p>
            <h2 class="tw-legal__heading" id="cancellation">Order Cancellation Policy</h2>
            <p>Jobs cannot be stopped and/or cancelled once they are in “Approved” status. There will be no refunds of any kind after a job has been approved.</p>
            <h2 class="tw-legal__heading" id="claims">Returns/Refund/Reprint Policy</h2>
            <p>storefrontsignwholesale.com focuses on providing our customers with the best possible printing experience and products. If there is any issue with your order, you must report the problem within five (5) business days. You can submit your claim by calling our Customer Service Dept at 866-436-2101 or via email at Info@StorefrontSignWholesale.com.  </p>
            <p>Our Customer Service Representative will document your complaint and create a claim for you to reference as we investigate how to best resolve the issue. All defects and/or order issues must be reported to Lit Sign Manufacturing LLC within 5 business days of receiving your order. Customer will be requested to submit digital photos to document the product’s defects. In some cases, customer will be required to ship defective product(s) back to Storefront Sign Wholesale at their own expense and in an agreed upon reasonable time frame. In cases where the order is required to be shipped back to Storefront Sign Wholesale at the customer’s expense, Storefront Sign Wholesale may reimburse the customer for shipping if a defect is determined to exist. Any charges related to expedited orders, such as rush printing or shipping, are non-refundable for defective products unless FedEx delivers a defective product or fails to deliver an order. Turnaround time and shipping options for reprinted orders varies due to available production capacity and are at Storefront Sign Wholesale’s discretion. Turnaround time begins after receipt of final proof approval. Final approval will only be accepted in written format. No verbal approvals will be accepted.</p>
            <h2 class="tw-legal__heading" id="proofs">Customer Supplied Proofs</h2>
            <p>We are not responsible for any and all errors in the customer’s printing job if they select the “No Proof. Run As Is.” option.</p>
            <h2 class="tw-legal__heading" id="color">Color Proof Accuracy</h2>
            <p>Please note color proofs cannot match the final product 100% due to the fact that proofs are created in a different way than the printed piece and the customer supplied proofs are not calibrated to our presses.</p>
            <h2 class="tw-legal__heading" id="indemnity">Indemnity</h2>
            <p>You agree to indemnify, defend and hold storefrontsignwholesale.com harmless, its partners, affiliates, subsidiaries and suppliers from any liability, loss, claim and expense related to your violation of this Terms and Conditions. You agree to defend, indemnify, and hold storefrontsignwholesale.com harmless from any and all claims arising from the improper use of trademark, copyright, or other protected material.</p>
            <h2 class="tw-legal__heading" id="sms">SMS TERMS AND CONDITIONS</h2>
            <p>By opting in, you agree to receive SMS text messages from Lit Sign Manufacturing LLC for customer service and other related communications.</p>
            <p>Message frequency may vary.</p>
            <p>Message and data rates may apply.</p>
            <p>Reply STOP to opt out.</p>
            <p>HELP for assistance.</p>
            <p>Consent to receive SMS messages is not a condition of purchase.</p>
            <p>For more information, please see our Privacy Policy: <a href="https://storefrontsignonline.com/privacy-policy/">https://storefrontsignonline.com/privacy-policy/</a>.</p>
            <h2 class="tw-legal__heading" id="refusal">Right of Refusal</h2>
            <p>We reserve the right to refuse service or products to any person or organization.</p>
        </article>
    </div>
</main>
<?php
get_footer();
