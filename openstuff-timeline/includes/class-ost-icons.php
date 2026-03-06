<?php
/**
 * Lucide Icons - SVG markup for PHP templates
 * אייקוני Lucide כ-SVG inline
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to output Lucide icon SVG.
 *
 * @param string $name Icon name: pencil, plus, calendar, eye, tag, heart.
 * @param int    $size Size in pixels.
 * @return string SVG markup.
 */
function ost_lucide_icon( $name, $size = 18 ) {
	$icons = array(
		'pencil'   => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
		'plus'     => '<path d="M5 12h14"/><path d="M12 5v14"/>',
		'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
		'eye'      => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
		'tag'      => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
		'heart'    => '<path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	$size = (int) $size;
	return sprintf(
		'<svg class="ost-lucide-icon" xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%2$s</svg>',
		$size,
		$icons[ $name ]
	);
}

/**
 * Data URI for Lucide calendar icon - לשימוש בתפריט אדמין.
 *
 * @return string
 */
function ost_lucide_menu_icon_data_uri() {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>';
	return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}
