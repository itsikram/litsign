<?php
/**
 * /trade-account/: the trade application form, the "check your email" and
 * verification messages, and the application status for logged-in users.
 * The handlers run first (inc/trade/registration.php).
 *
 * @package litsign
 */

$form = isset($GLOBALS['wholesale_trade_form']) ? $GLOBALS['wholesale_trade_form'] : array('errors' => array(), 'values' => array());
$errors = $form['errors'];
$values = $form['values'];
$verify = isset($GLOBALS['wholesale_trade_verify_result']) ? $GLOBALS['wholesale_trade_verify_result'] : '';
$submitted = isset($_GET['submitted']);
$brand = wholesale_trade_brand();
$user = wp_get_current_user();
$status = $user->ID ? wholesale_trade_status($user->ID) : '';

$value = static function ($key) use ($values) {
	return isset($values[$key]) ? (string) $values[$key] : '';
};
$field_class = static function ($key) use ($errors) {
	return 'tw-field' . (isset($errors[$key]) ? ' tw-field--error' : '');
};
$error = static function ($key) use ($errors) {
	if (isset($errors[$key])) {
		echo '<p class="tw-field__error" id="err-' . esc_attr($key) . '">' . esc_html($errors[$key]) . '</p>';
	}
};
$described = static function ($key) use ($errors) {
	return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . esc_attr($key) . '"' : '';
};
$text = static function ($key, $type = 'text', $attrs = '') use ($value, $field_class, $error, $described) {
	$fields = wholesale_trade_form_fields();
	$label = $fields[$key][0];
	$required = $fields[$key][1];
	echo '<div class="' . esc_attr($field_class($key)) . '"><label for="tw-' . esc_attr($key) . '">' . esc_html($label) . ($required ? '' : ' <span class="tw-opt">(optional)</span>') . '</label>';
	echo '<input type="' . esc_attr($type) . '" id="tw-' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value($key)) . '"' . ($required ? ' required' : '') . $attrs . $described($key) . '>';
	$error($key);
	echo '</div>';
};
$state_select = static function ($key) use ($value, $field_class, $error, $described) {
	$fields = wholesale_trade_form_fields();
	echo '<div class="' . esc_attr($field_class($key)) . '"><label for="tw-' . esc_attr($key) . '">' . esc_html($fields[$key][0]) . ($fields[$key][1] ? '' : ' <span class="tw-opt">(optional)</span>') . '</label>';
	echo '<select id="tw-' . esc_attr($key) . '" name="' . esc_attr($key) . '"' . ($fields[$key][1] ? ' required' : '') . $described($key) . '><option value="">Choose…</option>';
	foreach (wholesale_trade_us_states() as $code) {
		echo '<option value="' . esc_attr($code) . '"' . selected($value($key), $code, false) . '>' . esc_html($code) . '</option>';
	}
	echo '</select>';
	$error($key);
	echo '</div>';
};

get_header();
?>
<main id="primary" class="tw-page tw-trade-account">
	<section class="tw-hero">
		<div class="tw-wrap"><?php wholesale_trade_render_crumbs(); ?></div>
		<div class="tw-wrap" style="padding:28px 16px 44px">
			<p class="tw-eyebrow">Trade only</p>
			<h1 class="tw-hero__title" style="font-size:clamp(30px,4.4vw,46px)">Apply for a <em>trade account</em></h1>
			<p class="tw-hero__text" style="margin-bottom:0">For sign companies, installers, print shops, agencies, contractors and multi-location brands. Approved accounts see wholesale channel letter pricing, use the online builder and check out like any web order.</p>
		</div>
	</section>

	<div class="tw-wrap">
		<div class="tw-apply">
			<div>
				<?php if ('verified' === $verify || 'already' === $verify) : ?>
					<div class="tw-status">
						<span class="tw-status__badge">Pending review</span>
						<h2 class="tw-h2">Thanks, your email is confirmed</h2>
						<p>Your application is now with our team. We review every business by hand and email you the decision. Approved accounts see trade pricing as soon as they log in.</p>
						<p><a class="tw-btn tw-btn--primary" href="<?php echo esc_url(wholesale_trade_login_url(home_url('/trade-account/'))); ?>">Log in to check your status</a></p>
					</div>
				<?php elseif ('invalid' === $verify) : ?>
					<div class="tw-status">
						<span class="tw-status__badge tw-status__badge--rejected">Link expired</span>
						<h2 class="tw-h2">That confirmation link no longer works</h2>
						<p>Links last 3 days and work once. Log in and we will show your application status, or call trade sales at <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a>.</p>
					</div>
				<?php elseif ($submitted) : ?>
					<div class="tw-status">
						<span class="tw-status__badge">One more step</span>
						<h2 class="tw-h2">Check your email</h2>
						<p>We sent a confirmation link to your work email. Click it and your application goes straight to our team for review. The link works for 3 days.</p>
						<p style="color:var(--tw-muted)">Nothing there? Check spam, or call <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a>.</p>
					</div>
				<?php elseif ($user->ID) :
					$profile = wholesale_trade_profile($user->ID);
					$badge = in_array($status, array('approved'), true) ? 'approved' : (in_array($status, array('rejected', 'suspended'), true) ? $status : '');
					?>
					<div class="tw-status">
						<span class="tw-status__badge<?php echo $badge ? ' tw-status__badge--' . esc_attr($badge) : ''; ?>"><?php echo esc_html(wholesale_trade_status_label($status)); ?></span>
						<h2 class="tw-h2"><?php echo esc_html(!empty($profile['legal_name']) ? $profile['legal_name'] : $user->display_name); ?></h2>
						<?php if (wholesale_trade_can_order()) : ?>
							<p>Your trade account is active. Trade pricing shows on every channel letter page and in the builder.</p>
							<p><a class="tw-btn tw-btn--primary" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Open the builder</a> <a class="tw-btn tw-btn--ghost" href="<?php echo esc_url(home_url('/my-orders/')); ?>">My orders</a></p>
						<?php elseif ('email_unverified' === $status) : ?>
							<p>Please click the confirmation link we emailed to <strong><?php echo esc_html($user->user_email); ?></strong>. Your application goes to review as soon as you do.</p>
						<?php elseif ('pending_review' === $status) : ?>
							<p>Your application is with our team. We email you as soon as it is decided. Trade pricing opens on this login once you are approved.</p>
						<?php elseif ('info_requested' === $status) : ?>
							<p>We emailed you about a few details we still need. Reply to that email or call <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a>, and we will finish the review.</p>
						<?php elseif ('legacy_unreviewed' === $status) : ?>
							<p>This site is now trade only. Your account was created before trade accounts existed, so it has not been reviewed yet. Call trade sales at <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a> or email <a href="mailto:<?php echo esc_attr($brand['email']); ?>"><?php echo esc_html($brand['email']); ?></a> with your business name, website and address to complete your trade application.</p>
						<?php elseif ('rejected' === $status) : ?>
							<p>We could not approve a trade account for this business. Buying a sign for your own business? <a href="<?php echo esc_url($brand['retail_url']); ?>">Shop our retail site</a>.</p>
						<?php else : ?>
							<p>Your trade account is on hold. Please contact trade sales at <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a>.</p>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<form class="tw-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(wholesale_trade_apply_url()); ?>" novalidate>
						<?php if (!empty($errors['_form'])) : ?>
							<div class="tw-alert tw-alert--error" role="alert"><?php echo esc_html($errors['_form']); ?></div>
						<?php endif; ?>
						<input type="hidden" name="wholesale_trade_apply" value="1">
						<input type="hidden" name="trade_token" value="<?php echo esc_attr(wholesale_trade_form_token()); ?>">
						<?php wp_nonce_field('wholesale_trade_apply'); ?>
						<div class="tw-hp" aria-hidden="true">
							<label for="tw-company-fax">Company fax (leave empty)</label>
							<input type="text" id="tw-company-fax" name="company_fax" value="" tabindex="-1" autocomplete="off">
						</div>

						<fieldset>
							<legend>Your contact details</legend>
							<div class="tw-grid">
								<?php $text('first_name', 'text', ' autocomplete="given-name"'); ?>
								<?php $text('last_name', 'text', ' autocomplete="family-name"'); ?>
								<?php $text('job_title', 'text', ' autocomplete="organization-title"'); ?>
								<?php $text('mobile', 'tel', ' autocomplete="tel" inputmode="tel"'); ?>
								<?php $text('email', 'email', ' autocomplete="email"'); ?>
								<div class="<?php echo esc_attr($field_class('password')); ?>">
									<label for="tw-password">Create a password</label>
									<input type="password" id="tw-password" name="password" required minlength="10" autocomplete="new-password"<?php echo $described('password'); // Escaped in helper. ?>>
									<?php $error('password'); ?>
									<p class="tw-field__hint">At least 10 characters.</p>
								</div>
							</div>
						</fieldset>

						<fieldset>
							<legend>Your business</legend>
							<div class="tw-grid">
								<?php $text('legal_name', 'text', ' autocomplete="organization"'); ?>
								<?php $text('dba'); ?>
								<div class="<?php echo esc_attr($field_class('business_type')); ?>">
									<label for="tw-business_type">Business type</label>
									<select id="tw-business_type" name="business_type" required<?php echo $described('business_type'); // Escaped in helper. ?>>
										<option value="">Choose…</option>
										<?php foreach (wholesale_trade_business_types() as $key => $label) : ?>
											<option value="<?php echo esc_attr($key); ?>" <?php selected($value('business_type'), $key); ?>><?php echo esc_html($label); ?></option>
										<?php endforeach; ?>
									</select>
									<?php $error('business_type'); ?>
								</div>
								<?php $text('website', 'url', ' placeholder="yoursignco.com" inputmode="url"'); ?>
								<?php $text('business_phone', 'tel', ' inputmode="tel"'); ?>
								<?php $text('years_in_business', 'text', ' inputmode="numeric" maxlength="3"'); ?>
								<div class="tw-field--full"><?php $text('address_1', 'text', ' autocomplete="address-line1"'); ?></div>
								<?php $text('address_2', 'text', ' autocomplete="address-line2"'); ?>
								<?php $text('city', 'text', ' autocomplete="address-level2"'); ?>
								<?php $state_select('state'); ?>
								<?php $text('zip', 'text', ' autocomplete="postal-code" inputmode="numeric" maxlength="10"'); ?>
								<?php $state_select('incorporation_state'); ?>
								<div class="<?php echo esc_attr($field_class('monthly_volume')); ?>">
									<label for="tw-monthly_volume">Estimated channel letter sets per month</label>
									<select id="tw-monthly_volume" name="monthly_volume" required<?php echo $described('monthly_volume'); // Escaped in helper. ?>>
										<option value="">Choose…</option>
										<?php foreach (wholesale_trade_volume_options() as $key => $label) : ?>
											<option value="<?php echo esc_attr($key); ?>" <?php selected($value('monthly_volume'), $key); ?>><?php echo esc_html($label); ?></option>
										<?php endforeach; ?>
									</select>
									<?php $error('monthly_volume'); ?>
								</div>
							</div>
						</fieldset>

						<fieldset>
							<legend>Licenses &amp; documents</legend>
							<div class="tw-grid">
								<?php $text('contractor_license'); ?>
								<?php $state_select('contractor_license_state'); ?>
								<?php foreach (wholesale_trade_doc_fields() as $key => $label) :
									$required_doc = 'resale_certificate' === $key && get_option('wholesale_trade_require_resale');
									?>
									<div class="<?php echo esc_attr($field_class($key)); ?>">
										<label for="tw-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?><?php echo $required_doc ? '' : ' <span class="tw-opt">(optional)</span>'; ?></label>
										<input type="file" id="tw-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"<?php echo $required_doc ? ' required' : ''; ?><?php echo $described($key); // Escaped in helper. ?>>
										<?php $error($key); ?>
									</div>
								<?php endforeach; ?>
								<p class="tw-field__hint tw-field--full" style="margin:0">PDF, JPG or PNG up to 10 MB. A resale certificate is needed only for tax-exempt orders. Please don't send EINs, Social Security, bank or card numbers.</p>
							</div>
						</fieldset>

						<fieldset>
							<legend>Almost done</legend>
							<div class="tw-grid">
								<?php $text('heard_about'); ?>
								<div class="tw-field tw-field--full">
									<label for="tw-notes">Anything we should know about your shop or upcoming projects? <span class="tw-opt">(optional)</span></label>
									<textarea id="tw-notes" name="notes" rows="4" maxlength="2000"><?php echo esc_textarea($value('notes')); ?></textarea>
								</div>
								<div class="<?php echo esc_attr($field_class('agree_terms')); ?> tw-field--full">
									<label class="tw-check"><input type="checkbox" name="agree_terms" value="1" required<?php echo !empty($_POST['agree_terms']) ? ' checked' : ''; ?><?php echo $described('agree_terms'); // Escaped in helper. ?>> <span>I apply on behalf of a trade business and accept the <a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>" target="_blank" rel="noopener">trade terms and conditions</a>.</span></label>
									<?php $error('agree_terms'); ?>
								</div>
							</div>
						</fieldset>

						<button type="submit" class="tw-btn tw-btn--primary" style="width:100%">Send my application</button>
						<p class="tw-field__hint" style="text-align:center">Already have an account? <a href="<?php echo esc_url(wholesale_trade_login_url(home_url('/trade-account/'))); ?>">Log in</a></p>
					</form>
				<?php endif; ?>
			</div>

			<aside class="tw-apply__aside">
				<div class="tw-aside-card">
					<h2>What you get when approved</h2>
					<ul class="tw-checks">
						<li>Trade pricing on all 7 channel letter types</li>
						<li>The online builder: price complete sets in minutes</li>
						<li>Order online and track every order</li>
						<li>UL listed sets with wiring diagrams and patterns</li>
						<li>A trade-only supplier that never installs or sells to your clients</li>
					</ul>
				</div>
				<div class="tw-value" style="margin-top:16px">
					<h2 class="tw-h3">How review works</h2>
					<p>1. Confirm your email. 2. Our team checks your business details and website. 3. You get an email with the decision. We may ask for a resale certificate or license.</p>
				</div>
				<div class="tw-value" style="margin-top:16px">
					<h2 class="tw-h3">Questions?</h2>
					<p>Call <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a> (<?php echo esc_html($brand['hours']); ?>), text <a href="<?php echo esc_url($brand['text_href']); ?>"><?php echo esc_html($brand['text']); ?></a>, or email <a href="mailto:<?php echo esc_attr($brand['email']); ?>"><?php echo esc_html($brand['email']); ?></a>.</p>
				</div>
			</aside>
		</div>
	</div>
	<p class="tw-crosslink">Buying a sign for your own business? <a href="<?php echo esc_url($brand['retail_url']); ?>">Shop <?php echo esc_html($brand['retail_name']); ?></a>.</p>
</main>
<?php
get_footer();
