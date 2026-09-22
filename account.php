<?php
// template name: Account

if (!is_user_logged_in()) {
    wp_redirect(site_url().'/login');

}

if (isset($_REQUEST['logout'])) {
    wp_logout();
}


$user_data = get_userdata(get_current_user_id());
$user_id = $user_data->data->ID;

if (isset($_REQUEST['account_details'])) {
    $fname = isset($_REQUEST['fName']) ? sanitize_text_field($_REQUEST['fName']) : '';
    $lname = isset($_REQUEST['fName']) ? sanitize_text_field($_REQUEST['lName']) : '';
    $email = isset($_REQUEST['email']) ? sanitize_text_field($_REQUEST['email']) : '';
    $website = isset($_REQUEST['website']) ? sanitize_text_field($_REQUEST['website']) : '';
    $telephone = isset($_REQUEST['email']) ? sanitize_text_field($_REQUEST['telephone']) : '';


    try {
        wp_update_user(
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

        wp_redirect(get_permalink() . '?type=success&message=Account Details Updated Successfully');
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

if (isset($_REQUEST['user_address'])) {
    $city = $_REQUEST['city'] ? sanitize_text_field($_REQUEST['city']) : '';
    $state = $_REQUEST['state'] ? sanitize_text_field($_REQUEST['state']) : "";
    $zip = $_REQUEST['zip'] ? sanitize_text_field($_REQUEST['zip']) : '';
    $street_one = $_REQUEST['street_one'] ? sanitize_text_field($_REQUEST['street_one']) : '';
    $street_two = $_REQUEST['street_two'] ? sanitize_text_field($_REQUEST['street_two']) : '';

    try {

        update_user_meta($user_id, 'city', $city);
        update_user_meta($user_id, 'state', $state);
        update_user_meta($user_id, 'zip', $zip,);
        update_user_meta($user_id, 'street_one', $street_one);
        update_user_meta($user_id, 'street_two', $street_two);

        wp_redirect(get_permalink() . '?type=success&message=Address Updated Successfully');
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}


if (isset($_REQUEST['change_password'])) {
    $current_passwod = isset($_REQUEST['current_password']) ? sanitize_text_field($_REQUEST['current_password']) : '';
    $new_password = isset($_REQUEST['new_password']) ? sanitize_text_field($_REQUEST['new_password']) : "";
    $confirm_password = isset($_REQUEST['confirm_password']) ? sanitize_text_field($_REQUEST['confirm_password']) : "";


    if (wp_check_password($current_passwod, $user_data->data->user_pass, $user_id)) {

        if($new_password === $confirm_password){
            wp_update_user(array(
                'ID' => $user_id,
                'user_pass' => $new_password
            ));
    
            wp_redirect(get_permalink() . '?type=success&message=Passwords updated successfully');
        }else {
            wp_redirect(get_permalink() . '?type=danger&message=Your New Password and Confirm Password is not same');

        }


    }else {
        wp_redirect(get_permalink() . '?type=danger&message=Your password is incorrect');

    }
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
                                <a href="<?php echo esc_url(get_permalink() . '?logout=true'); ?>" class="nav-link text-start account-logout" id="v-pills-settings-tab" role="tab" aria-selected="false">Logout</a>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="tab-content account-tab-content" id="v-pills-tab">
                                <!-- Account Details  -->
                                <div class="tab-pane fade show active" id="v-pills-account" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                    <div class="card card-body">
                                        <h3>Account Details</h3>
                                        <form method="POST">
                                            <input type="hidden" name="account_details">

                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">First
                                                            name</label>
                                                        <input type="text" value="<?php echo $user_first_name; ?>" placeholder="First name" name="fName" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Last
                                                            name</label>
                                                        <input type="text" value="<?php echo $user_last_name; ?>" placeholder="Last name" name="lName" class="form-control">
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
                                                            <input disabled type="text" value="<?php echo $user_data->data->user_login; ?>" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Email</label>
                                                        <input type="text" value="<?php echo $user_data->data->user_email; ?>" placeholder="Email" name="email" class="form-control">
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
                                                            <input type="tel" name="telephone" value="<?php echo $suer_telephone; ?>" class="telephone form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Website</label>
                                                        <input type="text" value="<?php echo $user_website ?>" placeholder="https://your-website.com" name="website" class="form-control">
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
                                            <input type="hidden" name="user_address">
                                            <div class="row mb-2">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">City</label>
                                                        <input type="text" value="<?php echo $user_city; ?>" placeholder="New York" name="city" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">State</label>
                                                        <input type="text" value="<?php echo $user_state; ?>" placeholder="" name="state" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Zip Code</label>
                                                        <input type="text" value="<?php echo $user_zip; ?>" placeholder="" name="zip" class="form-control">
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Street 1</label>
                                                        <input type="text" value="<?php echo $user_street_one; ?>" name="street_one" class="form-control">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">Street 2</label>
                                                        <input type="text" value="<?php echo $user_street_two; ?>" name="street_two" class="form-control">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="input-fName" class="form-label mb-0">country</label>
                                                        <input type="text" disabled value="<?php echo $user_country; ?>" name="country" class="form-control">
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
                                            <input type="hidden" name="change_password">
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
