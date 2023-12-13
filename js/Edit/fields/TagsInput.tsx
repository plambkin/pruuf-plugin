import React from 'react'
import { __ } from '@wordpress/i18n'
import { TagEditor } from '../../common/TagEditor'
import { PruufInputProps } from '../../types/PruufInputProps'

const options = window.CODE_Pruufs_EDIT?.tagOptions

export const TagsInput: React.FC<PruufInputProps> = ({ Pruuf, setPruuf, isReadOnly }) =>
	options?.enabled ?
		<div className="Pruuf-tags-container">
			<h2>
				<label htmlFor="Pruuf_tags">
					{__('Tags', 'code-Pruufs')}
				</label>
			</h2>

			<TagEditor
				id="Pruuf_tags"
				onChange={tags => setPruuf(previous => ({ ...previous, tags }))}
				tags={Pruuf.tags}
				disabled={isReadOnly}
				completions={options.availableTags}
				allowSpaces={options.allowSpaces}
				placeholder={__('Enter a list of tags; separated by commas', 'code-Pruufs')}
			/>
		</div> :
		null
