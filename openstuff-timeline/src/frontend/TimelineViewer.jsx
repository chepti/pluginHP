/**
 * Timeline Viewer - תצוגת ציר זמן ציבורית
 * קו מצד ימין, עיגולים צבעוניים לנושאים, מצב הרחבה עם גרירה
 */
import { useState, useEffect, useLayoutEffect, useCallback } from '@wordpress/element';
import {
	DndContext,
	closestCenter,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	arrayMove,
	SortableContext,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
	Gamepad2,
	FileText,
	Settings,
	LayoutTemplate,
	Video,
	File,
	Tag,
	Eye,
	Heart,
} from 'lucide-react';

const CONTENT_ICONS = {
	game: Gamepad2,
	worksheet: FileText,
	presentation: Settings,
	template: LayoutTemplate,
	video: Video,
	default: File,
};

function ContentIcon( { type, size = 20 } ) {
	const Icon = CONTENT_ICONS[ type ] || CONTENT_ICONS.default;
	return <Icon size={ size } strokeWidth={ 2 } aria-hidden />;
}

function getFetchFn( fetchFn ) {
	if ( fetchFn && typeof fetchFn === 'function' ) {
		return ( path ) => fetchFn( { path } );
	}
	return ( path ) => {
		const base = ( typeof ostData !== 'undefined' && ostData?.restUrl ) ? ostData.restUrl : '';
		const url = base + path;
		const opts = { headers: {} };
		if ( typeof ostData !== 'undefined' && ostData?.nonce ) {
			opts.headers[ 'X-WP-Nonce' ] = ostData.nonce;
		}
		return fetch( url, opts ).then( ( r ) => r.json() );
	};
}

function postFetch( path, body ) {
	const base = ( typeof ostData !== 'undefined' && ostData?.restUrl ) ? ostData.restUrl : '';
	const url = base + path;
	const opts = {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify( body ),
	};
	if ( typeof ostData !== 'undefined' && ostData?.nonce ) {
		opts.headers[ 'X-WP-Nonce' ] = ostData.nonce;
	}
	return fetch( url, opts ).then( ( r ) => r.json() );
}

/* פלטת צבעים לעיגולי נושאים - פסטל */
const TOPIC_PALETTE = [
	'#F5D0A9', /* כתום/אפרסק */
	'#A8E6CF', /* מנטה/ירוק בהיר */
	'#F8B4C4', /* אלמוג/ורוד */
	'#B4D7F8', /* תכלת */
	'#D4B4F8', /* לבנדר */
];

function PinCard( {
	pin,
	expanded,
	canDrag,
	isDragging,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging: isSortableDragging,
	} = useSortable( {
		id: `pin-${ pin.id }`,
		disabled: ! canDrag,
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
	};

	const cardClass = `ost-pin-card ${ expanded ? 'ost-pin-card-expanded' : '' } ${ ( isDragging || isSortableDragging ) ? 'ost-pin-dragging' : '' }`;
	const content = canDrag ? (
		<div className={ cardClass }>
			<div className="ost-pin-thumb ost-pin-thumb-main">
				{ pin.thumbnail_url ? (
					<img src={ pin.thumbnail_url } alt="" />
				) : (
					<span className="ost-pin-icon"><ContentIcon type={ pin.content_type } size={ 24 } /></span>
				) }
			</div>
			<div className="ost-pin-body">
				<span className="ost-pin-title">{ pin.title }</span>
				{ expanded && ( pin.author_name || pin.tags?.length || pin.credit ) && (
					<div className="ost-pin-meta">
						{ pin.author_name && <span className="ost-pin-author">{ pin.author_name }</span> }
						{ pin.tags?.length > 0 && (
							<span className="ost-pin-tags">
								<Tag size={ 14 } strokeWidth={ 2 } aria-hidden />
								{ pin.tags.join( ', ' ) }
							</span>
						) }
						{ pin.credit && <span className="ost-pin-credit">{ pin.credit }</span> }
					</div>
				) }
			</div>
			<div className="ost-pin-type-circle">
				<span><ContentIcon type={ pin.content_type } size={ 14 } /></span>
			</div>
		</div>
	) : (
		<a
			href={ pin.url || '#' }
			className={ cardClass }
			target="_blank"
			rel="noopener noreferrer"
		>
			<div className="ost-pin-thumb ost-pin-thumb-main">
				{ pin.thumbnail_url ? (
					<img src={ pin.thumbnail_url } alt="" />
				) : (
					<span className="ost-pin-icon"><ContentIcon type={ pin.content_type } size={ 24 } /></span>
				) }
			</div>
			<div className="ost-pin-body">
				<span className="ost-pin-title">{ pin.title }</span>
				{ expanded && ( pin.author_name || pin.tags?.length || pin.credit ) && (
					<div className="ost-pin-meta">
						{ pin.author_name && <span className="ost-pin-author">{ pin.author_name }</span> }
						{ pin.tags?.length > 0 && (
							<span className="ost-pin-tags">
								<Tag size={ 14 } strokeWidth={ 2 } aria-hidden />
								{ pin.tags.join( ', ' ) }
							</span>
						) }
						{ pin.credit && <span className="ost-pin-credit">{ pin.credit }</span> }
					</div>
				) }
			</div>
			<div className="ost-pin-type-circle">
				<span><ContentIcon type={ pin.content_type } size={ 14 } /></span>
			</div>
		</a>
	);

	if ( canDrag ) {
		return (
			<div
				ref={ setNodeRef }
				style={ style }
				{ ...attributes }
				{ ...listeners }
				className="ost-pin-sortable-wrapper"
			>
				{ content }
			</div>
		);
	}
	return <div className="ost-pin-wrapper">{ content }</div>;
}

function useMobile() {
	const [ isMobile, setIsMobile ] = useState( false );
	useLayoutEffect( () => {
		const mq = window.matchMedia( '(max-width: 768px)' );
		setIsMobile( mq.matches ); /* עדכון התחלתי - קריטי למובייל */
		const handler = () => setIsMobile( mq.matches );
		mq.addEventListener( 'change', handler );
		return () => mq.removeEventListener( 'change', handler );
	}, [] );
	return isMobile;
}

function TopicSegment( {
	topic,
	topicIndex,
	expanded,
	onExpand,
	onCollapse,
	dragMode,
	onToggleDrag,
	onReorder,
	canEdit,
	isTail,
	isMobile,
} ) {
	const pins = [ ...( topic.pins || [] ) ].sort( ( a, b ) => a.position_order - b.position_order );
	const [ localPins, setLocalPins ] = useState( pins );

	useEffect( () => {
		setLocalPins( [ ...( topic.pins || [] ) ].sort( ( a, b ) => a.position_order - b.position_order ) );
	}, [ topic.pins, topic.id ] );

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 8 } } )
	);

	const handleDragEnd = useCallback( ( event ) => {
		const { active, over } = event;
		if ( ! over || active.id === over.id ) return;
		const oldIndex = localPins.findIndex( ( p ) => `pin-${ p.id }` === active.id );
		const newIndex = localPins.findIndex( ( p ) => `pin-${ p.id }` === over.id );
		if ( oldIndex < 0 || newIndex < 0 ) return;
		const next = arrayMove( localPins, oldIndex, newIndex );
		setLocalPins( next );
		onReorder?.( topic.id, next.map( ( p ) => p.id ) );
	}, [ localPins, topic.id, onReorder ] );

	const isExpanded = expanded === topic.id;

	return (
		<div
			className={ `ost-topic-segment ${ isExpanded ? 'ost-topic-expanded' : '' } ${ isTail ? 'ost-topic-tail' : '' }` }
			data-topic-id={ topic.id }
		>
			<button
				type="button"
				className="ost-topic-trigger"
				onClick={ () => isExpanded ? onCollapse() : onExpand( topic.id ) }
				aria-expanded={ isExpanded }
			>
				<span
					className="ost-topic-dot"
					style={ { backgroundColor: TOPIC_PALETTE[ topicIndex % TOPIC_PALETTE.length ] } }
					aria-hidden
				/>
				<span className="ost-topic-label">{ topic.title }</span>
				{ isMobile && pins.length > 0 && (
					<span className="ost-topic-count" aria-label={ `${ pins.length } פריטים` }>
						( { pins.length } )
					</span>
				) }
			</button>
			{ ! isTail && ( ! isMobile || isExpanded ) && (
			<div className="ost-topic-content">
				{ isExpanded ? (
					<>
						{ canEdit && (
							<div className="ost-topic-toolbar">
								<button
									type="button"
									className={ `ost-drag-mode-btn ${ dragMode ? 'active' : '' }` }
									onClick={ onToggleDrag }
									title="גרירה במרווחים מותאמים אישית"
								>
									{dragMode ? '✓ ' : ''}גרירה מותאמת
								</button>
							</div>
						) }
						{ dragMode && canEdit ? (
							<DndContext
								sensors={ sensors }
								collisionDetection={ closestCenter }
								onDragEnd={ handleDragEnd }
							>
								<SortableContext
									items={ localPins.map( ( p ) => `pin-${ p.id }` ) }
									strategy={ verticalListSortingStrategy }
								>
									<div className="ost-topic-pins ost-topic-pins-list">
										{ localPins.map( ( pin ) => (
											<PinCard
												key={ pin.id }
												pin={ pin }
												expanded
												canDrag
											/>
										) ) }
									</div>
								</SortableContext>
							</DndContext>
						) : (
							<div className="ost-topic-pins ost-topic-pins-list">
								{ localPins.map( ( pin ) => (
									<PinCard
										key={ pin.id }
										pin={ pin }
										expanded
										canDrag={ false }
									/>
								) ) }
							</div>
						) }
					</>
				) : (
					<div className="ost-topic-pins ost-topic-pins-grid">
						{ pins.slice( 0, 6 ).map( ( pin ) => (
							<PinCard key={ pin.id } pin={ pin } expanded={ false } canDrag={ false } />
						) ) }
						{ pins.length > 6 && (
							<span className="ost-topic-more">+{ pins.length - 6 }</span>
						) }
					</div>
				) }
			</div>
			) }
		</div>
	);
}

export default function TimelineViewer( { timelineId, fetchFn } ) {
	const [ timeline, setTimeline ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ expandedTopic, setExpandedTopic ] = useState( null );
	const [ dragMode, setDragMode ] = useState( false );
	const isMobile = useMobile();
	const fetchApi = getFetchFn( fetchFn );

	/* גרירה מותאמת רק בעורך בלוקים, לא בתצוגה ציבורית */
	const canEdit = !!fetchFn;

	useEffect( () => {
		if ( ! timelineId ) return;
		const path = fetchFn ? `/os-timeline/v1/timeline/${ timelineId }` : `/timeline/${ timelineId }`;
		fetchApi( path )
			.then( ( data ) => setTimeline( data ) )
			.catch( () => setTimeline( null ) )
			.finally( () => setLoading( false ) );
	}, [ timelineId, fetchFn ] );

	const handleReorder = useCallback( ( topicId, pinIds ) => {
		postFetch( `/topic/${ topicId }/reorder-pins`, { pin_ids: pinIds } ).catch( () => {} );
	}, [] );

	const handleLike = useCallback( () => {
		if ( ! timelineId ) return;
		postFetch( `/timeline/${ timelineId }/like`, {} )
			.then( ( res ) => res?.likes != null && setTimeline( ( t ) => ( t ? { ...t, likes: res.likes } : t ) ) )
			.catch( () => {} );
	}, [ timelineId ] );

	if ( loading ) {
		return <div className="ost-viewer-loading" dir="rtl">טוען...</div>;
	}
	if ( ! timeline ) {
		return <div className="ost-viewer-error" dir="rtl">ציר לא נמצא</div>;
	}

	const topics = timeline.topics || [];
	const expandedIdx = topics.findIndex( ( t ) => t.id === expandedTopic );

	return (
		<div
			className={ `ost-timeline-viewer ${ expandedTopic && ! isMobile ? 'ost-timeline-expanded' : '' } ${ isMobile ? 'ost-mobile-continuous' : '' }` }
			dir="rtl"
		>
			<div className="ost-viewer-header">
				<h2 className="ost-viewer-title">{ timeline.title }</h2>
				<div className="ost-viewer-meta">
					<span className="ost-meta-item ost-views" title="צפיות">
						<Eye size={ 18 } strokeWidth={ 2 } aria-hidden />
						{ timeline.views ?? 0 }
					</span>
					<button type="button" className="ost-meta-item ost-like-btn" onClick={ handleLike } title="לייק">
						<Heart size={ 18 } strokeWidth={ 2 } aria-hidden />
						<span className="ost-like-count">{ timeline.likes ?? 0 }</span>
					</button>
				</div>
			</div>
			<div className="ost-timeline-scroll">
				<div className="ost-spine">
					<div className="ost-spine-line" aria-hidden />
					<div className="ost-spine-content">
					{ topics.map( ( topic, idx ) => {
						const isExpanded = topic.id === expandedTopic;
						const showTail = ! isMobile && expandedTopic && (
							( expandedIdx > 0 && idx === expandedIdx - 1 ) ||
							( expandedIdx >= 0 && expandedIdx < topics.length - 1 && idx === expandedIdx + 1 )
						);
						const hideContent = ! isMobile && expandedTopic && ! isExpanded && ! showTail;
						if ( hideContent ) return null;
						return (
							<div
								key={ topic.id }
								className={ `ost-topic-wrapper ${ isExpanded ? 'ost-topic-wrapper-expanded' : '' } ${ showTail ? 'ost-topic-wrapper-tail' : '' }` }
							>
								<TopicSegment
									topic={ topic }
									topicIndex={ idx }
									expanded={ expandedTopic }
									onExpand={ setExpandedTopic }
									onCollapse={ () => setExpandedTopic( null ) }
									dragMode={ dragMode }
									onToggleDrag={ () => setDragMode( ( v ) => ! v ) }
									onReorder={ canEdit ? handleReorder : undefined }
									canEdit={ canEdit }
									isTail={ showTail }
									isMobile={ isMobile }
								/>
							</div>
						);
					} ) }
					</div>
				</div>
			</div>
		</div>
	);
}
