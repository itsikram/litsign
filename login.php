<?php
// template name: login


// Only ever send people back to a page on this site.
$redirect_url = isset($_REQUEST['redirect_ulr']) ? wp_validate_redirect(esc_url_raw(wp_unslash($_REQUEST['redirect_ulr'])), home_url('/')) : home_url('/');

if (is_user_logged_in()) {
    wp_safe_redirect($redirect_url);
    exit;
}

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['email'])) {
    check_admin_referer('wholesale_login', 'wholesale_login_nonce');

    $login = sanitize_text_field(wp_unslash($_POST['email']));
    $pass = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';

    // wp_signon() only accepts usernames on older WordPress; resolve an email to its username.
    if (is_email($login)) {
        $login_user = get_user_by('email', $login);
        if ($login_user) {
            $login = $login_user->user_login;
        }
    }

    $signed_in_user = wp_signon(
        array(
            'user_login' => $login,
            'user_password' => $pass,
            'remember' => true,
        ),
        is_ssl()
    );

    if (!is_wp_error($signed_in_user)) {
        // Same filter wp-login.php applies (trade sends pending accounts to their status page).
        wp_safe_redirect(apply_filters('login_redirect', $redirect_url, $redirect_url, $signed_in_user));
        exit;
    }

    wp_safe_redirect(add_query_arg(array(
        'type' => 'danger',
        'message' => rawurlencode('Invalid email/username or password.'),
        'redirect_ulr' => rawurlencode($redirect_url),
    ), get_permalink()));
    exit;
}
get_header();

$login_is_trade = function_exists('wholesale_is_trade_site') && wholesale_is_trade_site();
?>


<div class="container py-5">
    <div class="row">
        <div class="col-md-6 offset-md-3 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h1 class="fs-3">Login</h1>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php wp_nonce_field('wholesale_login', 'wholesale_login_nonce'); ?>
                        <input type="hidden" name="redirect_ulr" value="<?php echo esc_attr($redirect_url); ?>">
                        <div class="form-group mb-2">
                            <label for="login-email" class="form-label">Email</label>
                            <input type="text" required name="email" id="login-email" autocomplete="username" placeholder="Enter your email or username"
                                class="form-control">
                        </div>
                        <div class="form-group mb-2">
                            <label for="login-password" class="form-label">Password</label>
                            <input type="password" name="password" id="login-password" autocomplete="current-password" placeholder="Enter your Password" required
                                class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">Login</button>
                        <?php if ($login_is_trade) : ?>
                            <a href="<?php echo esc_url(wp_lostpassword_url($redirect_url)); ?>" class="btn btn-link">Forgot your password?</a>
                        <?php endif; ?>
                        <div class="text-center">
                            <?php if ($login_is_trade && function_exists('wholesale_trade_apply_url')) : ?>
                                <a href="<?php echo esc_url(wholesale_trade_apply_url()); ?>" class="btn btn-link">Apply for a trade account</a>
                            <?php else : ?>
                                <a href="<?php echo site_url().'/signup'; ?>" class="btn btn-link">Create an Account</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer();