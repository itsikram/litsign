<?php
// template name: sign up


if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/'));
    exit;
}

$signup_error = static function ($message) {
    wp_safe_redirect(add_query_arg(array('type' => 'danger', 'message' => rawurlencode($message)), get_permalink()));
    exit;
};

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['email'])) {
    check_admin_referer('wholesale_signup', 'wholesale_signup_nonce');

    $email = sanitize_email(wp_unslash($_POST['email']));
    $username = isset($_POST['username']) ? sanitize_user(wp_unslash($_POST['username']), true) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? (string) wp_unslash($_POST['confirm_password']) : '';
    $fname = isset($_POST['fname']) ? sanitize_text_field(wp_unslash($_POST['fname'])) : '';
    $lname = isset($_POST['lname']) ? sanitize_text_field(wp_unslash($_POST['lname'])) : '';
    $website = isset($_POST['website']) ? esc_url_raw(wp_unslash($_POST['website'])) : '';
    $telephone = isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : '';

    if (!is_email($email)) {
        $signup_error('Please enter a valid email address.');
    }
    if ('' === $username) {
        $signup_error('Please choose a username using letters, numbers, spaces, or . - _ @');
    }
    if (email_exists($email)) {
        $signup_error('Email Already Exist');
    }
    if (username_exists($username)) {
        $signup_error('Username Already Exist');
    }
    if (strlen($password) < 8) {
        $signup_error('Your password must be at least 8 characters.');
    }
    if ($password !== $confirm_password) {
        $signup_error('Your Password and Confirm Password do not match.');
    }

    $new_user_id = wp_insert_user(
        array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $fname,
            'last_name' => $lname,
            'role' => 'subscriber',
            'meta_input' => array(
                'website' => $website,
                'telephone' => $telephone
            )
        )
    );

    if (is_wp_error($new_user_id)) {
        $signup_error($new_user_id->get_error_message());
    }

    wp_safe_redirect(home_url('/login/?type=success&message=' . rawurlencode('Account Created Successfully')));
    exit;
}



get_header();



?>


<div class="container py-5">
    <div class="row">
        <div class="col-md-6 offset-md-3 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="fs-3">Sign Up</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php wp_nonce_field('wholesale_signup', 'wholesale_signup_nonce'); ?>
                        <div class="form-group mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" required name="email" placeholder="Enter your Email" class="form-control">
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Username</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        @
                                    </div>
                                </div>
                                <input type="text" required name="username" placeholder="Enter your username" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group mb-2">
                                    <label class="form-label">First Name</label>

                                    <input type="text" name="fname" placeholder="John" required class="form-control">
                                </div>
                            </div>
                            <div class="col">

                                <div class="form-group mb-2">
                                    <label class="form-label">Last Name</label>

                                    <input type="text" name="lname" placeholder="Doe" required class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" required name="password" placeholder="Enter your Password" required class="form-control">
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="" class="form-label mb-0">Phone Number</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        +1
                                    </div>
                                </div>
                                <input type="tel" required name="telephone" class="form-control">
                            </div>
                        </div>


                        <div class="form-group mb-2">
                            <label class="form-label">Website</label>

                            <input type="text" name="website" placeholder="https://your-website.com" required class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">Sign Up</button>
                        <div class="text-center">
                            <a href="<?php echo site_url() . '/login'; ?>" class="btn btn-link">Already have an
                                Account</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer();
