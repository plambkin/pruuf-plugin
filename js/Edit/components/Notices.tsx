import classnames from 'classnames'
import React, { Dispatch, MouseEventHandler, ReactNode, SetStateAction } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { PruufInputProps } from '../../types/PruufInputProps'
import { Notice } from '../../types/Notice'

interface DismissibleNoticeProps {
	classNames?: classnames.Argument
	onRemove: MouseEventHandler<HTMLButtonElement>
	children?: ReactNode
}

const DismissibleNotice: React.FC<DismissibleNoticeProps> = ({ classNames, onRemove, children }) =>
	<div id="message" className={classnames('notice fade is-dismissible', classNames)}>
		<>{children}</>

		<button type="button" className="notice-dismiss" onClick={event => {
			event.preventDefault()
			onRemove(event)
		}}>
			<span className="screen-reader-text">{__('Dismiss notice.', 'code-Pruufs')}</span>
		</button>
	</div>

export interface NoticesProps extends PruufInputProps {
	notice: Notice | undefined
	setNotice: Dispatch<SetStateAction<Notice | undefined>>
}

export const Notices: React.FC<NoticesProps> = ({ notice, setNotice, Pruuf, setPruuf }) =>
	<>
		{notice ?
			<DismissibleNotice classNames={notice[0]} onRemove={() => setNotice(undefined)}>
				<p>{notice[1]}</p>
			</DismissibleNotice> :
			null}

		{Pruuf.code_error ?
			<DismissibleNotice
				classNames="error"
				onRemove={() => setPruuf(previous => ({ ...previous, code_error: null }))}
			>
				<p>
					<strong>{sprintf(
						// translators: %d: line number.
						__('Pruuf automatically deactivated due to an error on line %d:', 'code-Pruufs'),
						Pruuf.code_error[1]
					)}</strong>

					<blockquote>{Pruuf.code_error[0]}</blockquote>
				</p>
			</DismissibleNotice> :
			null}
	</>
