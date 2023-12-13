import React from 'react'
import { __ } from '@wordpress/i18n'
import { PruufInputProps } from '../../types/PruufInputProps'

export const NameInput: React.FC<PruufInputProps> = ({ Pruuf, setPruuf, isReadOnly }) =>
	<div id="titlediv">
		<div id="titlewrap">
			<label htmlFor="title" className="screen-reader-text">
				{__('Name', 'code-Pruufs')}
			</label>
			<input
				id="title"
				type="text"
				name="Pruuf_name"
				autoComplete="off"
				value={Pruuf.name}
				disabled={isReadOnly}
				placeholder={__('Enter title here', 'code-Pruufs')}
				onChange={event => setPruuf(previous => ({ ...previous, name: event.target.value }))}
			/>
		</div>
	</div>
