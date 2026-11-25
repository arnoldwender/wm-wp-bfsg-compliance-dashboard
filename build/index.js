( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var __ = wp.i18n.__;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'wm/bfsg-anlage-3', {
		title: __( 'Erklärung zur Barrierefreiheit (BFSG)', 'wm-bfsg-compliance-dashboard' ),
		description: __(
			'Gibt die Erklärung zur Barrierefreiheit aus den gespeicherten Angaben oder optionalen Block-Attributen aus.',
			'wm-bfsg-compliance-dashboard'
		),
		category: 'widgets',
		icon: 'universal-access-alt',
		attributes: {
			company: { type: 'string', default: '' },
			url: { type: 'string', default: '' },
			level: { type: 'string', default: '' },
			email: { type: 'string', default: '' },
			phone: { type: 'string', default: '' },
			officer: { type: 'string', default: '' },
			authority: { type: 'string', default: '' },
		},
		supports: {
			html: false,
			align: [ 'wide', 'full' ],
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Angaben überschreiben (optional)', 'wm-bfsg-compliance-dashboard' ),
							initialOpen: true,
						},
						el( TextControl, {
							label: __( 'Name des Unternehmens', 'wm-bfsg-compliance-dashboard' ),
							value: attributes.company,
							onChange: function ( val ) {
								setAttributes( { company: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Adresse der Website', 'wm-bfsg-compliance-dashboard' ),
							value: attributes.url,
							onChange: function ( val ) {
								setAttributes( { url: val } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Stand der Vereinbarkeit', 'wm-bfsg-compliance-dashboard' ),
							value: attributes.level,
							options: [
								{
									label: __( 'Gespeicherte Einstellung verwenden', 'wm-bfsg-compliance-dashboard' ),
									value: '',
								},
								{ label: 'vollständig konform', value: 'vollständig konform' },
								{ label: 'größtenteils konform', value: 'größtenteils konform' },
								{ label: 'nicht konform', value: 'nicht konform' },
							],
							onChange: function ( val ) {
								setAttributes( { level: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Ansprechperson (optional)', 'wm-bfsg-compliance-dashboard' ),
							value: attributes.officer,
							onChange: function ( val ) {
								setAttributes( { officer: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'E-Mail für Rückmeldungen', 'wm-bfsg-compliance-dashboard' ),
							value: attributes.email,
							onChange: function ( val ) {
								setAttributes( { email: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Telefon (optional)', 'wm-bfsg-compliance-dashboard' ),
							value: attributes.phone,
							onChange: function ( val ) {
								setAttributes( { phone: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Durchsetzungsstelle', 'wm-bfsg-compliance-dashboard' ),
							value: attributes.authority,
							onChange: function ( val ) {
								setAttributes( { authority: val } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'wm/bfsg-anlage-3',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
