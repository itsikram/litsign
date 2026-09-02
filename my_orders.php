<?php
// Template Name: My Orders
get_header();

$user_id = 0;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
} else {
    wp_redirect(home_url());
}

?>

<div class="container mt-5">
    <h2 class="mb-4">Order Details</h2>

    <?php
                $args = array(
                    'post_type'      => 'order', // Custom post type
                    'meta_key'       => 'user_id', // Meta key
                    'meta_value'     => $user_id, // Meta value
                    'meta_compare'   => '=', // Comparison operator (optional, default is '=')
                    'posts_per_page' => -1, // Retrieve all matching posts, -1 for unlimited
                );

                $query = new WP_Query($args);

                if ($query->have_posts()) {?>

    <div class="responsive-table">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Order ID</th>
                    <th scope="col">Contact Number</th>
                    <th scope="col">Order Date</th>

                    <th scope="col">Estimated Delivery Date</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>

<?php
                    while ($query->have_posts()) {
                        $query->the_post();
                        $post_id = get_the_ID();
                        $order_id = get_post_meta($post_id,'order_id',true);
                        $order_status = get_post_status();
                        $order_time = get_post_meta($post_id,'order_time',true);
                        $estimate_delivery_time = get_post_meta($post_id,'estimate_delivery_time',true);

                        $contact_number = json_decode(get_post_meta($post_id,'billing_address',true),true)['billing_tel'];
                        
                ?>
                        <tr>
                            <td><a href="<?php echo get_permalink(); ?>">#<?php echo $order_id; ?></a></td>
                            <td><?php echo $contact_number; ?></td>
                            <td><?php echo $order_time; ?></td>
                            <td><?php echo $estimate_delivery_time; ?></td>
                            <td>

                                <?php switch($order_status){
                                    case 'on_hold':
                                            echo '<span class="badge bg-warning">On Hold</span>';
                                        break;
                                    case 'completed':
                                        echo '<span class="badge bg-success">Completed</span>';
                                        break;
                                    case 'processing':
                                        echo '<span class="badge bg-primary">Processing</span>';
                                        break;
                                    case 'failed':
                                        echo '<span class="badge bg-danger">Failed</span>';
                                        break;
                                }
                                ?>
                            
                            </td>
                        </tr>




                <?php
                    }
                    wp_reset_postdata(); // Restore global post data after custom query loop
                

                ?>

            </tbody>
        </table>
    </div>
    <?php 
    } else {
        echo '<p class="text-secondary">No Order Details Found</p>';
    }?>

</div>



<?php get_footer(); ?>