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
        wp_safe_redirect($redirect_url);
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

?>


<div class="container py-5">
    <div class="row">
        <div class="col-md-6 offset-md-3 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="fs-3">Login</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php wp_nonce_field('wholesale_login', 'wholesale_login_nonce'); ?>
                        <input type="hidden" name="redirect_ulr" value="<?php echo esc_attr($redirect_url); ?>">
                        <div class="form-group mb-2">
                            <label for="" class="form-label">Email</label>
                            <input type="text" required name="email" placeholder="Enter your email or username"
                                class="form-control">
                        </div>
                        <div class="form-group mb-2">
                            <label for="" class="form-label">Password</label>
                            <input type="password" name="password" placeholder="Enter your Password" required
                                class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">Login</button>
                        <div class="text-center">
                            <a href="<?php echo site_url().'/signup'; ?>" class="btn btn-link">Create an Account</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer();