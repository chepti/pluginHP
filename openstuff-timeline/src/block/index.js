/**
 * OpenStuff Timeline - Admin Editor Block
 * ציר זמן שנתי - עורך
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import TimelineEditor from './TimelineEditor';
import './editor.scss';

registerBlockType( 'ost/timeline-editor', {
	edit: function Edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( {
			className: 'ost-timeline-editor-block',
			dir: 'rtl',
		} );

		const { postId, postType } = useSelect( ( select ) => {
			const editor = select( 'core/editor' );
			return {
				postId: editor?.getCurrentPostId?.() ?? 0,
				postType: editor?.getCurrentPostType?.() ?? '',
			};
		}, [] );

		// כשעורכים ציר ישיר (os_timeline) - השתמש ב-ID של הפוסט הנוכחי
		const effectiveTimelineId = attributes.timelineId || ( postType === 'os_timeline' ? postId : 0 );

		return (
			<div { ...blockProps }>
				<TimelineEditor
					timelineId={ effectiveTimelineId }
					onTimelineChange={ ( id ) => setAttributes( { timelineId: id } ) }
				/>
			</div>
		);
	},

	save: function Save() {
		return null;
	},
} );
