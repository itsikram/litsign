<?php
// template name: login


if (is_user_logged_in()) {
    wp_redirect(site_url());
}

if (isset($_REQUEST['email'])) {
    $email = $_REQUEST['email'];
    $pass = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
    $redirect_url = isset($_REQUEST['redirect_ulr']) ? $_REQUEST['redirect_ulr'] : home_url();


    $is_login = wp_signon(
        array(
            'user_login' => $email,
            'user_password' => $pass,
            'remember' => true,
        )
    );



    if(is_user_logged_in()) {
        
        wp_redirect($redirect_url);

    }else {
        wp_redirect(get_permalink() . '?type=danger&message="Invalid Credential"&redirect_ulr='.$redirect_url);

    }
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