<?php


class Cart
{

    public $cart_items;
    public $sub_total = 0;
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
                    $this->sub_total = floatval($this->sub_total) + floatval($item->product_subtotal);
                }
            }
        }
    }

    public function getTitleByValue($product_id, $match = null) {
        $product_attr_json = get_post_meta($product_id, 'product_attr', true);
        $product_attr_array = json_decode((string) $product_attr_json, true);

        if (!is_array($product_attr_array)) {
            return $match;
        }

        foreach ($product_attr_array as $product_attr) {
            if (!isset($product_attr['options']) || !is_array($product_attr['options'])) {
                continue;
            }

            foreach ($product_attr['options'] as $single_option) {
                if (!is_array($single_option)) {
                    if (trim((string) $single_option) == trim((string) $match)) {
                        return $single_option;
                    }
                    continue;
                }

                foreach ($single_option as $title => $value) {
                    if (trim((string) $value) == trim((string) $match)) {
                        return $title;
                    }
                }
            }
        }

        return $match;
    }

    /**
     * Only accept design attachments that were uploaded in this visitor's session.
     */
    public static function is_session_design($design_id)
    {
        $design_id = absint($design_id);
        return $design_id > 0
            && isset($_SESSION['wholesale_design_uploads'])
            && is_array($_SESSION['wholesale_design_uploads'])
            && in_array($design_id, array_map('absint', $_SESSION['wholesale_design_uploads']), true);
    }

    private function redirect_with_error($product_id, $message)
    {
        $url = $product_id ? get_permalink($product_id) : home_url('/');
        wp_safe_redirect(add_query_arg(array('type' => 'danger', 'message' => rawurlencode($message)), $url));
        exit;
    }

    public function add_item()
    {
        $request = map_deep(wp_unslash($_REQUEST), 'sanitize_text_field');

        $product_id = isset($request['product_id']) ? absint($request['product_id']) : 0;
        if (!$product_id || 'product' !== get_post_type($product_id) || 'publish' !== get_post_status($product_id)) {
            $this->redirect_with_error(0, 'This product is no longer available.');
        }

        $design_id = isset($request['design_id']) && self::is_session_design($request['design_id']) ? absint($request['design_id']) : '';
        $product_title = get_the_title($product_id);
        $thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'single-post-thumbnail');
        $product_thumbnail = $thumbnail_src ? $thumbnail_src[0] : '';
        // The price is calculated on the server from the product settings; the browser's
        // total_cost is only used to tell the customer if the price they saw was out of date.
        $design = wholesale_product_is_channel_letter($product_id) ? wholesale_session_cl_design($product_id) : null;
        $quote = wholesale_price_quote($product_id, $request, $design);
        if (!$quote['ok']) {
            $this->redirect_with_error($product_id, $quote['error']);
        }
        $product_quantity = $quote['quantity'];
        $turnaround_cost = $quote['turnaround'];
        $shown_price = isset($request['total_cost']) ? floatval($request['total_cost']) : 0;
        if ($shown_price > 0 && abs($shown_price - ($quote['total'] - $turnaround_cost)) > 0.01) {
            $_SESSION['wholesale_cart_notice'] = sprintf('The price for %s was updated to $%s based on the options you chose.', get_the_title($product_id), number_format($quote['total'], 2));
        }
        $job_name = isset($request['job_name']) ? $request['job_name'] : '';
        // chennel letter details

        $face_color = isset($request['face']) ? $request['face'] : '';
        $return_color = isset($request['return']) ? $request['return'] : '';
        $return_size = isset($request['return-size']) ? $request['return-size'] : '';
        $trimcap_color  = isset($request['trimcap']) ? $request['trimcap'] : ''; 
        $default_trimcap_color  = isset($request['default_trimcap_color']) ? $request['default_trimcap_color'] : '';
        $is_same_return_color  = isset($request['is_same_return_color']) ? $request['is_same_return_color'] : ''; 
        $turnaround_option  = isset($request['turnaround_option']) ? $request['turnaround_option'] : ''; 
        $shipping_type  = isset($request['shipping_type']) ? $request['shipping_type'] : ''; 

        $raceway = isset($request['raceway']) ? $request['raceway'] : '';
        $font = isset($request['font']) ? $request['font'] : '';
        $letters = isset($request['letters']) ? $request['letters'] : '';
        $height = isset($request['height']) ? $request['height'] : '';
        $size = isset($request['size']) ? $request['size'] : '';
        $frame_color = isset($request['frame-color']) ? $request['frame-color'] : '';
        $grommets = isset($request['grommets']) ? $request['grommets'] : '';
        $grommet = isset($request['grommet']) ? $request['grommet'] : '';
        $rope = isset($request['rope']) ? $request['rope'] : '';
        $corners = isset($request['corners']) ? $request['corners'] : '';
        $windslit = isset($request['windslit']) ? $request['windslit'] : '';
        $velcro = isset($request['velcro']) ? $request['velcro'] : '';
        $of_sides = isset($request['of-side']) ? $request['of-side'] : '';
        $pole_pocket = isset($request['pole-pocket']) ? $request['pole-pocket'] : '';
        $stand_off = isset($request['stand-off']) ? $request['stand-off'] : '';

        $reinforced_strip = isset($request['reinforced-strip']) ? $request['reinforced-strip'] : '';
        $sandbag = isset($request['sandbag']) ? $request['sandbag'] : '';
        $full_wall = isset($request['full-wall']) ? $request['full-wall'] : '';
        $half_wall = isset($request['half-wall']) ? $request['half-wall'] : '';
        $hardware = isset($request['hardware']) ? $request['hardware'] : '';
        $hanger = isset($request['hanger']) ? $request['hanger'] : '';
        $finishing = isset($request['finishing']) ? $request['finishing'] : '';
        $bracket = isset($request['bracket']) ? $request['bracket'] : '';

        $led_ligths = isset($request['led-lights']) ? $request['led-lights'] : '';
        $led_light = isset($request['led-light']) ? $request['led-light'] : '';
        $base = isset($request['base']) ? $request['base'] : '';
        $carry_bag = isset($request['carry-bag']) ? $request['carry-bag'] : '';
        $hem = isset($request['hem']) ? $request['hem'] : '';
        $webbing = isset($request['webbing']) ? $request['webbing'] : '';


        $acrylic = isset($request['acrylic']) ? $request['acrylic'] : '';
        $edge_option = isset($request['edge-option']) ? $request['edge-option'] : '';
        $display_option = isset($request['display-option']) ? $request['display-option'] : '';
        $hardware_size = isset($request['hardware-size']) ? $request['hardware-size'] : '';
        $pennant_flag = isset($request['pennant-flag']) ? $request['pennant-flag'] : '';
        $holes_punch = isset($request['holes-punch']) ? $request['holes-punch'] : '';
        $corner_style = isset($request['corner-style']) ? $request['corner-style'] : '';
        $backside = isset($request['backside']) ? $request['backside'] : '';
        $table_height = isset($request['table-height']) ? $request['table-height'] : '';
        $table_diameter = isset($request['table-diameter']) ? $request['table-diameter'] : '';
        $size_and_color = isset($request['size-and-color']) ? $request['size-and-color'] : '';
        $rider = isset($request['rider']) ? $request['rider'] : '';
        $graphic = isset($request['graphic']) ? $request['graphic'] : '';
        $fsaso = isset($request['flag-shape-and-size-option']) ? $request['flag-shape-and-size-option'] : '';
        $graphic = isset($request['graphic']) ? $request['graphic'] : '';
        $flag_holder = isset($request['flag-holder']) ? $request['flag-holder'] : '';
        $width = '';

        $artwork_id = null;
        $attachment_src = null;

        if(isset($_FILES['custom-artwork']) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['custom-artwork']['error']) {
            $file = $_FILES['custom-artwork'];
            if (UPLOAD_ERR_OK !== (int) $file['error'] || !is_uploaded_file($file['tmp_name'])) {
                $this->redirect_with_error($product_id, 'Your artwork could not be uploaded. Please try again.');
            }

            $allowed_artwork_types = array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
            );
            $checked_type = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_artwork_types);
            if (empty($checked_type['ext']) || empty($checked_type['type'])) {
                $this->redirect_with_error($product_id, 'Artwork must be a JPG, PNG, GIF, WebP or PDF file.');
            }
            if (filesize($file['tmp_name']) > 25 * 1024 * 1024) {
                $this->redirect_with_error($product_id, 'Artwork files must be under 25MB.');
            }

            $file_ext = $checked_type['ext'];
            $file_name = 'custom-artwork-'.$product_id.'-'.uniqid();
            $upload_artwork = wp_upload_bits($file_name.'.'.$file_ext, null, file_get_contents($file['tmp_name']));
            if (!empty($upload_artwork['error'])) {
                $this->redirect_with_error($product_id, 'Your artwork could not be saved. Please try again.');
            }

            $file_path = $upload_artwork['file'];
            $file_url = $upload_artwork['url'];

            $attachment = array(
                'guid'           => $file_url,
                'post_mime_type' => $checked_type['type'],
                'post_title'     => $file_name,
                'post_content'   => '',
                'post_status'    => 'inherit',
            );

            $artwork_id = wp_insert_attachment($attachment, $file_path, 0);

            if ($artwork_id && !is_wp_error($artwork_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($artwork_id, $file_path);
                wp_update_attachment_metadata($artwork_id, $attach_data);
                $attachment_src = wp_get_attachment_url($artwork_id);
            } else {
                $artwork_id = null;
            }
        }

        if(trim($return_color) == 'Same as Face color') {
            $return_color = $face_color;
        }

        if (strlen($letters) > 0) {
            $height = $this->getTitleByValue($product_id,isset($request['height']) ? $request['height'] : '');
        } else {

            $height_ft = isset($request['height-ft']) ? floatval($request['height-ft']) : '0';
            $width_ft = isset($request['width-ft']) ? floatval($request['width-ft']) : '0';
            $height_in = isset($request['height-in']) ? floatval($request['height-in']) : '0';
            $width_in = isset($request['width-in']) ? floatval($request['width-in']) : '0';
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


        $material = isset($request['material']) ? $request['material'] : '';
        $print = isset($request['print']) ? $request['print'] : '';
        $lamination = isset($request['lamination']) ? $request['lamination'] : '';


        $power_suply = null;
        $lit = null;
        $cable = null;
        $design_url = null;
        $edit_cl_product_data = '{}';


        $product_cl_data_array = !empty($_SESSION['design_data_' . $product_id])
            ? json_decode(stripslashes($_SESSION['design_data_' . $product_id]), true)
            : null;

        if (is_array($product_cl_data_array)) {
            $extras = isset($product_cl_data_array['extras']) && is_array($product_cl_data_array['extras']) ? $product_cl_data_array['extras'] : array();

            if (!empty($extras['powerSupply']['value'])) {
                $power_suply = sanitize_text_field($extras['powerSupply']['value']);
            }
            if (!empty($extras['lit']['value'])) {
                $lit = $extras['lit']['value'] == 'Back Lit' ? 'Front and Back LIt' : sanitize_text_field($extras['lit']['value']);
            }
            if (!empty($extras['cable']['value'])) {
                $cable = sanitize_text_field($extras['cable']['value']);
            }
            if (!empty($product_cl_data_array['design_url'])) {
                $design_url = esc_url_raw($product_cl_data_array['design_url']);
            }
            if (!$design_id && !empty($product_cl_data_array['design_id']) && self::is_session_design($product_cl_data_array['design_id'])) {
                $design_id = absint($product_cl_data_array['design_id']);
            }

            if (!empty($product_cl_data_array['contentDimenstion']['height'])) {
                $height =  round(floatval($product_cl_data_array['contentDimenstion']['height']),1).' Inches';
            }
            if (!empty($product_cl_data_array['contentDimenstion']['width'])) {
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



        if ($attachment_src) {
            $attachment_src = esc_url_raw($attachment_src);
        }



        $color_profile = isset($request['color-profile']) ? $request['color-profile'] : '';
        $cart_items_json = json_encode(array(
            'product_id' => $product_id,
            'product_title' => $product_title,
            'product_subtotal' => $quote['total'],
            'unit_price' => $quote['unit_price'],
            'discount' => $quote['discount'],
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
                'Face color' => $this->getTitleByValue($product_id,$face_color),
                'Return color' => $this->getTitleByValue($product_id,$return_color),
                'Trimcap color' => $this->getTitleByValue($product_id,$trimcap_color),
                'Height' => $height,
                'Width' => $width,
                'Font' => $this->getTitleByValue($product_id,$font),
                'Raceway' => $this->getTitleByValue($product_id,$raceway),
                'Return size' => $this->getTitleByValue($product_id,$return_size),
                'Letters' => $letters,
                'Material' => $this->getTitleByValue($product_id,$material),
                'Print' => $this->getTitleByValue($product_id,$print),
                'Lamination' => $this->getTitleByValue($product_id,$lamination),
                'Color Profile' => $this->getTitleByValue($product_id,$color_profile),
                'Size' => $this->getTitleByValue($product_id,$size),
                'Acrylic' => $this->getTitleByValue($product_id,$acrylic),
                'Stand Off' => $this->getTitleByValue($product_id,$stand_off),
                'Edge Option' => $this->getTitleByValue($product_id,$edge_option),
                'Frame Color' => $this->getTitleByValue($product_id,$frame_color),
                'Grommets' => $this->getTitleByValue($product_id,$grommets),
                'Grommet' => $this->getTitleByValue($product_id,$grommet),
                'LED Lights' => $this->getTitleByValue($product_id,$led_ligths),
                'LED Light' => $this->getTitleByValue($product_id,$led_light),
                'Base' => $this->getTitleByValue($product_id,$base),
                'Carry Bag' => $this->getTitleByValue($product_id,$carry_bag),
                'Hem' => $this->getTitleByValue($product_id,$hem),
                'Webbing' => $this->getTitleByValue($product_id,$webbing),
                'Of Sides' => $this->getTitleByValue($product_id,$of_sides),
                'Pole Pocket' => $this->getTitleByValue($product_id,$pole_pocket),
                'Velcro' => $this->getTitleByValue($product_id,$velcro),
                'Windslit' => $this->getTitleByValue($product_id,$windslit),
                'Corners' => $this->getTitleByValue($product_id,$corners),
                'Rope' => $this->getTitleByValue($product_id,$rope),
                'Reinforced Strip' => $this->getTitleByValue($product_id,$reinforced_strip),
                'Sandbag' => $this->getTitleByValue($product_id,$sandbag),
                'Full Wall' => $this->getTitleByValue($product_id,$full_wall),
                'Half Wall' => $this->getTitleByValue($product_id,$half_wall),
                'Hardware' => $this->getTitleByValue($product_id,$hardware),
                'Hanger' => $this->getTitleByValue($product_id,$hanger),
                'Finishing' => $this->getTitleByValue($product_id,$finishing),
                'Bracket' => $this->getTitleByValue($product_id,$bracket),
                'Display Option' => $this->getTitleByValue($product_id,$display_option),
                'Hardware Size' => $this->getTitleByValue($product_id,$hardware_size),
                'Rider' => $this->getTitleByValue($product_id,$rider),
                'Pennant Flag' => $this->getTitleByValue($product_id,$pennant_flag),
                'Holes Punch' => $this->getTitleByValue($product_id,$holes_punch),
                'Corner Style' => $this->getTitleByValue($product_id,$corner_style),
                'Backside' => $this->getTitleByValue($product_id,$backside),
                'Table Height' => $this->getTitleByValue($product_id,$table_height),
                'Table Diameter' => $this->getTitleByValue($product_id,$table_diameter),
                'Size and Color' => $this->getTitleByValue($product_id,$size_and_color),
                'Graphic' => $this->getTitleByValue($product_id,$graphic),
                'Flag Shape and Size Option' => $this->getTitleByValue($product_id,$fsaso),
                'Flag Holder' => $this->getTitleByValue($product_id,$flag_holder),
                'Design Url' => $design_url,
                'My Artwork' => $attachment_src,
                
            )
        ));

        $cart_items = $this->get_items();
        array_push($cart_items, json_decode($cart_items_json));
        $_SESSION['cart_items'] = json_encode($cart_items);
        wp_safe_redirect(get_permalink());
        exit;
    }

    public function update_quantity($cart_id, $quantity)
    {
        $quantity = max(1, absint($quantity));
        $cart_items = $this->get_items();
        $updated_cart_items = array();
        foreach ($cart_items as $key => $item) {
            if ($item->cart_id == $cart_id) {
                $current_quanitity = max(1, absint($item->product_quantity));
                $prodcut_subtotal = floatval($item->product_subtotal);
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
        return is_array($this->cart_items) ? $this->cart_items : array();
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
        if (function_exists('wholesale_sync_cart_cookie')) {
            wholesale_sync_cart_cookie();
        }
    }
}

$cart  = new Cart();
