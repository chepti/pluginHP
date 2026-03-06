<?php
/**
 * Lucide Icons - SVG markup for Homer Patuach BP Tweaks
 * אייקוני Lucide כ-SVG inline
 *
 * @package Homer_Patuach_BP_Tweaks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to output Lucide icon SVG.
 *
 * @param string $name Icon name: user, bell, check, trash2, loader.
 * @param int    $size Size in pixels.
 * @param string $stroke Stroke color (default currentColor; use #ffffff for light on dark).
 * @return string SVG markup.
 */
function hpg_lucide_icon( $name, $size = 24, $stroke = 'currentColor' ) {
	$icons = array(
		'user'   => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		'bell'   => '<path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>',
		'check'  => '<path d="M20 6 9 17l-5-5"/>',
		'trash2' => '<path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
		'loader' => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>',
		'sprout' => '<path d="M14 9.536V7a4 4 0 0 1 4-4h1.5a.5.5 0 0 1 .5.5V5a4 4 0 0 1-4 4 4 4 0 0 0-4 4c0 2 1 3 1 5a5 5 0 0 1-1 3"/><path d="M4 9a5 5 0 0 1 8 4 5 5 0 0 1-8-4"/><path d="M5 21h14"/>',
		'star'   => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
		'message-circle' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
		'message-square' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
		'crown'  => '<path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/>',
		'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
		'heart'   => '<path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/>',
		'tag'    => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
		'pencil' => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	$size = (int) $size;
	return sprintf(
		'<svg class="hpg-lucide-icon" xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="%3$s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%2$s</svg>',
		$size,
		$icons[ $name ],
		esc_attr( $stroke )
	);
}
