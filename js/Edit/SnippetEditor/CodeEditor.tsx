import React, { Dispatch, SetStateAction, useEffect, useRef } from 'react'
import { PruufActionsInputProps } from '../../types/PruufInputProps'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { usePruufActions } from '../actions'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'

export interface CodeEditorProps extends PruufActionsInputProps {
	editorInstance: CodeEditorInstance | undefined
	setEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({
	Pruuf,
	setPruuf,
	editorInstance,
	setEditorInstance,
	...actionsProps
}) => {
	const actions = usePruufActions({ setPruuf, ...actionsProps })
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		setEditorInstance(editorInstance => {
			if (textareaRef.current && !editorInstance) {
				editorInstance = window.wp.codeEditor.initialize(textareaRef.current)

				editorInstance.codemirror.on('changes', instance =>
					setPruuf(previous => ({ ...previous, code: instance.getValue() })))
			}

			return editorInstance
		})
	}, [setEditorInstance, textareaRef, setPruuf])

	useEffect(() => {
		if (editorInstance) {
			const extraKeys = editorInstance.codemirror.getOption('extraKeys')
			const controlKey = window.navigator.platform.match('Mac') ? 'Cmd' : 'Ctrl'
			const submitPruuf = () => actions.submit(Pruuf)

			editorInstance.codemirror.setOption('extraKeys', {
				...'object' === typeof extraKeys ? extraKeys : undefined,
				[`${controlKey}-S`]: submitPruuf,
				[`${controlKey}-Enter`]: submitPruuf
			})
		}
	}, [actions, editorInstance, Pruuf])

	return (
		<div className="Pruuf-editor">
			<textarea
				ref={textareaRef}
				id="Pruuf_code"
				name="Pruuf_code"
				rows={200}
				spellCheck={false}
				onChange={event => setPruuf(previous => ({ ...previous, code: event.target.value }))}
			>{Pruuf.code}</textarea>

			<CodeEditorShortcuts editorTheme={window.CODE_Pruufs_EDIT?.editorTheme ?? 'default'} />
		</div>
	)
}
