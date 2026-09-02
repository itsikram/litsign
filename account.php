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






?>

<div class="container py-3">
    <div class="row">
        <div class="col">
            <p>Hi, <?php echo $user_data->data->user_nicename; ?></p>

            <div class="card">
                <div class="card-header">
                    My Account
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#v-pills-account" type="button" role="tab" aria-controls="v-pills-account" aria-selected="true">Account Details</button>
                                <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#v-pills-address" type="button" role="tab" aria-controls="v-pills-address" aria-selected="false">Address</button>
                                <button class="nav-link  text-start" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab" aria-controls="v-pills-password" aria-selected="false">Password</button>
                                <a href="<?php echo get_permalink() . '?logout=true' ?>" class="nav-link text-danger  text-start" id="v-pills-settings-tab" type="button" role="tab" aria-selected="false">Logout</a>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="tab-content w-md-75 w-100" id="v-pills-tab">

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
</div>



<?php get_footer();
