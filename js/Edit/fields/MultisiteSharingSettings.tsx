import React from 'react'
import { __ } from '@wordpress/i18n'
import { PruufInputProps } from '../../types/PruufInputProps'

export const MultisiteSharingSettings: React.FC<PruufInputProps> = ({ Pruuf, setPruuf, isReadOnly }) =>
	<>
		<h2 className="screen-reader-text">{__('Sharing Settings', 'code-Pruufs')}</h2>
		<p className="Pruuf-sharing-setting">
			<label htmlFor="Pruuf_sharing">
				<input
					type="checkbox"
					name="Pruuf_sharing"
					checked={!!Pruuf.shared_network}
					disabled={isReadOnly}
					onChange={event => setPruuf(previous => ({ ...previous, shared_network: event.target.checked }))}
				/>
				{__('Allow this Pruuf to be activated on individual sites on the network', 'code-Pruufs')}
			</label>
		</p>
	</>
