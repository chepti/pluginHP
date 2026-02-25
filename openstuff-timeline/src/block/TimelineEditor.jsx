/**
 * Timeline Editor - Warehouse + Spine layout
 * עורך ציר: המחסן (שמאל) + ציר (ימין)
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	DndContext,
	DragOverlay,
	useDraggable,
	useDroppable,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';

function DraggablePostCard( { post, isDragging, onHide, onForLater, onNotRelated } ) {
	const [ menuOpen, setMenuOpen ] = useState( false );
	const [ previewOpen, setPreviewOpen ] = useState( false );
	const wrapperRef = useRef( null );

	useEffect( () => {
		if ( ! menuOpen && ! previewOpen ) return;
		const handler = ( e ) => {
			if ( wrapperRef.current && ! wrapperRef.current.contains( e.target ) ) {
				const previewEl = document.querySelector( '.ost-preview-overlay' );
				if ( ! previewEl || ! previewEl.contains( e.target ) ) {
					setMenuOpen( false );
					setPreviewOpen( false );
				}
			}
		};
		document.addEventListener( 'click', handler );
		return () => document.removeEventListener( 'click', handler );
	}, [ menuOpen, previewOpen ] );

	const { attributes, listeners, setNodeRef } = useDraggable( {
		id: `post-${ post.id }`,
		data: { type: 'post', post },
	} );
	return (
		<>
		<div
			ref={ wrapperRef }
			className={ `ost-post-card-wrapper ${ isDragging ? 'ost-dragging' : '' } ${ menuOpen ? 'ost-menu-open' : '' }` }
			data-post-id={ post.id }
		>
			<div
				ref={ setNodeRef }
				{ ...listeners }
				{ ...attributes }
				className="ost-post-card ost-draggable"
			>
				<div className="ost-card-thumb">
					{ post.thumbnail_url ? (
						<img src={ post.thumbnail_url } alt="" />
					) : (
						<span className="ost-card-icon">{ CONTENT_ICONS[ post.content_type ] || CONTENT_ICONS.default }</span>
					) }
				</div>
				<div className="ost-card-title">{ post.title }</div>
				<span className="ost-card-type">{ CONTENT_ICONS[ post.content_type ] || CONTENT_ICONS.default }</span>
			</div>
			<div className="ost-card-actions">
				<button
					type="button"
					className="ost-card-action-btn"
					onClick={ ( e ) => { e.stopPropagation(); setMenuOpen( ! menuOpen ); } }
					title={ __( 'פעולות', 'openstuff-timeline' ) }
					aria-expanded={ menuOpen }
				>
					⋮
				</button>
				{ menuOpen && (
					<div className="ost-card-action-menu">
						<button type="button" onClick={ ( e ) => { e.stopPropagation(); setPreviewOpen( true ); setMenuOpen( false ); } }>
							👁 { __( 'תצוגה מקדימה', 'openstuff-timeline' ) }
						</button>
						<button type="button" onClick={ ( e ) => { e.stopPropagation(); window.open( post.url, '_blank' ); setMenuOpen( false ); } }>
							🔗 { __( 'פתח בכרטיסייה חדשה', 'openstuff-timeline' ) }
						</button>
						<button type="button" onClick={ ( e ) => { e.stopPropagation(); onHide?.( post ); setMenuOpen( false ); } }>
							👁‍🗨 { __( 'הסתר', 'openstuff-timeline' ) }
						</button>
						<button type="button" onClick={ ( e ) => { e.stopPropagation(); onForLater?.( post ); setMenuOpen( false ); } }>
							⏱ { __( 'למיון בהמשך', 'openstuff-timeline' ) }
						</button>
						<button type="button" onClick={ ( e ) => { e.stopPropagation(); onNotRelated?.( post ); setMenuOpen( false ); } }>
							✕ { __( 'לא קשור לנושא', 'openstuff-timeline' ) }
						</button>
					</div>
				) }
			</div>
		</div>
		{ previewOpen && post.url && (
			<div
				className="ost-preview-overlay"
				role="dialog"
				aria-modal="true"
				aria-label={ __( 'תצוגה מקדימה', 'openstuff-timeline' ) }
			>
				<div className="ost-preview-backdrop" onClick={ () => setPreviewOpen( false ) } />
				<div className="ost-preview-modal">
					<div className="ost-preview-header">
						<span className="ost-preview-title">{ post.title }</span>
						<button type="button" className="ost-preview-close" onClick={ () => setPreviewOpen( false ) } aria-label={ __( 'סגור', 'openstuff-timeline' ) }>
							✕
						</button>
					</div>
					<iframe src={ post.url } title={ post.title } className="ost-preview-iframe" />
					<a href={ post.url } target="_blank" rel="noopener noreferrer" className="ost-preview-open-tab">
						{ __( 'פתח בכרטיסייה חדשה', 'openstuff-timeline' ) }
					</a>
				</div>
			</div>
		) }
		</>
	);
}

function DraggablePinCard( { pin, isDragging, onApprovePin } ) {
	const { attributes, listeners, setNodeRef } = useDraggable( {
		id: `pin-${ pin.id }`,
		data: { type: 'pin', pin },
	} );
	return (
		<div
			ref={ setNodeRef }
			{ ...listeners }
			{ ...attributes }
			className={ `ost-pin-card ost-draggable-pin ${ pin.status === 'pending' ? 'ost-pin-pending-card' : '' } ${ isDragging ? 'ost-dragging' : '' }` }
			data-pin-id={ pin.id }
		>
			<div className="ost-pin-thumb">
				{ pin.thumbnail_url ? (
					<img src={ pin.thumbnail_url } alt="" />
				) : (
					<span>{ CONTENT_ICONS[ pin.content_type ] || '📄' }</span>
				) }
			</div>
			<span className="ost-pin-title">{ pin.title }</span>
			{ pin.status === 'pending' && (
				<button
					type="button"
					className="ost-pin-approve-btn"
					onClick={ ( e ) => { e.stopPropagation(); onApprovePin?.( pin.id ); } }
					title={ __( 'אשר', 'openstuff-timeline' ) }
				>
					✓
				</button>
			) }
		</div>
	);
}

function DroppableWarehouse( { isOver, children } ) {
	const { setNodeRef } = useDroppable( { id: 'warehouse', data: { type: 'warehouse' } } );
	return (
		<div
			ref={ setNodeRef }
			className={ `ost-warehouse-droppable ost-warehouse-cards ${ isOver ? 'ost-warehouse-over' : '' }` }
		>
			{ children }
		</div>
	);
}

function TopicDragHandle( { topic, isDragging } ) {
	const { attributes, listeners, setNodeRef } = useDraggable( {
		id: `sort-topic-${ topic.id }`,
		data: { type: 'sort-topic', topic },
	} );
	return (
		<div
			ref={ setNodeRef }
			{ ...attributes }
			{ ...listeners }
			className={ `ost-topic-drag-handle ${ isDragging ? 'ost-dragging' : '' }` }
			title={ __( 'גרור לסידור', 'openstuff-timeline' ) }
			aria-label={ __( 'גרור לסידור', 'openstuff-timeline' ) }
		>
			<span className="ost-grip-dots" aria-hidden>
				<span className="ost-grip-dot" /><span className="ost-grip-dot" />
				<span className="ost-grip-dot" /><span className="ost-grip-dot" />
				<span className="ost-grip-dot" /><span className="ost-grip-dot" />
			</span>
		</div>
	);
}

function DroppableTopic( { topic, zoomOut, onApprovePin, isOver, children, sortMode, activeSortTopicId, onEditTopic, onDeleteTopic } ) {
	const [ menuOpen, setMenuOpen ] = useState( false );
	const [ editing, setEditing ] = useState( false );
	const [ editTitle, setEditTitle ] = useState( topic.title );
	const wrapperRef = useRef( null );

	useEffect( () => {
		if ( ! menuOpen && ! editing ) return;
		const handler = ( e ) => {
			if ( wrapperRef.current && ! wrapperRef.current.contains( e.target ) ) {
				setMenuOpen( false );
				if ( editing ) setEditing( false );
			}
		};
		document.addEventListener( 'click', handler );
		return () => document.removeEventListener( 'click', handler );
	}, [ menuOpen, editing ] );

	useEffect( () => setEditTitle( topic.title ), [ topic.title ] );

	const { setNodeRef } = useDroppable( {
		id: `topic-${ topic.id }`,
		data: { type: 'topic', topic },
	} );
	const setRefs = ( el ) => {
		wrapperRef.current = el;
		setNodeRef( el );
	};
	return (
		<div
			ref={ setRefs }
			className={ `ost-topic-segment ${ isOver ? 'ost-topic-over' : '' } ${ sortMode ? 'ost-topic-sort-mode' : '' }` }
			style={ { borderColor: topic.color } }
			data-topic-id={ topic.id }
		>
			<div className="ost-topic-header">
				{ sortMode && (
					<TopicDragHandle topic={ topic } isDragging={ activeSortTopicId === topic.id } />
				) }
				{ editing ? (
					<>
						<input
							type="text"
							className="ost-topic-edit-input"
							value={ editTitle }
							onChange={ ( e ) => setEditTitle( e.target.value ) }
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' ) {
									onEditTopic?.( topic.id, editTitle.trim() );
									setEditing( false );
								}
								if ( e.key === 'Escape' ) {
									setEditTitle( topic.title );
									setEditing( false );
								}
							} }
							autoFocus
						/>
						<button type="button" className="ost-topic-save-btn" onClick={ () => { onEditTopic?.( topic.id, editTitle.trim() ); setEditing( false ); } }>
							✓
						</button>
					</>
				) : (
					<>
						<span className="ost-topic-label">{ topic.title }</span>
						<div className="ost-topic-actions">
							<button
								type="button"
								className="ost-topic-edit-btn"
								onClick={ ( e ) => { e.stopPropagation(); setMenuOpen( ! menuOpen ); } }
								title={ __( 'ערוך נושא', 'openstuff-timeline' ) }
								aria-label={ __( 'ערוך נושא', 'openstuff-timeline' ) }
							>
								✎
							</button>
							{ menuOpen && (
								<div className="ost-topic-action-menu">
									<button type="button" onClick={ ( e ) => { e.stopPropagation(); setMenuOpen( false ); setEditing( true ); } }>
										✎ { __( 'ערוך שם', 'openstuff-timeline' ) }
									</button>
									<button type="button" onClick={ ( e ) => {
										e.stopPropagation();
										if ( window.confirm( __( 'למחוק את הנושא וכל החומרים שבו?', 'openstuff-timeline' ) ) ) {
											onDeleteTopic?.( topic.id );
										}
										setMenuOpen( false );
									} }>
										🗑 { __( 'מחק נושא', 'openstuff-timeline' ) }
									</button>
								</div>
							) }
						</div>
					</>
				) }
			</div>
			{ zoomOut ? (
				<div className="ost-topic-icons" />
			) : (
				<div className="ost-topic-pins">{ children }</div>
			) }
		</div>
	);
}

const CONTENT_ICONS = {
	game: '🎲',
	worksheet: '📝',
	presentation: '⚙️',
	template: '🏗️',
	video: '🎬',
	default: '📄',
};

export default function TimelineEditor( { timelineId, onTimelineChange } ) {
	const [ timelines, setTimelines ] = useState( [] );
	const [ selectedTimeline, setSelectedTimeline ] = useState( null );
	const [ posts, setPosts ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ search, setSearch ] = useState( '' );
	const [ contentFilter, setContentFilter ] = useState( '' );
	const [ searchAll, setSearchAll ] = useState( false );
	const [ zoomOut, setZoomOut ] = useState( false );
	const [ fullTimeline, setFullTimeline ] = useState( null );
	const [ newTopicTitle, setNewTopicTitle ] = useState( '' );
	const [ addingTopic, setAddingTopic ] = useState( false );
	const [ activeId, setActiveId ] = useState( null );
	const [ overId, setOverId ] = useState( null );
	const [ hiddenIds, setHiddenIds ] = useState( new Set() );
	const [ forLaterIds, setForLaterIds ] = useState( new Set() );
	const [ categories, setCategories ] = useState( [] );
	const [ sortTopicsMode, setSortTopicsMode ] = useState( false );

	useEffect( () => {
		apiFetch( { path: '/os-timeline/v1/categories' } )
			.then( ( data ) => setCategories( data || [] ) )
			.catch( () => setCategories( [] ) );
	}, [] );

	const pinnedPostIds = new Set(
		fullTimeline?.topics?.flatMap( ( t ) => ( t.pins || [] ).map( ( p ) => p.post_id ) ) || []
	);

	const handleHidePost = ( post ) => {
		setHiddenIds( ( prev ) => new Set( prev ).add( post.id ) );
	};

	const handleForLater = ( post ) => {
		setForLaterIds( ( prev ) => new Set( prev ).add( post.id ) );
		setHiddenIds( ( prev ) => new Set( prev ).add( post.id ) );
	};

	const handleNotRelated = ( post ) => {
		const msg = __( 'האם להסיר שיוך לתחום הדעת מהפוסט?', 'openstuff-timeline' );
		if ( ! window.confirm( msg ) ) return;
		const subjectId = selectedTimeline?.subject_id;
		if ( ! subjectId ) return;
		apiFetch( {
			path: `/os-timeline/v1/post/${ post.id }/remove-subject`,
			method: 'POST',
			data: { subject_id: subjectId },
		} ).then( () => {
			setPosts( ( prev ) => prev.filter( ( p ) => p.id !== post.id ) );
		} );
	};

	const visiblePosts = posts.filter( ( p ) => ! hiddenIds.has( p.id ) && ! pinnedPostIds.has( p.id ) );
	const forLaterPosts = posts.filter( ( p ) => forLaterIds.has( p.id ) );

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 8 } } )
	);

	const handleDragStart = ( event ) => setActiveId( event.active.id );
	const handleDragOver = ( event ) => setOverId( event.over?.id ?? null );
	const handleDragEnd = ( event ) => {
		const { active, over } = event;
		setActiveId( null );
		setOverId( null );
		if ( ! over || active.id === over.id ) return;

		const sortTopicMatch = String( active.id ).match( /^sort-topic-(.+)$/ );
		const postMatch = String( active.id ).match( /^post-(.+)$/ );
		const pinMatch = String( active.id ).match( /^pin-(.+)$/ );
		const topicMatch = String( over.id ).match( /^topic-(.+)$/ );
		const isWarehouse = over.id === 'warehouse';

		if ( sortTopicMatch && topicMatch ) {
			const draggedId = parseInt( sortTopicMatch[ 1 ], 10 );
			const overIdNum = parseInt( topicMatch[ 1 ], 10 );
			const topics = fullTimeline?.topics || [];
			const fromIdx = topics.findIndex( ( t ) => t.id === draggedId );
			const toIdx = topics.findIndex( ( t ) => t.id === overIdNum );
			if ( fromIdx >= 0 && toIdx >= 0 && fromIdx !== toIdx ) {
				const newOrder = [ ...topics ];
				const [ removed ] = newOrder.splice( fromIdx, 1 );
				newOrder.splice( toIdx, 0, removed );
				const prevTimeline = fullTimeline;
				setFullTimeline( { ...fullTimeline, topics: newOrder } );
				reorderTopics( newOrder.map( ( t ) => t.id ), () => setFullTimeline( prevTimeline ) );
			}
		} else if ( postMatch && topicMatch ) {
			const postId = parseInt( postMatch[ 1 ], 10 );
			const topicId = parseInt( topicMatch[ 1 ], 10 );
			apiFetch( {
				path: '/os-timeline/v1/pin',
				method: 'POST',
				data: { post_id: postId, topic_id: topicId },
			} ).then( () => refreshTimeline() );
		} else if ( pinMatch && topicMatch ) {
			const pinId = parseInt( pinMatch[ 1 ], 10 );
			const topicId = parseInt( topicMatch[ 1 ], 10 );
			apiFetch( {
				path: `/os-timeline/v1/pin/${ pinId }/move`,
				method: 'PUT',
				data: { topic_id: topicId },
			} ).then( () => refreshTimeline() );
		} else if ( pinMatch && isWarehouse ) {
			const pinId = parseInt( pinMatch[ 1 ], 10 );
			apiFetch( {
				path: `/os-timeline/v1/pin/${ pinId }/unpin`,
				method: 'DELETE',
			} ).then( () => refreshTimeline() );
		}
	};

	const refreshTimeline = () => {
		if ( selectedTimeline ) {
			apiFetch( { path: `/os-timeline/v1/timeline/${ selectedTimeline.id }` } )
				.then( ( data ) => setFullTimeline( data ) )
				.catch( () => setFullTimeline( null ) );
			loadPosts( selectedTimeline.id, search, contentFilter, searchAll );
		}
	};

	const reorderTopics = ( topicIds, onError ) => {
		if ( ! selectedTimeline ) return;
		const ids = topicIds.map( ( id ) => parseInt( id, 10 ) ).filter( ( id ) => id > 0 );
		apiFetch( {
			path: `/os-timeline/v1/timeline/${ selectedTimeline.id }/reorder-topics`,
			method: 'POST',
			data: { topic_ids: ids },
			headers: { 'Content-Type': 'application/json' },
		} ).catch( () => onError?.() );
	};

	const handleApprovePin = ( pinId ) => {
		apiFetch( {
			path: `/os-timeline/v1/pin/${ pinId }/approve`,
			method: 'PUT',
		} ).then( () => {
			if ( selectedTimeline ) {
				apiFetch( { path: `/os-timeline/v1/timeline/${ selectedTimeline.id }` } )
					.then( ( data ) => setFullTimeline( data ) )
					.catch( () => setFullTimeline( null ) );
			}
		} );
	};

	const activePost = activeId && String( activeId ).startsWith( 'post-' )
		? posts.find( ( p ) => `post-${ p.id }` === activeId )
		: null;
	const activePin = activeId && String( activeId ).startsWith( 'pin-' )
		? fullTimeline?.topics?.flatMap( ( t ) => t.pins || [] ).find( ( p ) => `pin-${ p.id }` === activeId )
		: null;
	const activeSortTopic = activeId && String( activeId ).startsWith( 'sort-topic-' )
		? fullTimeline?.topics?.find( ( t ) => `sort-topic-${ t.id }` === activeId )
		: null;

	useEffect( () => {
		apiFetch( { path: '/os-timeline/v1/timelines' } )
			.then( ( data ) => {
				setTimelines( data || [] );
				if ( timelineId && data?.length ) {
					const t = data.find( ( x ) => x.id === timelineId );
					if ( t ) setSelectedTimeline( t );
					else setSelectedTimeline( data[ 0 ] );
				} else if ( data?.length ) {
					setSelectedTimeline( data[ 0 ] );
				}
			} )
			.catch( () => setTimelines( [] ) )
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => {
		if ( selectedTimeline ) {
			onTimelineChange?.( selectedTimeline.id );
			loadPosts( selectedTimeline.id );
			apiFetch( { path: `/os-timeline/v1/timeline/${ selectedTimeline.id }` } )
				.then( ( data ) => setFullTimeline( data ) )
				.catch( () => setFullTimeline( null ) );
		} else {
			setFullTimeline( null );
		}
	}, [ selectedTimeline?.id ] );

	useEffect( () => {
		if ( selectedTimeline ) {
			loadPosts( selectedTimeline.id, search, contentFilter, searchAll );
		}
	}, [ search, contentFilter, searchAll ] );

	const addTopic = () => {
		if ( ! newTopicTitle.trim() || ! selectedTimeline ) return;
		setAddingTopic( true );
		apiFetch( {
			path: '/os-timeline/v1/topic',
			method: 'POST',
			data: {
				timeline_id: selectedTimeline.id,
				title: newTopicTitle.trim(),
				color: '#E8F4F8',
				order: fullTimeline?.topics?.length || 0,
			},
		} ).then( () => {
			setNewTopicTitle( '' );
			apiFetch( { path: `/os-timeline/v1/timeline/${ selectedTimeline.id }` } )
				.then( ( data ) => setFullTimeline( data ) )
				.catch( () => setFullTimeline( null ) );
		} ).catch( () => {} ).finally( () => setAddingTopic( false ) );
	};

	const handleEditTopic = ( topicId, newTitle ) => {
		if ( ! newTitle.trim() ) return;
		apiFetch( {
			path: `/os-timeline/v1/topic/${ topicId }`,
			method: 'PUT',
			data: { title: newTitle.trim() },
			headers: { 'Content-Type': 'application/json' },
		} ).then( () => {
			setFullTimeline( ( prev ) => ( {
				...prev,
				topics: prev.topics.map( ( t ) => ( t.id === topicId ? { ...t, title: newTitle.trim() } : t ) ),
			} ) );
		} );
	};

	const handleDeleteTopic = ( topicId ) => {
		apiFetch( {
			path: `/os-timeline/v1/topic/${ topicId }`,
			method: 'DELETE',
		} ).then( () => {
			apiFetch( { path: `/os-timeline/v1/timeline/${ selectedTimeline.id }` } )
				.then( ( data ) => setFullTimeline( data ) )
				.catch( () => setFullTimeline( null ) );
		} );
	};

	const loadPosts = ( id, s = '', ct = '', all = false ) => {
		let path = `/os-timeline/v1/posts?timeline=${ id }`;
		if ( s ) path += `&search=${ encodeURIComponent( s ) }`;
		if ( ct ) path += `&content_type=${ encodeURIComponent( ct ) }`;
		if ( all ) path += '&search_all=1';
		apiFetch( { path } )
			.then( ( data ) => setPosts( data || [] ) )
			.catch( () => setPosts( [] ) );
	};


	if ( loading ) {
		return (
			<div className="ost-editor-loading" dir="rtl">
				{ __( 'טוען...', 'openstuff-timeline' ) }
			</div>
		);
	}

	if ( ! timelines.length ) {
		return (
			<div className="ost-editor-empty" dir="rtl">
				<p>{ __( 'לא נמצאו צירי זמן. וודא שיש פוסטים עם תחום דעת וכיתה.', 'openstuff-timeline' ) }</p>
			</div>
		);
	}

	return (
		<DndContext
			sensors={ sensors }
			onDragStart={ handleDragStart }
			onDragOver={ handleDragOver }
			onDragEnd={ handleDragEnd }
		>
		<div className="ost-timeline-editor" dir="rtl">
			<div className="ost-editor-toolbar">
				<select
					value={ selectedTimeline?.id || '' }
					onChange={ ( e ) => {
						const t = timelines.find( ( x ) => x.id === parseInt( e.target.value, 10 ) );
						if ( t ) setSelectedTimeline( t );
					} }
				>
					{ timelines.map( ( t ) => (
						<option key={ t.id } value={ t.id }>
							{ t.title }
						</option>
					) ) }
				</select>
				<button
					type="button"
					className={ `ost-zoom-toggle ${ zoomOut ? 'zoom-out' : 'zoom-in' }` }
					onClick={ () => setZoomOut( ! zoomOut ) }
					title={ zoomOut ? __( 'הצג פריטים', 'openstuff-timeline' ) : __( 'תצוגה שנתית', 'openstuff-timeline' ) }
				>
					{ zoomOut ? '⊟' : '⊞' }
				</button>
				<button
					type="button"
					className={ `ost-sort-topics-btn ${ sortTopicsMode ? 'active' : '' }` }
					onClick={ () => setSortTopicsMode( ! sortTopicsMode ) }
					title={ __( 'סידור נושאים', 'openstuff-timeline' ) }
				>
					{ __( 'סידור נושאים', 'openstuff-timeline' ) }
				</button>
			</div>

			<div className="ost-editor-layout">
				{/* המחסן - Left panel */}
				<aside className="ost-warehouse">
					<h3 className="ost-warehouse-title">{ __( 'המחסן', 'openstuff-timeline' ) }</h3>
					<div className="ost-warehouse-filters">
						<input
							type="search"
							placeholder={ __( 'חפש חומרים...', 'openstuff-timeline' ) }
							value={ search }
							onChange={ ( e ) => setSearch( e.target.value ) }
							className="ost-search-input"
						/>
						<select
							value={ searchAll ? 'all' : 'class' }
							onChange={ ( e ) => setSearchAll( e.target.value === 'all' ) }
							className="ost-search-scope"
							title={ __( 'היקף חיפוש', 'openstuff-timeline' ) }
						>
							<option value="class">{ __( 'לפי כיתות (ברירת מחדל)', 'openstuff-timeline' ) }</option>
							<option value="all">{ __( 'כל המאגר', 'openstuff-timeline' ) }</option>
						</select>
						<select
							value={ contentFilter }
							onChange={ ( e ) => setContentFilter( e.target.value ) }
							className="ost-content-filter"
						>
							<option value="">{ __( 'כל סוגי התוכן', 'openstuff-timeline' ) }</option>
							{ ( categories.length ? categories : [
								{ id: 0, name: 'פעילות' },
								{ id: 1, name: 'מערך שיעור' },
								{ id: 2, name: 'דף עבודה' },
								{ id: 3, name: 'סרטון' },
								{ id: 4, name: 'מצגת' },
								{ id: 5, name: 'כלי דיגיטלי' },
								{ id: 6, name: 'תבנית' },
							] ).map( ( c ) => (
								<option key={ c.id } value={ c.name }>{ c.name }</option>
							) ) }
						</select>
					</div>
					<DroppableWarehouse isOver={ overId === 'warehouse' }>
						{ overId === 'warehouse' && activeId?.startsWith( 'pin-' ) && (
							<p className="ost-warehouse-drop-hint">{ __( 'שחרר להסרה מהציר', 'openstuff-timeline' ) }</p>
						) }
						{ visiblePosts.map( ( p ) => (
							<DraggablePostCard
								key={ p.id }
								post={ p }
								isDragging={ activeId === `post-${ p.id }` }
								onHide={ handleHidePost }
								onForLater={ handleForLater }
								onNotRelated={ handleNotRelated }
							/>
						) ) }
						{ ! visiblePosts.length && forLaterPosts.length === 0 && (
							<p className="ost-no-posts">
								{ hiddenIds.size > 0
									? __( 'הסתרת פריטים. רענן לדף כדי לראות שוב.', 'openstuff-timeline' )
									: __( 'אין חומרים התואמים לציר זה', 'openstuff-timeline' ) }
							</p>
						) }
						{ forLaterPosts.length > 0 && (
							<div className="ost-for-later-section">
								<h4 className="ost-for-later-title">{ __( 'למיון בהמשך', 'openstuff-timeline' ) }</h4>
								{ forLaterPosts.map( ( p ) => (
									<div key={ p.id } className="ost-post-card ost-for-later-card">
										<div className="ost-card-thumb">
											{ p.thumbnail_url ? (
												<img src={ p.thumbnail_url } alt="" />
											) : (
												<span className="ost-card-icon">{ CONTENT_ICONS[ p.content_type ] || CONTENT_ICONS.default }</span>
											) }
										</div>
										<div className="ost-card-title">{ p.title }</div>
										<button
											type="button"
											className="ost-restore-btn"
											onClick={ () => {
												setForLaterIds( ( prev ) => { const s = new Set( prev ); s.delete( p.id ); return s; } );
												setHiddenIds( ( prev ) => { const s = new Set( prev ); s.delete( p.id ); return s; } );
											} }
											title={ __( 'החזר למחסן', 'openstuff-timeline' ) }
										>
											↩
										</button>
									</div>
								) ) }
							</div>
						) }
					</DroppableWarehouse>
				</aside>

				{/* ציר - Right spine */}
				<main className="ost-spine-area">
					<div className="ost-spine">
						{ fullTimeline?.topics?.length ? (
							<>
							{ fullTimeline.topics.map( ( topic ) => (
								<DroppableTopic
									key={ topic.id }
									topic={ topic }
									zoomOut={ zoomOut }
									onApprovePin={ handleApprovePin }
									isOver={ overId === `topic-${ topic.id }` }
									sortMode={ sortTopicsMode }
									activeSortTopicId={ activeSortTopic?.id }
									onEditTopic={ handleEditTopic }
									onDeleteTopic={ handleDeleteTopic }
								>
									{ topic.pins?.map( ( pin ) => (
										<DraggablePinCard
											key={ pin.id }
											pin={ pin }
											isDragging={ activeId === `pin-${ pin.id }` }
											onApprovePin={ handleApprovePin }
										/>
									) ) }
								</DroppableTopic>
							) ) }
							<div className="ost-add-topic-inline">
								<input
									type="text"
									placeholder={ __( 'נושא חדש', 'openstuff-timeline' ) }
									value={ newTopicTitle }
									onChange={ ( e ) => setNewTopicTitle( e.target.value ) }
									onKeyDown={ ( e ) => e.key === 'Enter' && addTopic() }
								/>
								<button type="button" onClick={ addTopic } disabled={ addingTopic || ! newTopicTitle.trim() }>
									+
								</button>
							</div>
							</>
						) : (
							<div className="ost-spine-empty">
								<div className="ost-add-topic-form">
									<input
										type="text"
										placeholder={ __( 'שם נושא חדש', 'openstuff-timeline' ) }
										value={ newTopicTitle }
										onChange={ ( e ) => setNewTopicTitle( e.target.value ) }
										onKeyDown={ ( e ) => e.key === 'Enter' && addTopic() }
									/>
									<button type="button" onClick={ addTopic } disabled={ addingTopic || ! newTopicTitle.trim() }>
										{ addingTopic ? __( 'מוסיף...', 'openstuff-timeline' ) : __( 'הוסף נושא', 'openstuff-timeline' ) }
									</button>
								</div>
								<p>{ __( 'גרור חומרים מהמחסן אל נושאים', 'openstuff-timeline' ) }</p>
							</div>
						) }
					</div>
				</main>
			</div>
		</div>
		<DragOverlay>
			{ activeSortTopic ? (
				<div className="ost-topic-segment ost-drag-overlay ost-topic-sort-mode" style={ { borderColor: activeSortTopic.color } }>
					<div className="ost-topic-header">
						<div className="ost-topic-drag-handle ost-dragging">
							<span className="ost-grip-dots" aria-hidden>
								<span className="ost-grip-dot" /><span className="ost-grip-dot" />
								<span className="ost-grip-dot" /><span className="ost-grip-dot" />
								<span className="ost-grip-dot" /><span className="ost-grip-dot" />
							</span>
						</div>
						<span className="ost-topic-label">{ activeSortTopic.title }</span>
					</div>
				</div>
			) : activePost ? (
				<div className="ost-post-card ost-drag-overlay">
					<div className="ost-card-thumb">
						{ activePost.thumbnail_url ? (
							<img src={ activePost.thumbnail_url } alt="" />
						) : (
							<span className="ost-card-icon">{ CONTENT_ICONS[ activePost.content_type ] || CONTENT_ICONS.default }</span>
						) }
					</div>
					<div className="ost-card-title">{ activePost.title }</div>
					<span className="ost-card-type">{ CONTENT_ICONS[ activePost.content_type ] || CONTENT_ICONS.default }</span>
				</div>
			) : activePin ? (
				<div className="ost-pin-card ost-drag-overlay">
					<div className="ost-pin-thumb">
						{ activePin.thumbnail_url ? (
							<img src={ activePin.thumbnail_url } alt="" />
						) : (
							<span>{ CONTENT_ICONS[ activePin.content_type ] || '📄' }</span>
						) }
					</div>
					<span className="ost-pin-title">{ activePin.title }</span>
				</div>
			) : null }
		</DragOverlay>
		</DndContext>
	);
}
