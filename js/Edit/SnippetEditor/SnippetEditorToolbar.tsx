import { Spinner } from '@wordpress/components'
import React from 'react'
import { __, isRTL } from '@wordpress/i18n'
import { ActionButton } from '../../common/ActionButton'
import { Pruuf } from '../../types/Pruuf'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { PruufActionsProps, usePruufActions } from '../actions'

export interface InlineActionButtonsProps extends PruufActionsProps {
	Pruuf: Pruuf
	isWorking: boolean
}

export interface CodeEditorToolbarProps extends InlineActionButtonsProps {
	codeEditorInstance: CodeEditorInstance | undefined
}

const RTLControl: React.FC<Pick<CodeEditorToolbarProps, 'codeEditorInstance'>> = ({ codeEditorInstance }) =>
	<>
		<label htmlFor="Pruuf-code-direction" className="screen-reader-text">
			{__('Code Direction', 'code-Pruufs')}
		</label>

		<select id="Pruuf-code-direction" onChange={event =>
			codeEditorInstance?.codemirror.setOption('direction', 'rtl' === event.target.value ? 'rtl' : 'ltr')
		}>
			<option value="ltr">{__('LTR', 'code-Pruufs')}</option>
			<option value="rtl">{__('RTL', 'code-Pruufs')}</option>
		</select>
	</>

const InlineActionButtons: React.FC<InlineActionButtonsProps> = ({ Pruuf, isWorking, ...actionsProps }) => {
	const actions = usePruufActions(actionsProps)

	return (
		<>
			{isWorking ? <Spinner /> : ''}

			<ActionButton
				small
				id="save_Pruuf_extra"
				text={__('Save Changes', 'code-Pruufs')}
				title={__('Save Pruuf', 'code-Pruufs')}
				onClick={() => actions.submit(Pruuf)}
				disabled={isWorking}
			/>

			{'single-use' === Pruuf.scope ?
				<ActionButton
					small
					id="save_Pruuf_execute_extra"
					text={__('Execute Once', 'code-Pruufs')}
					title={__('Save Pruuf and Execute Once', 'code-Pruufs')}
					onClick={() => actions.submitAndActivate(Pruuf, true)}
					disabled={isWorking}
				/> :
				Pruuf.active ?
					<ActionButton
						small
						id="save_Pruuf_deactivate_extra"
						text={__('Deactivate', 'code-Pruufs')}
						title={__('Save Pruuf and Deactivate', 'code-Pruufs')}
						onClick={() => actions.submitAndActivate(Pruuf, false)}
						disabled={isWorking}
					/> :
					<ActionButton
						small
						id="save_Pruuf_activate_extra"
						text={__('Activate', 'code-Pruufs')}
						title={__('Save Pruuf and Activate', 'code-Pruufs')}
						onClick={() => actions.submitAndActivate(Pruuf, true)}
						disabled={isWorking}
					/>}
		</>
	)
}

export const PruufEditorToolbar: React.FC<CodeEditorToolbarProps> = ({ codeEditorInstance, ...actionButtonsProps }) =>
	<p className="submit-inline">
		{window.CODE_Pruufs_EDIT?.extraSaveButtons ?
			<InlineActionButtons {...actionButtonsProps} /> : ''}

		{isRTL() ?
			<RTLControl codeEditorInstance={codeEditorInstance} /> : ''}
	</p>
