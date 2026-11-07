<?php
/**
 * Customer project case studies (2026-11-05), rendered by wholesale_render_case_studies()
 * in inc/buying-journey.php. An entry shows on the site ONLY when:
 *   'published' => true, 'permission' => true (written OK from the customer to show
 *   their business, photos and quote), and at least one real photo attachment ID.
 * Never use concept renderings or AI images as 'photos'. A concept image may go in
 * 'concept_image' and is labelled "Concept rendering" on the page.
 *
 * All five slots below are empty and unpublished: they wait for real projects.
 * Owner checklist: conversion-plan/case-study-checklist.md (site root).
 *
 * Fields:
 *  business_type  e.g. 'Dental office'          location    city/state the customer agrees to show
 *  requirement    what the customer needed       style       e.g. 'Front lit channel letters (Type 1)'
 *  size           e.g. '12 ft x 24 in, 18 in letters'          materials  faces, returns, colors
 *  scope          what we made and shipped       install     who installed (customer's installer, not us)
 *  photos_day     attachment IDs (daytime)       photos_night attachment IDs (night)
 *  quote          customer's own words, approved review_url  link to the public review, if any
 *  products       product slugs ordered          concept_image optional attachment ID (labelled as concept)
 *
 * @package litsign
 */

$case_study_blank = array(
	'published' => false,
	'permission' => false,
	'business_type' => '',
	'location' => '',
	'requirement' => '',
	'style' => '',
	'size' => '',
	'materials' => '',
	'scope' => '',
	'install' => '',
	'photos_day' => array(),
	'photos_night' => array(),
	'quote' => '',
	'quote_name' => '',
	'review_url' => '',
	'products' => array(),
	'concept_image' => 0,
);

return array(
	// Suggested mix: salon, restaurant, dental office, retail store, trade show/event.
	'case-1' => $case_study_blank,
	'case-2' => $case_study_blank,
	'case-3' => $case_study_blank,
	'case-4' => $case_study_blank,
	'case-5' => $case_study_blank,
);
