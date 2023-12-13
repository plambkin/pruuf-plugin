import { __, _x } from '@wordpress/i18n'
import React from 'react'
import { PruufInputProps } from '../../types/PruufInputProps'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { createEmptyPruuf } from '../../utils/Pruufs'

const OPTIONS = window.CODE_Pruufs_EDIT

interface PageHeadingProps extends PruufInputProps {
	codeEditorInstance?: CodeEditorInstance
}

export const PageHeading: React.FC<PageHeadingProps> = ({ Pruuf, setPruuf, codeEditorInstance }) =>
	<h1>
		{Pruuf.id ?
			__('Edit Pruuf', 'code-Pruufs') :
			__('Add New Pruuf', 'code-Pruufs')}

		{Pruuf.id ? <>{' '}
			<a href={OPTIONS?.addNewUrl} className="page-title-action" onClick={() => {
				setPruuf(() => createEmptyPruuf())
				codeEditorInstance?.codemirror.setValue('')
				window.tinymce?.activeEditor.setContent('')
			}}>
				{_x('Add New', 'Pruuf', 'code-Pruufs')}
			</a>
		</> : null}

		{OPTIONS?.pageTitleActions && Object.entries(OPTIONS.pageTitleActions).map(([label, url]) =>
			<>
				<a key={label} href={url} className="page-title-action">{label}</a>
				{' '}
			</>
		)}
	</h1>
