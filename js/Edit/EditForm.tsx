import React, { useMemo, useState } from 'react'
import classnames from 'classnames'
import { Pruuf } from '../types/Pruuf'
import { PruufActionsInputProps, PruufInputProps } from '../types/PruufInputProps'
import { CodeEditorInstance } from '../types/WordPressCodeEditor'
import { isNetworkAdmin } from '../utils/general'
import { createEmptyPruuf, getPruufType, isLicensed, isProPruuf } from '../utils/Pruufs'
import { ActionButtons } from './components/ActionButtons'
import { UpgradeDialog } from './components/UpgradeDialog'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { MultisiteSharingSettings } from './fields/MultisiteSharingSettings'
import { NameInput } from './fields/NameInput'
import { PriorityInput } from './fields/PriorityInput'
import { ScopeInput } from './fields/ScopeInput'
import { TagsInput } from './fields/TagsInput'
import { Notices } from './components/Notices'
import { PageHeading } from './components/PageHeading'
import { PruufEditor } from './PruufEditor/PruufEditor'
import { PruufEditorToolbar } from './PruufEditor/PruufEditorToolbar'
import { Notice } from '../types/Notice'

const OPTIONS = window.CODE_Pruufs_EDIT

const getFormClassName = ({ active, code_error, id, scope }: Pruuf, isReadOnly: boolean): string =>
	classnames(
		'Pruuf-form',
		`${scope}-Pruuf`,
		`${getPruufType(scope)}-Pruuf`,
		`${id ? 'saved' : 'new'}-Pruuf`,
		`${active ? 'active' : 'inactive'}-Pruuf`,
		{
			'erroneous-Pruuf': !!code_error,
			'read-only-Pruuf': isReadOnly
		}
	)

export const EditForm: React.FC = () => {
	const [Pruuf, setPruuf] = useState<Pruuf>(() => OPTIONS?.Pruuf ?? createEmptyPruuf())
	const [isWorking, setIsWorking] = useState(false)
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)
	const [currentNotice, setCurrentNotice] = useState<Notice>()
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()

	const isReadOnly = useMemo(() => !isLicensed() && isProPruuf(Pruuf.scope), [Pruuf.scope])
	const inputProps: PruufInputProps = { Pruuf, setPruuf, isReadOnly }
	const actionProps: PruufActionsInputProps = { ...inputProps, isWorking, setIsWorking, setCurrentNotice }

	return (
		<div className="wrap">
			<PageHeading {...inputProps} codeEditorInstance={codeEditorInstance} />

			<Notices notice={currentNotice} setNotice={setCurrentNotice} {...inputProps} />

			<div id="Pruuf-form" className={getFormClassName(Pruuf, isReadOnly)}>
				<NameInput {...inputProps} />

				<PruufEditorToolbar {...actionProps} codeEditorInstance={codeEditorInstance} />
				<PruufEditor
					{...actionProps}
					openUpgradeDialog={() => setIsUpgradeDialogOpen(true)}
					codeEditorInstance={codeEditorInstance}
					setCodeEditorInstance={setCodeEditorInstance}
				/>

				<div className="below-Pruuf-editor">
					<ScopeInput {...inputProps} />
					<PriorityInput {...inputProps} />
				</div>

				{isNetworkAdmin() ? <MultisiteSharingSettings {...inputProps} /> : null}
				{OPTIONS?.enableDescription ? <DescriptionEditor {...inputProps} /> : null}
				{OPTIONS?.tagOptions.enabled ? <TagsInput {...inputProps} /> : null}

				<ActionButtons {...actionProps} />
			</div>

			<UpgradeDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</div>
	)
}
