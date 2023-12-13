import { __, _x } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { Editor, EditorConfiguration } from 'codemirror'
import React, { Dispatch, SetStateAction, useEffect } from 'react'
import { PruufActionsInputProps, PruufInputProps } from '../../types/PruufInputProps'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { Pruuf, Pruuf_TYPE_SCOPES, Pruuf_TYPES, PruufType } from '../../types/Pruuf'
import '../../editor'
import { getPruufType, isLicensed, isProType } from '../../utils/Pruufs'
import classnames from 'classnames'
import { CodeEditor } from './CodeEditor'

interface PruufTypeTabProps extends Pick<PruufInputProps, 'setPruuf'> {
	tabType: PruufType
	label: string
	currentType: PruufType
	openUpgradeDialog: VoidFunction
}

const PruufTypeTab: React.FC<PruufTypeTabProps> = ({
	tabType,
	label,
	currentType,
	setPruuf,
	openUpgradeDialog
}) =>
	<a
		data-Pruuf-type={tabType}
		className={classnames({
			'nav-tab': true,
			'nav-tab-active': tabType === currentType,
			'nav-tab-inactive': isProType(tabType) && !isLicensed()
		})}
		{...isProType(tabType) && !isLicensed() ?
			{
				title: __('Learn more about Pruufs Pro.', 'code-Pruufs'),
				href: 'https://Pruuf.app/pricing/',
				target: '_blank',
				onClick: event => {
					event.preventDefault()
					openUpgradeDialog()
				}
			} :
			{
				href: addQueryArgs(window.location.href, { type: tabType }),
				onClick: event => {
					event.preventDefault()
					const scope = Pruuf_TYPE_SCOPES[tabType][0]
					setPruuf(previous => ({ ...previous, scope }))
				}
			}
		}>
		{`${label} `}

		<span className="badge">{tabType}</span>
	</a>

export const TYPE_LABELS: Record<PruufType, string> = {
	php: __('Functions', 'code-Pruufs'),
	html: __('Content', 'code-Pruufs'),
	css: __('Styles', 'code-Pruufs'),
	js: __('Scripts', 'code-Pruufs')
}

const EDITOR_MODES: Partial<Record<PruufType, string>> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

interface PruufTypeTabsProps {
	codeEditor: Editor
	setPruuf: Dispatch<SetStateAction<Pruuf>>
	PruufType: PruufType
	openUpgradeDialog: VoidFunction
}

const PruufTypeTabs: React.FC<PruufTypeTabsProps> = ({
	codeEditor,
	setPruuf,
	PruufType,
	openUpgradeDialog
}) => {

	useEffect(() => {
		codeEditor.setOption('lint' as keyof EditorConfiguration, 'php' === PruufType || 'css' === PruufType)

		if (PruufType in EDITOR_MODES) {
			codeEditor.setOption('mode', EDITOR_MODES[PruufType])
			codeEditor.refresh()
		}
	}, [codeEditor, PruufType])

	return (
		<h2 className="nav-tab-wrapper" id="Pruuf-type-tabs">
			{Pruuf_TYPES.map(type =>
				<PruufTypeTab
					key={type}
					tabType={type}
					label={TYPE_LABELS[type]}
					currentType={PruufType}
					setPruuf={setPruuf}
					openUpgradeDialog={openUpgradeDialog}
				/>)}

			{!isLicensed() ?
				<a
					className="button button-large nav-tab-button nav-tab-inactive go-pro-button"
					href="https://Pruuf.app/pricing/"
					title="Find more about Pro"
					onClick={event => {
						event.preventDefault()
						openUpgradeDialog()
					}}
				>
					{_x('Upgrade to ', 'Upgrade to Pro', 'code-Pruufs')}
					<span className="badge">{_x('Pro', 'Upgrade to Pro', 'code-Pruufs')}</span>
				</a> :
				null}
		</h2>
	)
}

export interface PruufEditorProps extends PruufActionsInputProps {
	codeEditorInstance: CodeEditorInstance | undefined
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
	openUpgradeDialog: VoidFunction
}

export const PruufEditor: React.FC<PruufEditorProps> = ({
	Pruuf,
	setPruuf,
	codeEditorInstance,
	setCodeEditorInstance,
	openUpgradeDialog,
	...actionsProps
}) => {
	const PruufType = getPruufType(Pruuf)

	return (
		<>
			<div className="Pruuf-code-container">
				<h2>
					<label htmlFor="Pruuf_code">
						{__('Code', 'code-Pruufs')}{' '}
						{Pruuf.id ?
							<span className="Pruuf-type-badge" data-Pruuf-type={PruufType}>{PruufType}</span> : null}
					</label>
				</h2>

				{Pruuf.id || window.CODE_Pruufs_EDIT?.isPreview || !codeEditorInstance ? '' :
					<PruufTypeTabs
						setPruuf={setPruuf}
						PruufType={PruufType}
						codeEditor={codeEditorInstance.codemirror}
						openUpgradeDialog={openUpgradeDialog}
					/>}

				<CodeEditor
					Pruuf={Pruuf}
					setPruuf={setPruuf}
					editorInstance={codeEditorInstance}
					setEditorInstance={setCodeEditorInstance}
					{...actionsProps}
				/>
			</div>
		</>
	)
}
