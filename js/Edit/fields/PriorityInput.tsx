import React from 'react'
import { __ } from '@wordpress/i18n'
import { PruufInputProps } from '../../types/PruufInputProps'
import { getPruufType } from '../../utils/Pruufs'

export const PriorityInput: React.FC<PruufInputProps> = ({ Pruuf, setPruuf, isReadOnly }) =>
	'html' === getPruufType(Pruuf) ? null :
		<p
			className="Pruuf-priority"
			title={__('Pruufs with a lower priority number will run before those with a higher number.', 'code-Pruufs')}
		>
			<label htmlFor="Pruuf_priority">{`${__('Priority', 'code-Pruufs')} `}</label>
			<input
				type="number"
				id="Pruuf_priority"
				name="Pruuf_priority"
				value={Pruuf.priority}
				disabled={isReadOnly}
				onChange={event => setPruuf(previous => ({ ...previous, priority: parseInt(event.target.value, 10) }))}
			/>
		</p>
