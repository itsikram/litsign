<?php


class Cart
{

    public $cart_items;
    public $sub_total;
    public $have_items = false;
    public function __construct()
    {

        if (isset($_SESSION['cart_items'])) {
            $cart_session = str_replace("\\", '', $_SESSION['cart_items']);
            if (is_array(json_decode($cart_session))) {
                $this->have_items = count(json_decode($cart_session)) == 0 ? false : true;
            }

            if ($this->have_items) {
                $this->cart_items = json_decode($cart_session) ? json_decode($cart_session) : [];

                foreach ($this->cart_items as $item) {
                    $this->sub_total = $this->sub_total + $item->product_subtotal;
                }
            }
        }
    }

    public function getTitleByValue($product_id, $match = null) {
        $product_attr_json = get_post_meta($product_id, 'product_attr', true);
        $product_attr_array = json_decode($product_attr_json,true);

        foreach($product_attr_array as $product_attr){
            echo '<pre>';
            foreach($product_attr['options'] as $single_option){

                foreach($single_option as $title => $value) {

                    if(trim($value) == trim($match)) {
                        return $title;
                    }

                }

            }
        }

    }

    public function add_item()
    {
        $product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : '';
        $design_id = isset($_REQUEST['design_id']) ? $_REQUEST['design_id'] : '';
        $product_title = get_the_title($product_id);
        $product_thumbnail = $product_id ? wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'single-post-thumbnail')[0] : '';
        $product_price = isset($_REQUEST['total_cost']) ? floatval($_REQUEST['total_cost']) : '';
        $turnaround_cost = isset($_REQUEST['$turnaround_cost']) ? floatval($_REQUEST['$turnaround_cost']) : 0;
        $product_quantity = isset($_REQUEST['product_quantity']) ? $_REQUEST['product_quantity'] : 1;
        $job_name = isset($_REQUEST['job_name']) ? $_REQUEST['job_name'] : '';
        // chennel letter details

        $face_color = isset($_REQUEST['face']) ? $_REQUEST['face'] : '';
        $return_color = isset($_REQUEST['return']) ? $_REQUEST['return'] : '';
        $return_size = isset($_REQUEST['return-size']) ? $_REQUEST['return-size'] : '';
        $trimcap_color  = isset($_REQUEST['trimcap']) ? $_REQUEST['trimcap'] : ''; 
        $default_trimcap_color  = isset($_REQUEST['default_trimcap_color']) ? $_REQUEST['default_trimcap_color'] : '';
        $is_same_return_color  = isset($_REQUEST['is_same_return_color']) ? $_REQUEST['is_same_return_color'] : ''; 
        $turnaround_option  = isset($_REQUEST['turnaround_option']) ? $_REQUEST['turnaround_option'] : ''; 
        $shipping_type  = isset($_REQUEST['shipping_type']) ? $_REQUEST['shipping_type'] : ''; 

        $raceway = isset($_REQUEST['raceway']) ? $_REQUEST['raceway'] : '';
        $font = isset($_REQUEST['font']) ? $_REQUEST['font'] : '';
        $letters = isset($_REQUEST['letters']) ? $_REQUEST['letters'] : '';
        $height = isset($_REQUEST['height']) ? $_REQUEST['height'] : '';
        $size = isset($_REQUEST['size']) ? $_REQUEST['size'] : '';
        $frame_color = isset($_REQUEST['frame-color']) ? $_REQUEST['frame-color'] : '';
        $grommets = isset($_REQUEST['grommets']) ? $_REQUEST['grommets'] : '';
        $grommet = isset($_REQUEST['grommet']) ? $_REQUEST['grommet'] : '';
        $rope = isset($_REQUEST['rope']) ? $_REQUEST['rope'] : '';
        $corners = isset($_REQUEST['corners']) ? $_REQUEST['corners'] : '';
        $windslit = isset($_REQUEST['windslit']) ? $_REQUEST['windslit'] : '';
        $velcro = isset($_REQUEST['velcro']) ? $_REQUEST['velcro'] : '';
        $of_sides = isset($_REQUEST['of-side']) ? $_REQUEST['of-side'] : '';
        $pole_pocket = isset($_REQUEST['pole-pocket']) ? $_REQUEST['pole-pocket'] : '';
        $stand_off = isset($_REQUEST['stand-off']) ? $_REQUEST['stand-off'] : '';

        $reinforced_strip = isset($_REQUEST['reinforced-strip']) ? $_REQUEST['reinforced-strip'] : '';
        $sandbag = isset($_REQUEST['sandbag']) ? $_REQUEST['sandbag'] : '';
        $full_wall = isset($_REQUEST['full-wall']) ? $_REQUEST['full-wall'] : '';
        $half_wall = isset($_REQUEST['half-wall']) ? $_REQUEST['half-wall'] : '';
        $hardware = isset($_REQUEST['hardware']) ? $_REQUEST['hardware'] : '';
        $hanger = isset($_REQUEST['hanger']) ? $_REQUEST['hanger'] : '';
        $finishing = isset($_REQUEST['finishing']) ? $_REQUEST['finishing'] : '';
        $bracket = isset($_REQUEST['bracket']) ? $_REQUEST['bracket'] : '';

        $led_ligths = isset($_REQUEST['led-lights']) ? $_REQUEST['led-lights'] : '';
        $led_light = isset($_REQUEST['led-light']) ? $_REQUEST['led-light'] : '';
        $base = isset($_REQUEST['base']) ? $_REQUEST['base'] : '';
        $carry_bag = isset($_REQUEST['carry-bag']) ? $_REQUEST['carry-bag'] : '';
        $hem = isset($_REQUEST['hem']) ? $_REQUEST['hem'] : '';
        $webbing = isset($_REQUEST['webbing']) ? $_REQUEST['webbing'] : '';


        $acrylic = isset($_REQUEST['acrylic']) ? $_REQUEST['acrylic'] : '';
        $edge_option = isset($_REQUEST['edge-option']) ? $_REQUEST['edge-option'] : '';
        $display_option = isset($_REQUEST['display-option']) ? $_REQUEST['display-option'] : '';
        $hardware_size = isset($_REQUEST['hardware-size']) ? $_REQUEST['hardware-size'] : '';
        $pennant_flag = isset($_REQUEST['pennant-flag']) ? $_REQUEST['pennant-flag'] : '';
        $holes_punch = isset($_REQUEST['holes-punch']) ? $_REQUEST['holes-punch'] : '';
        $corner_style = isset($_REQUEST['corner-style']) ? $_REQUEST['corner-style'] : '';
        $backside = isset($_REQUEST['backside']) ? $_REQUEST['backside'] : '';
        $table_height = isset($_REQUEST['table-height']) ? $_REQUEST['table-height'] : '';
        $table_diameter = isset($_REQUEST['table-diameter']) ? $_REQUEST['table-diameter'] : '';
        $size_and_color = isset($_REQUEST['size-and-color']) ? $_REQUEST['size-and-color'] : '';
        $rider = isset($_REQUEST['rider']) ? $_REQUEST['rider'] : '';
        $graphic = isset($_REQUEST['graphic']) ? $_REQUEST['graphic'] : '';
        $fsaso = isset($_REQUEST['flag-shape-and-size-option']) ? $_REQUEST['flag-shape-and-size-option'] : '';
        $graphic = isset($_REQUEST['graphic']) ? $_REQUEST['graphic'] : '';
        $flag_holder = isset($_REQUEST['flag-holder']) ? $_REQUEST['flag-holder'] : '';
        $width = '';

        $artwork_id = null;
        $attachment_src = null;

        if(isset($_FILES['custom-artwork'])) {
            $file = $_FILES['custom-artwork'];
            $file_type = $_FILES['custom-artwork']['type'];
            $file_ext = explode('/', $file_type)[1];
            $file_name = 'custom-artwork-'.$product_id.'-'.uniqid();
            if($file['error'] != 4) {
                $upload_artwork = wp_upload_bits($file_name.'.'.$file_ext, null, file_get_contents($file['tmp_name']));
                if (!$upload_artwork['error']) {
                    $file_path = $upload_artwork['file'];
                    $file_url = $upload_artwork['url'];
        
                    // Insert the attachment
                    $attchment_filetype = wp_check_filetype(basename($file_path), null);
                    $attachment = array(
                        'guid'           => $file_url, 
                        'post_mime_type' => $attchment_filetype['type'],
                        'post_title'     => $file_name,
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    );
                    
                    // Optional: Associate with a specific post or custom post type
                    $post_id = 123; // Replace with the ID of the post you want to attach the file to
                    
                    $artwork_id = wp_insert_attachment($attachment, $file_path, $post_id);
                    
                    // Generate and update attachment metadata
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($artwork_id, $file_path);
                    wp_update_attachment_metadata($artwork_id, $attach_data);
        
                    if($artwork_id) {
                        $attachment_src = wp_get_attachment_url($artwork_id);
                    }
                }
            }
            

        }




        if(trim($return_color) == 'Same as Face color') {
            $return_color = $face_color;
        }

        function getTitleByValue($product_id, $match = null) {
            $product_attr_json = get_post_meta($product_id, 'product_attr', true);
            $product_attr_array = json_decode($product_attr_json,true);

            foreach($product_attr_array as $product_attr){
                foreach($product_attr['options'] as $single_option){
                    if(!is_array($single_option)) {
                        if(trim($single_option) == trim($match)) {
                            return $single_option;
                        }
                    }

                    foreach($single_option as $title => $value) {

                        if(trim($value) == trim($match)) {
                            return $title;
                        }
                    }

                }
            }

            return $match;
    
        }

        if (strlen($letters) > 0) {
            $height = getTitleByValue($product_id,isset($_REQUEST['height']) ? $_REQUEST['height'] : '');
        } else {

            $height_ft = isset($_REQUEST['height-ft']) ? floatval($_REQUEST['height-ft']) : '0';
            $width_ft = isset($_REQUEST['width-ft']) ? floatval($_REQUEST['width-ft']) : '0';
            $height_in = isset($_REQUEST['height-in']) ? floatval($_REQUEST['height-in']) : '0';
            $width_in = isset($_REQUEST['width-in']) ? floatval($_REQUEST['width-in']) : '0';
            $height = "";
            $width = "";

			$height_ft_text = "$height_ft Foot ";
			$width_ft_text = "$width_ft Foot ";

			$height_in_text = "$height_in Inch";
			$width_in_text = "$width_in Inch ";

			


			if($height_ft > 1) {

				$height_ft_text = "$height_ft Foots ";

				$height = $height_ft_text;
				if($height_in > 1) {
					$height_in_text = "$height_in Inchs";
					$height = $height_ft_text.$height_in_text;

				}else {
					$height_in_text = '';
					$height = $height_ft_text.$height_in_text;

				}
			}else {
				$height = "";
			}



			if($width_ft > 1) {
				$width_ft_text = "$width_ft Foots ";
				$width = $width_ft_text;
				
				if($width_in > 1) {
					$width_in_text = "$width_in Inches";
                    $width = $width_ft_text.$width_in_text;
                }else {
					$width_in_text = "";
					
				}
					
			}else {
				$width = "";
			}

        }


        $material = isset($_REQUEST['material']) ? $_REQUEST['material'] : '';
        $print = isset($_REQUEST['print']) ? $_REQUEST['print'] : '';
        $lamination = isset($_REQUEST['lamination']) ? $_REQUEST['lamination'] : '';


        $power_suply = null;
        $lit = null;
        $cable = null;
        $design_url = null;
        $edit_cl_product_data = '{}';


        if (isset($_SESSION['design_data_' . $product_id])) {
            $edit_cl_product_data = stripslashes($_SESSION['design_data_' . $product_id]);
            $product_cl_data_array = json_decode($edit_cl_product_data, true);

            if($product_cl_data_array['extras']['powerSupply']) {
                $power_suply = $product_cl_data_array['extras']['powerSupply']['value'];
            }
            if($product_cl_data_array['extras']['lit']) {
                $lit = $product_cl_data_array['extras']['lit']['value'] == 'Back Lit' ? 'Front and Back LIt' : $product_cl_data_array['extras']['lit']['value'];
            }
            if($product_cl_data_array['extras']['cable']) {
                $cable = $product_cl_data_array['extras']['cable']['value'];
            }
            if($product_cl_data_array['design_url']) {
                $design_url = $product_cl_data_array['design_url'];
                if(strlen($design_url) > 5) {
                    $design_url =  "<a href='".$design_url."' target='_blank'>".'Open'."</a>";
                }
            }

            if($product_cl_data_array['contentDimenstion']['height']) {
                $height =  round(floatval($product_cl_data_array['contentDimenstion']['height']),1).' Inches';
            }
            if($product_cl_data_array['contentDimenstion']['width']) {
                $width = round(floatval($product_cl_data_array['contentDimenstion']['width']),1).' Inches';
            }


    
        }

		if($shipping_type == 'store_pickup') {
			$shipping_type = 'Store Pickup';
		}elseif($shipping_type == 'blind_drop') {
			$shipping_type  = 'Blind Drop';
		}


		if($turnaround_option == 'same_day') {
			$turnaround_option = 'Same Day';
		}elseif($turnaround_option == 'next_day') {
			$turnaround_option = 'Next Day';
		}



        if(strlen($attachment_src) > 5) {
            $attachment_src = "<a href='".$attachment_src."' target='_blank'>Open</a>";
        }



        $color_profile = isset($_REQUEST['color-profile']) ? $_REQUEST['color-profile'] : '';
        $cart_items_json = json_encode(array(
            'product_id' => $product_id,
            'product_title' => $product_title,
            'product_subtotal' => $product_price + $turnaround_cost,
            'product_quantity' => $product_quantity,
            'product_thumbnail' => $product_thumbnail,
			'turnaround_cost' => $turnaround_cost,
            'job_name' => $job_name,
            'cart_id' => uniqid(),
            'design_id' => $design_id,
            'artwork_id' => $artwork_id,
            'product_details' => array(
                'Product Id' => $product_id,
                'Turnaround Option' => $turnaround_option,
                'Shipping Type' => $shipping_type,
                'Power Supply' => $power_suply,
                'Lit' => $lit,
                'Cable' => $cable,
                'Face color' => getTitleByValue($product_id,$face_color),
                'Return color' => getTitleByValue($product_id,$return_color),
                'Trimcap color' => getTitleByValue($product_id,$trimcap_color),
                'Height' => $height,
                'Width' => $width,
                'Font' => getTitleByValue($product_id,$font),
                'Raceway' => getTitleByValue($product_id,$raceway),
                'Return size' => getTitleByValue($product_id,$return_size),
                'Letters' => $letters,
                'Material' => getTitleByValue($product_id,$material),
                'Print' => getTitleByValue($product_id,$print),
                'Lamination' => getTitleByValue($product_id,$lamination),
                'Color Profile' => getTitleByValue($product_id,$color_profile),
                'Size' => getTitleByValue($product_id,$size),
                'Acrylic' => getTitleByValue($product_id,$acrylic),
                'Stand Off' => getTitleByValue($product_id,$stand_off),
                'Edge Option' => getTitleByValue($product_id,$edge_option),
                'Frame Color' => getTitleByValue($product_id,$frame_color),
                'Grommets' =>  getTitleByValue($product_id,$grommets),
                'Grommet' =>  getTitleByValue($product_id,$grommet),
                'LED Lights' =>  getTitleByValue($product_id,$led_ligths),
                'LED Light' =>  getTitleByValue($product_id,$led_light),
                'Base' =>  getTitleByValue($product_id,$base),
                'Carry Bag' =>  getTitleByValue($product_id,$carry_bag),
                'Hem' =>  getTitleByValue($product_id,$hem),
                'Webbing' =>  getTitleByValue($product_id,$webbing),
                'Of Sides' =>  getTitleByValue($product_id,$of_sides),
                'Pole Pocket' =>  getTitleByValue($product_id,$pole_pocket),
                'Velcro' =>  getTitleByValue($product_id,$velcro),
                'Windslit' =>  getTitleByValue($product_id,$windslit),
                'Corners' =>  getTitleByValue($product_id,$corners),
                'Rope' =>  getTitleByValue($product_id,$rope),
                'Reinforced Strip' =>  getTitleByValue($product_id,$reinforced_strip),
                'Sandbag' =>  getTitleByValue($product_id,$sandbag),
                'Full Wall' =>  getTitleByValue($product_id,$full_wall),
                'Half Wall' =>  getTitleByValue($product_id,$half_wall),
                'Hardware' =>  getTitleByValue($product_id,$hardware),
                'Hanger' =>  getTitleByValue($product_id,$hanger),
                'Finishing' =>  getTitleByValue($product_id,$finishing),
                'Bracket' =>  getTitleByValue($product_id,$bracket),
                'Display Option' =>  getTitleByValue($product_id,$display_option),
                'Hardware Size' =>  getTitleByValue($product_id,$hardware_size),
                'Rider' =>  getTitleByValue($product_id,$rider),
                'Pennant Flag' =>  getTitleByValue($product_id,$pennant_flag),
                'Holes Punch' =>  getTitleByValue($product_id,$holes_punch),
                'Corner Style' =>  getTitleByValue($product_id,$corner_style),
                'Backside' =>  getTitleByValue($product_id,$backside),
                'Table Height' =>  getTitleByValue($product_id,$table_height),
                'Table Diameter' =>  getTitleByValue($product_id,$table_diameter),
                'Size and Color' =>  getTitleByValue($product_id,$size_and_color),
                'Graphic' =>  getTitleByValue($product_id,$graphic),
                'Flag Shape and Size Option' =>  getTitleByValue($product_id,$fsaso),
                'Flag Holder' =>  getTitleByValue($product_id,$flag_holder),
                'Design Url' => $design_url,
                'My Artwork' => $attachment_src,
                
            )
        ));

        // echo '<pre>';
        // print_r(json_decode($cart_items_json));
        // exit();

        $cart_items = $this->get_items() ? $this->get_items() : array();

        // get cart items 
        if ($product_id == true) {
            array_push($cart_items, json_decode($cart_items_json));
            $_SESSION['cart_items'] = json_encode($cart_items);
           wp_redirect(get_permalink());
        }

    }

    public function update_quantity($cart_id, $quantity)
    {
        $cart_items = $this->get_items();
        $updated_cart_items = array();
        foreach ($cart_items as $key => $item) {
            if ($item->cart_id == $cart_id) {
                $current_quanitity = $item->product_quantity;
                $prodcut_subtotal = $item->product_subtotal;
                $single_product_cost = $prodcut_subtotal / $current_quanitity;
                $update_product_cost = $single_product_cost * $quantity;

                $updated_item = $cart_items[$key];
                $updated_item->product_subtotal = $update_product_cost;
                $updated_item->product_quantity = $quantity;
                $updated_cart_items[] = $updated_item;
            } else {
                $updated_cart_items[] = $item;
            }
        }

        $_SESSION['cart_items'] = json_encode($updated_cart_items);
    }


    public function get_items()
    {

        return $this->cart_items;
    }

    public function remove_item($id)
    {
        $cart_items = $this->get_items();
        $update_cart_items = array();
        foreach ($cart_items as $key => $item) {
            if ($item->cart_id == $id) {
                unset($cart_items[$key]);
            } else {
                $update_cart_items[] = $cart_items[$key];
            }
        }

        $_SESSION['cart_items'] = json_encode($update_cart_items);
    }

    public function empty() {
        $_SESSION['cart_items']  = json_encode(array());

    }
}

$cart  = new Cart();
