/**
 * Block: Card (used to be called Panel)
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToolbarButton,
	ToolbarGroup,
	TextControl,
	Popover,
	Card,
	CardBody
} from '@wordpress/components';


import {
	InspectorControls,
	BlockControls,
	useBlockProps,
	RichText
} from '@wordpress/block-editor';


import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

import { Fragment, useState } from '@wordpress/element';

import { ToolbarBootstrapHeadingLevelSelector } from '../shared-elements/toolbar';

import { ComboboxControl } from '@wordpress/components';

import './editor.scss';
import './style.scss';

export default function Edit( props ) {
	const blockProps = useBlockProps();
	const { attributes: {
		programName,
		selectedProgram,
		linkText,
		headingTag,
	}, setAttributes, isSelected } = props;

	const Tag = headingTag;

	const queryParams = {
		'format': 'select'
	};

	const [ programs, setPrograms ] = useState( [ {value: '', label: 'Loading...'}] );
	const [ isLoaded, setIsLoaded] = useState( false );
	if ( ! isLoaded ) {
		apiFetch( {
			path: addQueryArgs( '/bawb/v1/programs', queryParams ),
			method: 'GET',
		} ).then( ( response ) => {
			setPrograms( response );
			setIsLoaded( true );
		} ).catch( ( error ) => {
			console.log( error );
		} );
	}

	const ProgramSelectPopover = () => {
		const [ isVisible, setIsVisible ] = useState( false );
		const toggleVisibility = () => {
			setIsVisible( ! isVisible );
		};

		return (
			<ToolbarButton
				text={ __( 'Select Degree/Cert.' ) }
				icon="welcome-learn-more"
				onClick={ toggleVisibility }
				isActive={ isVisible }
			>
				{ isVisible && 
					<Popover
						variant="toolbar"
					>
						<div className="program-select-popover-content">
							<ComboboxControl
								label="Select Degree/Certificate"
								value={ selectedProgram }
								options={ programs }
								onChange={ ( selectedProgram ) => {
									setAttributes( { selectedProgram: selectedProgram } );
									programs.find( ( program ) => {
										if ( program.value === selectedProgram ) {
											setAttributes( { linkText: program.label } );
										}
									})
								}}
							/>
						</div>
					</Popover>
				}
			</ToolbarButton>
		)
	}

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarBootstrapHeadingLevelSelector
						values = { [ 'Heading 2', 'Heading 3', 'Heading 4', 'Heading 5', 'Heading 6', 'Paragraph' ] }
						active = { headingTag }
						onClick = { ( newLevel ) => { setAttributes( { headingTag: newLevel } ) } }
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ProgramSelectPopover />
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody title="Degree/Certificate to Display" initialOpen={ true }>
					<PanelRow>
						<ComboboxControl
							label="Select Degree/Certificate"
							value={ selectedProgram }
							options={ programs }
							onChange={ ( selectedProgram ) => {
								setAttributes( { selectedProgram: selectedProgram } );
								programs.find( ( program ) => {
									if ( program.value === selectedProgram ) {
										setAttributes( { linkText: program.label } );
									}
								})
							}}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label="Link Text"
							value={ linkText }
							placeholder= {programName ?? 'Enter link text'}
							onChange={ ( linkText ) => {
								setAttributes( { linkText: linkText } );
							} }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ ! selectedProgram &&
					<Card>
						<CardBody>
							Select a degree/certificate to display
						</CardBody>
					</Card>
				}
				{ selectedProgram &&
					<Tag>
						<RichText
							tagName="a"
							value={ linkText }
							onChange={ ( linkText ) => {
								setAttributes( { linkText: linkText } );
							}}
							placeholder='Enter the program name as you would like it to appear'
							allowedFormats={ [ 'core/bold', 'core/italic' ] } 
						/>
					</Tag>
				}
			</div>
		</>
	);
}
