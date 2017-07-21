<?php
/**
 * Novalnet checkout form.
 *
 * @author  Novalnet
 * @package Novalnet-gateway/Templates
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	exit; // Exit if accessed directly.
endif;

echo wp_kses(
	$contents, array(
		'p' => array(
			'style' => true,
		),
		'font' => array(
			'color' => true,
		 ),
		'b' => array(),
	)
);
