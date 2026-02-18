/**
 * OpenStuff Timeline Viewer - Block registration
 * בלוק תצוגת ציר זמן (עורך + פרונט)
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import TimelineViewer from './TimelineViewer';
import './editor.scss';
import './viewer.scss';

registerBlockType( 'ost/timeline-viewer', {
	edit: function Edit( { attributes, setAttributes } ) {
		const [ timelines, setTimelines ] = useState( [] );
		const blockProps = useBlockProps( { dir: 'rtl' } );

		useEffect( () => {
			apiFetch( { path: '/os-timeline/v1/timelines' } )
				.then( ( data ) => setTimelines( data || [] ) )
				.catch( () => setTimelines( [] ) );
		}, [] );

		const options = [
			{ value: 0, label: __( 'בחר ציר...', 'openstuff-timeline' ) },
			...timelines.map( ( t ) => ( { value: t.id, label: t.title } ) ),
		];

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'הגדרות ציר', 'openstuff-timeline' ) }>
						<SelectControl
							label={ __( 'ציר זמן', 'openstuff-timeline' ) }
							value={ attributes.timelineId || 0 }
							options={ options }
							onChange={ ( v ) => setAttributes( { timelineId: parseInt( v, 10 ) } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					{ attributes.timelineId ? (
						<TimelineViewer timelineId={ attributes.timelineId } fetchFn={ apiFetch } />
					) : (
						<div className="ost-viewer-placeholder">
							<p>{ __( 'בחר ציר זמן מהפאנל בצד', 'openstuff-timeline' ) }</p>
						</div>
					) }
				</div>
			</>
		);
	},

	save: function Save( { attributes } ) {
		return null;
	},
} );
