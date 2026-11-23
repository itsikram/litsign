<?php
/**
 * Checkout shipping methods, matching what trade print suppliers offer sign
 * shops:
 *
 * - A UPS and a FedEx service for every speed: Ground, 3 Day, 2 Day, Next
 *   Day, Saturday Delivery and Next Day Early AM.
 * - Blind Drop Ship: the customer's business is the return address on the
 *   label and nothing in the box names us or shows prices, so a sign shop can
 *   ship straight to its own client.
 * - Store Pickup at our shop, free.
 *
 * Rates are flat per speed, set in Settings > Storefront Sign > Checkout
 * defaults: the Ground, 3 Day, 2 Day and Next Day rate lists (channel letters
 * have their own) plus a surcharge for Saturday and for early-morning delivery.
 * UPS and FedEx charge the same rate; the customer picks the carrier they
 * prefer. Live carrier rating would need UPS and FedEx API accounts.
 *
 * Orders keep their delivery details in the shipping_address meta:
 *   delivery_method   'ship' or 'pickup'
 *   shipping_carrier  'ups' or 'fedex' (shipped orders)
 *   shipping_service  e.g. 'UPS 2nd Day Air'
 *   pickup_location   address text (pickup orders)
 *   blind_ship        'yes' for blind drop ship, with blind_from_* return address
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Carriers checkout can offer. */
function wholesale_shipping_carriers()
{
	return array('ups' => 'UPS', 'fedex' => 'FedEx');
}

/** Carriers switched on in Settings ('both', 'ups' or 'fedex'). */
function wholesale_enabled_shipping_carriers()
{
	$setting = (string) wholesale_get_setting('shipping_carriers');
	$carriers = wholesale_shipping_carriers();
	return isset($carriers[$setting]) ? array($setting => $carriers[$setting]) : $carriers;
}

function wholesale_sanitize_shipping_carriers($value)
{
	$value = sanitize_key((string) $value);
	return in_array($value, array('both', 'ups', 'fedex'), true) ? $value : 'both';
}

/** Each carrier's name for a speed. */
function wholesale_shipping_service_names()
{
	return array(
		'ups' => array(
			'ground' => 'UPS Ground',
			'3day' => 'UPS 3 Day Select',
			'2day' => 'UPS 2nd Day Air',
			'nextday' => 'UPS Next Day Air',
			'saturday_2day' => 'UPS 2nd Day Air (Saturday Delivery)',
			'saturday_nextday' => 'UPS Next Day Air (Saturday Delivery)',
			'early' => 'UPS Next Day Air Early',
		),
		'fedex' => array(
			'ground' => 'FedEx Ground',
			'3day' => 'FedEx Express Saver',
			'2day' => 'FedEx 2Day',
			'nextday' => 'FedEx Standard Overnight',
			'saturday_2day' => 'FedEx 2Day (Saturday Delivery)',
			'saturday_nextday' => 'FedEx Priority Overnight (Saturday Delivery)',
			'early' => 'FedEx First Overnight',
		),
	);
}

/**
 * The speeds offered for an order shipping on $ship_date, slowest first.
 * `rate` is the position in the Settings rate list the speed is priced from,
 * `fee` an extra surcharge setting. Saturday Delivery only exists when it
 * really lands on a Saturday: 2 Day from a Thursday ship date, Next Day from a
 * Friday one.
 */
function wholesale_shipping_speeds(DateTime $ship_date)
{
	$speeds = array(
		'ground' => array('group' => 'Ground', 'rate' => 0, 'days' => 6, 'note' => '3–6 business days in transit'),
		'3day' => array('group' => '3 Day', 'rate' => 1, 'days' => 3, 'note' => '3 business days in transit'),
		'2day' => array('group' => '2 Day', 'rate' => 2, 'days' => 2, 'note' => '2 business days in transit'),
		'nextday' => array('group' => 'Next Day', 'rate' => 3, 'days' => 1, 'note' => 'Next business day'),
	);

	$weekday = (int) $ship_date->format('N');
	if (4 === $weekday || 5 === $weekday) {
		$speeds['saturday'] = array(
			'group' => 'Saturday Delivery',
			'rate' => 4 === $weekday ? 2 : 3,
			'fee' => 'saturday_delivery_fee',
			'service' => 4 === $weekday ? 'saturday_2day' : 'saturday_nextday',
			'saturday' => true,
			'note' => 'Delivered on Saturday',
		);
	}

	$speeds['early'] = array('group' => 'Next Day Early AM', 'rate' => 3, 'days' => 1, 'fee' => 'early_am_fee', 'note' => 'Next business day, early morning');

	return $speeds;
}

/**
 * Every shipping method checkout offers for the cart, keyed by id
 * ("ups_ground", "fedex_2day", ...), slowest first, UPS before FedEx.
 *
 * Each method: id, carrier, carrier_label, speed, group, label, amount (what
 * is charged), regular (before free shipping), free, delivery ("Fri Nov. 13")
 * and note.
 */
function wholesale_cart_shipping_methods($cart)
{
	$rates = wholesale_cart_shipping_options($cart);
	$free = wholesale_cart_qualifies_free_shipping($cart);
	$ship_date = wholesale_estimated_ship_datetime($cart);
	$names = wholesale_shipping_service_names();
	$methods = array();

	foreach (wholesale_shipping_speeds($ship_date) as $speed => $info) {
		if (!isset($rates[$info['rate']])) {
			continue;
		}
		$regular = (float) $rates[$info['rate']];
		if (!empty($info['fee'])) {
			$fee = (float) wholesale_get_setting($info['fee']);
			if ($fee <= 0) {
				continue; // A surcharge of 0 switches the speed off.
			}
			$regular += $fee;
		}
		$regular = round($regular, 2);
		$is_free = 'ground' === $speed && $free;

		if (!empty($info['saturday'])) {
			$delivery = clone $ship_date;
			$delivery->modify('next saturday');
		} else {
			$delivery = wholesale_add_business_days($ship_date, $info['days']);
		}

		foreach (wholesale_enabled_shipping_carriers() as $carrier => $carrier_label) {
			$service = $info['service'] ?? $speed;
			$id = $carrier . '_' . $speed;
			$methods[$id] = array(
				'id' => $id,
				'carrier' => $carrier,
				'carrier_label' => $carrier_label,
				'speed' => $speed,
				'group' => $info['group'],
				'label' => $names[$carrier][$service] ?? ($carrier_label . ' ' . $info['group']),
				'amount' => $is_free ? 0.0 : $regular,
				'regular' => $regular,
				'free' => $is_free,
				'delivery' => $delivery->format('D M. j'),
				'note' => $info['note'],
			);
		}
	}

	return $methods;
}

/** The method checkout selects first: the cheapest (Ground). */
function wholesale_default_shipping_method($methods)
{
	return $methods ? (string) array_key_first($methods) : '';
}

// ------------------------------------------------------------------
// Store pickup
// ------------------------------------------------------------------

function wholesale_pickup_enabled()
{
	return wholesale_setting_enabled('pickup_enabled') && '' !== trim((string) wholesale_get_setting('pickup_address'));
}

/** Pickup address and hours, or null when pickup is off. */
function wholesale_pickup_location()
{
	if (!wholesale_pickup_enabled()) {
		return null;
	}
	$address = trim((string) wholesale_get_setting('pickup_address'));
	// "2601 NE 12th St, Renton, WA 98056" -> "Renton, WA".
	$parts = array_map('trim', explode(',', $address));
	$city = count($parts) >= 3 ? $parts[count($parts) - 2] . ', ' . preg_replace('/\s+\d{5}(-\d{4})?$/', '', $parts[count($parts) - 1]) : $address;
	return array(
		'address' => $address,
		'city' => $city,
		'hours' => trim((string) wholesale_get_setting('pickup_hours')),
		'map' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address),
	);
}

/** "Ready for pickup" date: the day production finishes. */
function wholesale_pickup_ready_date($cart)
{
	return wholesale_estimated_ship_datetime($cart)->format('D M. j');
}

// ------------------------------------------------------------------
// Blind drop ship
// ------------------------------------------------------------------

/** Return-address fields for Blind Drop Ship, with whether each is required. */
function wholesale_blind_ship_fields()
{
	return array(
		'company' => array('label' => 'Business name', 'required' => true),
		'name' => array('label' => 'Contact name', 'required' => false),
		'tel' => array('label' => 'Phone', 'required' => false),
		'address' => array('label' => 'Street address', 'required' => true),
		'address_2' => array('label' => 'Apt, suite or unit', 'required' => false),
		'city' => array('label' => 'City', 'required' => true),
		'state' => array('label' => 'State', 'required' => true),
		'zip' => array('label' => 'ZIP code', 'required' => true),
	);
}

/** The return address a signed-in customer used last time, keyed like wholesale_blind_ship_fields(). */
function wholesale_saved_blind_ship_from($user_id = null)
{
	$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
	$saved = $user_id ? get_user_meta($user_id, '_blind_ship_from', true) : array();
	return is_array($saved) ? $saved : array();
}

// ------------------------------------------------------------------
// Orders
// ------------------------------------------------------------------

function wholesale_order_is_pickup($shipping)
{
	return is_array($shipping) && 'pickup' === ($shipping['delivery_method'] ?? '');
}

function wholesale_order_is_blind_ship($shipping)
{
	return is_array($shipping) && 'yes' === ($shipping['blind_ship'] ?? '');
}

/** Blind ship return address as lines (unescaped). */
function wholesale_order_blind_from_lines($shipping)
{
	if (!wholesale_order_is_blind_ship($shipping)) {
		return array();
	}
	$get = static function ($key) use ($shipping) {
		return trim((string) ($shipping['blind_from_' . $key] ?? ''));
	};
	return array_values(array_filter(array(
		$get('company'),
		$get('name'),
		trim($get('address') . ($get('address_2') ? ', ' . $get('address_2') : '')),
		trim($get('city') . ', ' . $get('state') . ' ' . $get('zip'), ', '),
		$get('tel'),
	)));
}

/**
 * One plain-text line describing how an order is delivered, for staff
 * emails and lists: "Store pickup (2601 NE 12th St, Renton, WA 98056)",
 * "UPS Ground - BLIND DROP SHIP from Acme Signs, 1 Main St, Kent, WA 98032".
 */
function wholesale_order_delivery_summary($shipping, $cost = array())
{
	if (wholesale_order_is_pickup($shipping)) {
		return 'Store pickup' . (!empty($shipping['pickup_location']) ? ' (' . $shipping['pickup_location'] . ')' : '');
	}
	$summary = (string) ($shipping['shipping_service'] ?? ($cost['shipping_method'] ?? ''));
	if (wholesale_order_is_blind_ship($shipping)) {
		$from = wholesale_order_blind_from_lines($shipping);
		$summary .= ($summary ? ' - ' : '') . 'BLIND DROP SHIP from ' . implode(', ', $from);
	}
	return $summary;
}
