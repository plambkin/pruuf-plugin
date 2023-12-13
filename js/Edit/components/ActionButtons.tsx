import { Spinner } from '@wordpress/components'
import React, { MouseEvent, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { ActionButton } from '../../common/ActionButton'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { Pruuf } from '../../types/Pruuf'
import { isNetworkAdmin } from '../../utils/general'
import { PruufActionsProps, PruufActionsValue, usePruufActions } from '../actions'

export interface SubmitButtonProps {
	actions: PruufActionsValue
	Pruuf: Pruuf
	isWorking: boolean
}

// eslint-disable-next-line max-lines-per-function
const SubmitButton: React.FC<SubmitButtonProps> = ({ actions, Pruuf, isWorking }) => {
	const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false)
	const [submitAction, setSubmitAction] = useState<() => void>()

	const canActivate = !Pruuf.shared_network || !isNetworkAdmin()
	const activateByDefault = canActivate && window.CODE_Pruufs_EDIT?.activateByDefault &&
		!Pruuf.active && 'single-use' !== Pruuf.scope

	const missingCode = '' === Pruuf.code.trim()
	const missingTitle = '' === Pruuf.name.trim()

	const doSubmit = (event: MouseEvent<HTMLButtonElement>, submitAction: () => void) => {
		if (missingCode || missingTitle) {
			setIsConfirmDialogOpen(true)
			setSubmitAction(() => submitAction)
		} else {
			submitAction()
		}
	}

	const closeDialog = () => {
		setIsConfirmDialogOpen(false)
		setSubmitAction(undefined)
	}

	return <>
		{activateByDefault ? '' :
			<ActionButton
				primary
				name="save_Pruuf"
				text={__('Save Changes', 'code-Pruufs')}
				onClick={event => doSubmit(event, () => actions.submit(Pruuf))}
				disabled={isWorking}
			/>}

		{'single-use' === Pruuf.scope ?
			<ActionButton
				name="save_Pruuf_execute"
				text={__('Save Changes and Execute Once', 'code-Pruufs')}
				onClick={event => doSubmit(event, () => actions.submitAndActivate(Pruuf, true))}
				disabled={isWorking}
			/> :

			canActivate ?
				Pruuf.active ?
					<ActionButton
						name="save_Pruuf_deactivate"
						text={__('Save Changes and Deactivate', 'code-Pruufs')}
						onClick={event => doSubmit(event, () => actions.submitAndActivate(Pruuf, false))}
						disabled={isWorking}
					/> :
					<ActionButton
						primary={activateByDefault}
						name="save_Pruuf_activate"
						text={__('Save Changes and Activate', 'code-Pruufs')}
						onClick={event => doSubmit(event, () => actions.submitAndActivate(Pruuf, true))}
						disabled={isWorking}
					/> : ''}

		{activateByDefault ?
			<ActionButton
				name="save_Pruuf"
				text={__('Save Changes', 'code-Pruufs')}
				onClick={event => doSubmit(event, () => actions.submit(Pruuf))}
				disabled={isWorking}
			/> : ''}

		<ConfirmDialog
			open={isConfirmDialogOpen}
			title={__('Pruuf incomplete', 'code-Pruufs')}
			confirmLabel={__('Continue', 'code-Pruufs')}
			onCancel={closeDialog}
			onConfirm={() => {
				submitAction?.()
				closeDialog()
			}}
		>
			<p>
				{missingCode && missingTitle ? __('This Pruuf has no code or title. Continue?', 'code-Pruufs') :
					missingCode ? __('This Pruuf has no Pruuf code. Continue?', 'code-Pruufs') :
						missingTitle ? __('This Pruuf has no title. Continue?', 'code-Pruufs') : ''}
			</p>
		</ConfirmDialog>
	</>
}

export interface ActionButtonProps extends PruufActionsProps {
	Pruuf: Pruuf
	isWorking: boolean
}

export const ActionButtons: React.FC<ActionButtonProps> = ({ Pruuf, isWorking, ...actionsProps }) => {
	const actions = usePruufActions({ ...actionsProps })
	const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false)

	return (
		<p className="submit">
			<SubmitButton actions={actions} Pruuf={Pruuf} isWorking={isWorking} />

			{Pruuf.id ?
				<>
					<ActionButton
						name="export_Pruuf"
						text={__('Export', 'code-Pruufs')}
						onClick={() => actions.export(Pruuf)}
						disabled={isWorking}
					/>

					{window.CODE_Pruufs_EDIT?.enableDownloads ?
						<ActionButton
							name="export_Pruuf_code"
							text={__('Export Code', 'code-Pruufs')}
							onClick={() => actions.exportCode(Pruuf)}
							disabled={isWorking}
						/> : ''}

					<ActionButton
						name="delete_Pruuf"
						text={__('Delete', 'code-Pruufs')}
						onClick={() => setIsDeleteDialogOpen(true)}
						disabled={isWorking}
					/>
				</> : ''}

			{isWorking ? <Spinner /> : ''}

			<ConfirmDialog
				open={isDeleteDialogOpen}
				title={__('Permanently delete?', 'code-Pruufs')}
				confirmLabel={__('Delete', 'code-Pruuf')}
				confirmButtonClassName="is-destructive"
				onCancel={() => setIsDeleteDialogOpen(false)}
				onConfirm={() => {
					setIsDeleteDialogOpen(false)
					actions.delete(Pruuf)
				}}
			>
				<p>
					{__('You are about to permanently delete this Pruuf.', 'code-Pruufs')}{' '}
					{__('Are you sure?', 'code-Pruufs')}
				</p>
				<p><strong>{__('This action cannot be undone.', 'code-Pruufs')}</strong></p>
			</ConfirmDialog>
		</p>
	)
}
