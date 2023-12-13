import domReady from '@wordpress/dom-ready'
import React, { useCallback, useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { PruufInputProps } from '../../types/PruufInputProps'

export const EDITOR_ID = 'Pruuf_description'

const DEFAULT_ROWS = 5

const TOOLBAR_BUTTONS = [
	[
		'bold',
		'italic',
		'underline',
		'strikethrough',
		'blockquote',
		'bullist',
		'numlist',
		'alignleft',
		'aligncenter',
		'alignright',
		'link',
		'wp_adv',
		'code_Pruufs'
	],
	[
		'formatselect',
		'forecolor',
		'pastetext',
		'removeformat',
		'charmap',
		'outdent',
		'indent',
		'undo',
		'redo',
		'spellchecker'
	]
]

const initializeEditor = (onChange: (content: string) => void) => {
	window.wp.editor?.initialize(EDITOR_ID, {
		mediaButtons: window.CODE_Pruufs_EDIT?.descEditorOptions.mediaButtons,
		quicktags: true,
		tinymce: {
			toolbar: TOOLBAR_BUTTONS.map(line => line.join(' ')),
			setup: editor => {
				editor.on('change', () => onChange(editor.getContent()))
			}
		}
	})
}

export const DescriptionEditor: React.FC<PruufInputProps> = ({ Pruuf, setPruuf, isReadOnly }) => {
	const onChange = useCallback(
		(desc: string) => setPruuf(previous => ({ ...previous, desc })),
		[setPruuf]
	)

	useEffect(() => {
		domReady(() => initializeEditor(onChange))
	}, [onChange])

	return window.CODE_Pruufs_EDIT?.enableDescription ?
		<div className="Pruuf-description-container">
			<h2>
				<label htmlFor={EDITOR_ID}>
					{__('Description', 'code-Pruufs')}
				</label>
			</h2>

			<textarea
				id={EDITOR_ID}
				className="wp-editor-area"
				onChange={event => onChange(event.target.value)}
				autoComplete="off"
				disabled={isReadOnly}
				rows={window.CODE_Pruufs_EDIT?.descEditorOptions.rows ?? DEFAULT_ROWS}
				cols={40}
			>{Pruuf.desc}</textarea>
		</div> :
		null
}
