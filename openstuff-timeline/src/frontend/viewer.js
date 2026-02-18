/**
 * OpenStuff Timeline - Public viewer script
 * טוען את תצוגת הציר בעמוד הציבורי
 */
import { createRoot } from '@wordpress/element';
import TimelineViewer from './TimelineViewer';
import './viewer.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	const roots = document.querySelectorAll( '.ost-timeline-viewer-root' );
	roots.forEach( ( el ) => {
		const id = parseInt( el.dataset.timelineId, 10 );
		if ( id ) {
			const root = createRoot( el );
			root.render( <TimelineViewer timelineId={ id } /> );
		}
	} );
} );

