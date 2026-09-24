<?php
// template name: Account

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

$account_redirect = static function ($type, $message) {
    wp_safe_redirect(add_query_arg(array('type' => $type, 'message' => rawurlencode($message)), get_permalink()));
    exit;
};

if (isset($_GET['logout'])) {
    check_admin_referer('wholesale_account_logout');
    wp_logout();
    wp_safe_redirect(home_url('/'));
    exit;
}


$user_data = get_userdata(get_current_user_id());
$user_id = $user_data->ID;

if ('POST' === $_SERVER['REQUEST_METHOD'] && (isset($_POST['account_details']) || isset($_POST['user_address']) || isset($_POST['change_password']))) {
    check_admin_referer('wholesale_account_update', 'wholesale_account_nonce');
}

if (isset($_POST['account_details'])) {
    $fname = isset($_POST['fName']) ? sanitize_text_field(wp_unslash($_POST['fName'])) : '';
    $lname = isset($_POST['lName']) ? sanitize_text_field(wp_unslash($_POST['lName'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $website = isset($_POST['website']) ? esc_url_raw(wp_unslash($_POST['website'])) : '';
    $telephone = isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : '';

    if (!is_email($email)) {
        $account_redirect('danger', 'Please enter a valid email address.');
    }

    $email_owner = email_exists($email);
    if ($email_owner && (int) $email_owner !== (int) $user_id) {
        $account_redirect('danger', 'That email address is already used by another account.');
    }

    $updated = wp_update_user(
        array(
            "ID" => $user_id,
            "first_name" => $fname,
            "last_name" => $lname,
            "user_email" => $email,
            "meta_input" => array(
                "telephone" => $telephone,
                "website" => $website
            )
        )
    );

    if (is_wp_error($updated)) {
        $account_redirect('danger', $updated->get_error_message());
    }

    $account_redirect('success', 'Account Details Updated Successfully');
}

if (isset($_POST['user_address'])) {
    foreach (array('city', 'state', 'zip', 'street_one', 'street_two') as $address_field) {
        $address_value = isset($_POST[$address_field]) ? sanitize_text_field(wp_unslash($_POST[$address_field])) : '';
        update_user_meta($user_id, $address_field, $address_value);
    }

    $account_redirect('success', 'Address Updated Successfully');
}


if (isset($_POST['change_password'])) {
    // Passwords are used verbatim; sanitizing would silently change them.
    $current_passwod = isset($_POST['current_password']) ? (string) wp_unslash($_POST['current_password']) : '';
    $new_password = isset($_POST['new_password']) ? (string) wp_unslash($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? (string) wp_unslash($_POST['confirm_password']) : '';

    if (!wp_check_password($current_passwod, $user_data->user_pass, $user_id)) {
        $account_redirect('danger', 'Your password is incorrect');
    }
    if ($new_password !== $confirm_password) {
        $account_redirect('danger', 'Your New Password and Confirm Password is not same');
    }
    if (strlen($new_password) < 8) {
        $account_redirect('danger', 'Your new password must be at least 8 characters.');
    }

    wp_set_password($new_password, $user_id);
    wp_set_auth_cookie($user_id, true);
    $account_redirect('success', 'Passwords updated successfully');
}

// define variable for user data

$user_first_name = get_user_meta($user_id, 'first_name', true);
$user_last_name = get_user_meta($user_id, 'last_name', true);
$user_website = get_user_meta($user_id, 'website', true);
$suer_telephone = get_user_meta($user_id, 'telephone', true);



$user_city = get_user_meta($user_id, 'city', true);
$user_state = get_user_meta($user_id, 'state', true);
$user_zip = get_user_meta($user_id, 'zip', true);
$user_street_one = get_user_meta($user_id, 'street_one', true);
$user_street_two = get_user_meta($user_id, 'street_two', true);
$user_country = "United States";





get_header();

$account_notice_type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
$account_notice_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

?>

<div class="container py-4 account-page-wrap">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 account-topbar">
                <div>
                    <p class="mb-1 account-greeting">Hi, <?php echo esc_html($user_data->data->user_nicename); ?></p>
                    <h2 class="mb-0 account-page-title">My Account</h2>
                </div>
            </div>

            <?php if (!empty($account_notice_message)) : ?>
                <div class="alert alert-<?php echo esc_attr($account_notice_type === 'danger' ? 'danger' : 'success'); ?> account-alert" role="alert">
                    <?php echo esc_html($account_notice_message); ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm account-shell">
                <div class="card-header account-header">
                    Account Overview
                </div>
                <div class="card-body account-body">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="nav flex-column nav-pills account-tabs" style="gap: 10px" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#v-pills-account" type="button" role="tab" aria-controls="v-pills-account" aria-selected="true">Account Details</button>
                                <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#v-pills-address" type="button" role="tab" aria-controls="v-pills-address" aria-selected="false">Address</button>
                                <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab" aria-controls="v-pills-password" aria-selected="false">Password</button>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('logout', 'true', get_permalink()), 'wholesale_account_logout')); ?>" class="nav-link text-start account-logout" id="v-pills-settings-tab" role="tab" aria-selected="false">Logout</a>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="tab-content account-tab-content" id="v-pills-tab">
                                <!-- Account Details  -->
                                <div class="tab-pane fade show active" id="v-pills-account" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                    <div class="card card-body">
                                        <h3>Account Details</h3>
                                        <form method="POST">
                                            <input type="hidden" name="account_details" value="1">
                                            <?php wp_nonce_field('wholesale_account_update', 'wholesale_account_nonce'); ?>

                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">First
                                                            name</label>
                                                        <input type="text" value="<?php echo esc_attr($user_first_name); ?>" placeholder="First name" name="fName" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Last
                                                            name</label>
                                                        <input type="text" value="<?php echo esc_attr($user_last_name); ?>" placeholder="Last name" name="lName" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="" class="form-label mb-0">Username</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <div class="input-group-text">
                                                                    @
                                                                </div>
                                                            </div>
                                                            <input disabled type="text" value="<?php echo esc_attr($user_data->data->user_login); ?>" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Email</label>
                                                        <input type="text" value="<?php echo esc_attr($user_data->data->user_email); ?>" placeholder="Email" name="email" class="form-control">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="" class="form-label mb-0">Phone Number</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <div class="input-group-text">
                                                                    +1
                                                                </div>
                                                            </div>
                                                            <input type="tel" name="telephone" value="<?php echo esc_attr($suer_telephone); ?>" class="telephone form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Website</label>
                                                        <input type="text" value="<?php echo esc_attr($user_website); ?>" placeholder="https://your-website.com" name="website" class="form-control">
                                                    </div>
                                                </div>
                                            </div>



                                            <button type="submit" class="btn btn-primary my-2">Save Changes</button>


                                        </form>
                                    </div>
                                </div>

                                <!-- User Address  -->
                                <div class="tab-pane fade" id="v-pills-address" role="tabpanel">
                                    <div class="card card-body">
                                        <h3>Address</h3>
                                        <form method="POST">
                                            <input type="hidden" name="user_address" value="1">
                                            <?php wp_nonce_field('wholesale_account_update', 'wholesale_account_nonce'); ?>
                                            <div class="row mb-2">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">City</label>
                                                        <input type="text" value="<?php echo esc_attr($user_city); ?>" placeholder="New York" name="city" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">State</label>
                                                        <input type="text" value="<?php echo esc_attr($user_state); ?>" placeholder="" name="state" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Zip Code</label>
                                                        <input type="text" value="<?php echo esc_attr($user_zip); ?>" placeholder="" name="zip" class="form-control">
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Street 1</label>
                                                        <input type="text" value="<?php echo esc_attr($user_street_one); ?>" name="street_one" class="form-control">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Street 2</label>
                                                        <input type="text" value="<?php echo esc_attr($user_street_two); ?>" name="street_two" class="form-control">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">country</label>
                                                        <input type="text" disabled value="<?php echo esc_attr($user_country); ?>" name="country" class="form-control">
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary my-2">Save Changes</button>

                                        </form>
                                    </div>
                                </div>

                                <!-- change password  -->
                                <div class="tab-pane fade" id="v-pills-password" role="tabpanel">
                                    <div class="card card-body">
                                        <h3>Change Password</h3>
                                        <form method="POST">
                                            <input type="hidden" name="change_password" value="1">
                                            <?php wp_nonce_field('wholesale_account_update', 'wholesale_account_nonce'); ?>
                                            <div class="row mb-2">
                                                <div class="form-group">
                                                    <label for="input-fName" class="form-label mb-0">Current Password</label>
                                                    <input type="password" name="current_password" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group">
                                                    <label for="input-fName" class="form-label mb-0">New Password</label>
                                                    <input type="password" placeholder="" name="new_password" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group">
                                                    <label for="input-fName" class="form-label mb-0">Confirm Password</label>
                                                    <input type="password" placeholder="" name="confirm_password" class="form-control">
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary my-2">Save Changes</button>

                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .account-page-wrap {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .account-topbar {
        margin-bottom: 1.25rem;
    }

    .account-greeting {
        color: #1fa8de;
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
    }

    .account-page-title {
        color: #122033;
        font-weight: 700;
        letter-spacing: -0.03em;
    }

    .account-alert {
        border-radius: 12px;
        margin-bottom: 1.2rem;
        border: none;
    }

    .account-shell {
        border-radius: 22px !important;
        overflow: hidden;
        border: 1px solid rgba(31, 168, 222, 0.15) !important;
        box-shadow: 0 18px 45px rgba(18, 32, 51, 0.08) !important;
    }

    .account-header {
        padding: 1rem 1.4rem;
        background: linear-gradient(135deg, #f3fbff 0%, #eaf8ff 100%);
        border-bottom: 1px solid rgba(31, 168, 222, 0.15);
        color: #0f2f42;
        font-weight: 700;
    }

    .account-body {
        background: #ffffff;
        padding: 1.25rem;
    }

    .account-tabs {
        background: linear-gradient(180deg, #f6fbff 0%, #eef8ff 100%);
        border: 1px solid rgba(31, 168, 222, 0.15);
        border-radius: 20px;
        padding: 0.75rem;
        gap: 10px;
        min-height: 100%;
    }

    .account-tabs .nav-link {
        color: #1fa8de;
        border: 1px solid rgba(31, 168, 222, 0.2);
        background-color: transparent;
        border-radius: 12px;
        padding: 0.8rem 0.9rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .account-tabs .nav-link:hover,
    .account-tabs .nav-link:focus,
    .account-tabs .nav-link.active {
        color: #fff;
        background-color: #1fa8de;
        border-color: #1fa8de;
        box-shadow: 0 10px 18px rgba(31, 168, 222, 0.18);
    }

    .account-logout {
        color: #d93a3a !important;
        border-color: rgba(217, 58, 58, 0.2) !important;
    }

    .account-logout:hover,
    .account-logout:focus {
        background-color: #d93a3a !important;
        color: #fff !important;
        border-color: #d93a3a !important;
    }

    .account-tab-content {
        width: 100%;
    }

    .account-tab-content .card {
        border: 1px solid #edf5fb;
        border-radius: 18px;
        box-shadow: none;
        background: #fff;
        padding: 1.5rem;
    }

    .account-tab-content h3 {
        margin-bottom: 1.2rem;
        color: #132638;
        font-size: 1.7rem;
        font-weight: 700;
    }

    .account-tab-content .form-group {
        margin-bottom: 1rem;
    }

    .account-tab-content .form-label {
        color: #2c435d;
        font-weight: 600;
        margin-bottom: 0.45rem;
    }

    .account-tab-content .form-control,
    .account-tab-content .input-group-text {
        border-radius: 12px;
        border: 1px solid #dfeaf5;
        min-height: 46px;
    }

    .account-tab-content .form-control {
        background: #fbfdff;
        color: #18314c;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .account-tab-content .form-control:focus {
        border-color: rgba(31, 168, 222, 0.5);
        box-shadow: 0 0 0 0.2rem rgba(31, 168, 222, 0.12);
        background: #fff;
    }

    .account-tab-content .input-group-text {
        background: #edf9ff;
        color: #1fa8de;
        font-weight: 700;
    }

    .account-tab-content .btn-primary {
        background: #1fa8de;
        border-color: #1fa8de;
        border-radius: 999px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        box-shadow: 0 12px 22px rgba(31, 168, 222, 0.24);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .account-tab-content .btn-primary:hover,
    .account-tab-content .btn-primary:focus,
    .account-tab-content .btn-primary:active {
        background: #148ec3;
        border-color: #148ec3;
        box-shadow: 0 16px 28px rgba(31, 168, 222, 0.28);
        transform: translateY(-1px);
    }

    @media (max-width: 767px) {
        .account-body {
            padding: 1rem;
        }

        .account-shell {
            border-radius: 18px !important;
        }

        .account-tab-content .card {
            padding: 1rem;
        }

        .account-tab-content h3 {
            font-size: 1.4rem;
        }
    }
</style>

<?php get_footer();
