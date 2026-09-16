<?php

get_header();

$short_desc = get_post_meta(get_the_ID(), '_product_short_desc', true);

$product_metarial = get_post_meta(get_the_ID(), '_product_metarial', true);
$product_print = get_post_meta(get_the_ID(), '_product_print', true);
$product_lamination = get_post_meta(get_the_ID(), '_product_lamination', true);

$product_min_height = floatVal(get_post_meta(get_the_ID(), '_min_height',true));
$product_min_width = floatVal(get_post_meta(get_the_ID(), '_min_width',true));
$product_min_sqft = $product_min_height * $product_min_width;


$price_per_sqft = floatval(get_post_meta(get_the_ID(),'_price_per_sqft',true));

$product_min_price = $product_min_sqft * $price_per_sqft;

$product_attr_json = get_post_meta(get_the_ID(),'product_attr',true);
$product_attr_array = json_decode($product_attr_json);


?>

<div class="container product-details">
    <?php wholesale_breadcrumbs(); ?>
    <h2 class="fs-3 product-title my-3">
        <?php echo get_the_title(); ?>
    </h2>
    <div class="row">
        <div class="col-md-6">
            <div class="thumbnail-container">
                <?php
                the_post_thumbnail('', array('class' => 'single-product-thumbnail'));
                ?>
            </div>

            <div class="product-price-per-sqft text-end py-2 text-secondary">
                $<?php echo $price_per_sqft; ?> per ft<sup>2</sup>
            </div>
            <div class="prodcut-short-desc-container my-3">
                <?php echo $short_desc; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="product-attibute-box">
                <input type="hidden" name="price_per_sqft" value="<?php echo $price_per_sqft; ?>" id="pricePerSqft">
                <input type="hidden" name="total_cost" value="<?php echo $product_min_price; ?>" id="totalCost">
                <div class="row d-fex align-items-center">
                    <div class="col-3">
                        <label>Height</label>
                    </div>
                    <div class="col-9">
                        <div class="row">
                            <div class="col-6">
                                <input placeholder="Ft" value="<?php echo $product_min_height; ?>" name="height-ft" min="<?php echo $product_min_height; ?>" type="number" id="input-height-ft"
                                    class="form-control text-right">
                            </div>
                            <div class="col-6">
                                <input type="number" name="height-in" max="12" placeholder="In" id="input-height-in"  class="form-control text-right" />
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row d-fex align-items-center">
                    <div class="col-3">
                        <label>Width</label>
                    </div>
                    <div class="col-9 mt-2">
                        <div class="row">
                            <div class="col-6">
                                <input placeholder="Ft" min="<?php echo $product_min_width; ?>" value="<?php echo $product_min_width; ?>" name="width-ft" type="number" id="input-width-ft"
                                    class="form-control text-right">

                            </div>
                            <div class="col-6">
                                <input type="number" max="12" name="width-in" placeholder="In" id="input-width-in"
                                    class="form-control text-right">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-3">

                    </div>
                    <div class="col-9">
                    <span class="total-size-sqft"><?php echo $product_min_height * 12; ?>" x <?php echo $product_min_width * 12; ?>" = <?php echo $product_min_sqft; ?> ft<sup>2</sup></span>

                    </div>
                </div>
                
                <?php
                if($product_attr_array == true){
                foreach($product_attr_array as $single_attr){ ?>
                <div class="row d-fex align-items-center  mt-2">
                        <div class="col-md-3 col-4">
                            <label for="select-<?php echo $single_attr -> name; ?>" class="text-capitalize"><?php echo str_replace('-',' ', $single_attr -> name); ?></label>
                        </div>
                        <div class="col-8 col-md-9">
                            <div class="row">
                                <div class="col">
                                    <?php  if(count($single_attr -> options) !== 1){ ?>
                                    <select id="select-<?php echo $single_attr -> name; ?>" data-cCost="0" class="form-select dynamic-select"> 
                                        <?php foreach($single_attr -> options as $option){ 
                                            foreach($option as $name => $price){
                                            ?>
                                        <option  value="<?php echo $price; ?>"><?php echo $name; ?></option>
                                        <?php  }};?>
                                    </select>
                                    <?php }else {
                                        ?>

<?php foreach($single_attr -> options as $option){ 
                                            foreach($option as $name => $price){
                                            ?>
                                        <span><?php echo $name; ?></span>
                                        <?php  }};?>
<?php
                                    } ?>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php }}; ?>
                    

                <?php if ($product_metarial): ?>
                    <div class="row d-fex align-items-center  mt-4">
                        <div class="col-md-2 col-4">
                            <label>Material</label>
                        </div>
                        <div class="col-8 col-md-10">
                            <div class="row">
                                <div class="col">
                                    <span>
                                        <?php echo $product_metarial; ?>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif;
                if ($product_print): ?>

                    <div class="row d-fex align-items-center mt-2">
                        <div class="col-4 col-md-2">
                            <label>Print</label>
                        </div>
                        <div class="col-8 col-md-10">
                            <div class="row">
                                <div class="col">
                                    <span>
                                        <?php echo $product_print; ?>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif;

                if ($product_lamination): ?>

                    <div class="row d-fex align-items-center  mt-2">
                        <div class="col-4 col-md-2">
                            <label>Lamination</label>
                        </div>
                        <div class="col-8 col-md-10">
                            <div class="row">
                                <div class="col">
                                    <span>
                                        <?php echo $product_lamination; ?>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-pricing-box">
                <div class="container">
                    <div class="row subtotal-container">
                        <div class="col-6">
                            <span class="fs-6">Subtotal</span>
                        </div>
                        <div class="col-6 price-subtotal-container">
                            <span class="fs-6 d-block "><span>$</span><span class="price-subtotal"><?php echo $product_min_price; ?></span></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row total-container">
                        <div class="col-6">
                            <span class="fs-4">Total</span>
                        </div>
                        <div class="col-6 price-total-container">
                            <span class="fs-4 d-block "><span>$</span><span class="price-total"><?php echo $product_min_price; ?></span></span>
                        </div>
                    </div>
                    <?php if(is_user_logged_in()){ ?>
                    <div class="row">
                        <div class="col text-center">
                            <a href="#" id="payNowBtn" class="btn btn-success">Order Now</a>
                        </div>
                    </div>
                    <?php } else {
                        ?>
                    <div class="row">
                        <div class="col text-center">
                            <a href="<?php echo site_url().'/login'; ?>"  class="btn btn-primary">Login to order this product</a>
                        </div>
                    </div>
                        <?php 
                    }?>

                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col">
            <div class="product-main-desc">
                <?php echo get_the_content(); ?>
            </div>
        </div>
    </div>
</div>
<div class="container">

</div>


<script>
    (function($){
        $(document).ready(e => {
            

            // $('#payNowBtn').click(() => {
            //     alert('clicked')
            // })
        })
    })(jQuery)
</script>

<script>

const stripe = Stripe('<?php echo 'pk_test_51NWbhtBkPgGRflkhjqfJ4sVRfodWxqCeaTsgGhizrruZR5eNkZG4ebdGHesHTVdTFXxGSo6Mcp88HALuANAvZD2Z00GbRxzgbD'; ?>');

// Select payment button
const payBtn = document.getElementById("payNowBtn");

// Payment request handler
payBtn.addEventListener("click", function(evt) {

    evt.preventDefault();

    let isPayment = true;


    if (isPayment) {
        payBtn.innerText = 'Payment Procesing..'


        payBtn.setAttribute('disbled', 'true')
        createCheckoutSession().then(function(data) {
            if (data.sessionId) {
                stripe.redirectToCheckout({
                    sessionId: data.sessionId,
                }).then(handleResult);
            } else {
                handleResult(data);
            }
        });
    }

});

// Create a Checkout Session with the selected product
const createCheckoutSession = function(stripe) {



    return fetch("<?php echo site_url(); ?>/payment", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            createCheckoutSession: 1,
            cost: parseFloat(document.getElementById('totalCost').value),
            currency: 'USD',
            email: '',

        }),
    }).then(function(result) {
        //console.log(result.json())
        return result.json();
    })
};

// Handle any errors returned from Checkout
const handleResult = function(result) {
    console.log(result)
    if (result.error) {
        console.log(result.error)
    }
};
</script>
<?php

get_footer();

