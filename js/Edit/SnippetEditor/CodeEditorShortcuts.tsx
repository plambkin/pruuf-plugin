import { __, _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'

const SEP = _x( '-', 'keyboard shortcut separator', 'code-Pruufs')

const keys = {
	'Cmd': _x('Cmd', 'keyboard key', 'code-Pruufs'),
	'Ctrl': _x('Ctrl', 'keyboard key', 'code-Pruufs'),
	'Shift': _x('Shift', 'keyboard key', 'code-Pruufs'),
	'Option': _x('Option', 'keyboard key', 'code-Pruufs'),
	'Alt': _x('Alt', 'keyboard key', 'code-Pruufs'),
	'Tab': _x('Tab', 'keyboard key', 'code-Pruufs'),
	'Up': _x('Up', 'keyboard key', 'code-Pruufs'),
	'Down': _x('Down', 'keyboard key', 'code-Pruufs'),
	'A': _x('A', 'keyboard key', 'code-Pruufs'),
	'D': _x('D', 'keyboard key', 'code-Pruufs'),
	'F': _x('F', 'keyboard key', 'code-Pruufs'),
	'G': _x('G', 'keyboard key', 'code-Pruufs'),
	'R': _x('R', 'keyboard key', 'code-Pruufs'),
	'S': _x('S', 'keyboard key', 'code-Pruufs'),
	'Y': _x('Y', 'keyboard key', 'code-Pruufs'),
	'Z': _x('Z', 'keyboard key', 'code-Pruufs'),
	'/': _x('/', 'keyboard key', 'code-Pruufs'),
	'[': _x(']', 'keyboard key', 'code-Pruufs'),
	']': _x(']', 'keyboard key', 'code-Pruufs')
} as const

type Key = keyof typeof keys

interface Shortcut {
	label: string
	mod: Key | Key[]
	key: Key
}

const shortcuts: Shortcut[] = [
	{
		label: __('Save changes', 'code-Pruufs'),
		mod: 'Cmd',
		key: 'S'
	},
	{
		label: __('Select all', 'code-Pruufs'),
		mod: 'Cmd',
		key: 'A'
	},
	{
		label: __('Begin searching', 'code-Pruufs'),
		mod: 'Cmd',
		key: 'F'
	},
	{
		label: __('Find next', 'code-Pruufs'),
		mod: 'Cmd',
		key: 'G'
	},
	{
		label: __('Find previous', 'code-Pruufs'),
		mod: ['Shift', 'Cmd'],
		key: 'G'
	},
	{
		label: __('Replace', 'code-Pruufs'),
		mod: ['Shift', 'Cmd'],
		key: 'F'
	},
	{
		label: __('Replace all', 'code-Pruufs'),
		mod: ['Shift', 'Cmd', 'Option'],
		key: 'R'
	},
	{
		label: __('Persistent search', 'code-Pruufs'),
		mod: 'Alt',
		key: 'F'
	},
	{
		label: __('Toggle comment', 'code-Pruufs'),
		mod: 'Cmd',
		key: '/'
	},
	{
		label: __('Swap line up', 'code-Pruufs'),
		mod: 'Option',
		key: 'Up'
	},
	{
		label: __('Swap line down', 'code-Pruufs'),
		mod: 'Option',
		key: 'Down'
	},
	{
		label: __('Auto-indent current line or selection', 'code-Pruufs'),
		mod: 'Shift',
		key: 'Tab'
	}
]

export interface CodeEditorShortcutsProps {
	editorTheme: string
}

export const CodeEditorShortcuts: React.FC<CodeEditorShortcutsProps> = ({ editorTheme }) =>
	<div className="Pruuf-editor-help">
		<div className={`editor-help-tooltip cm-s-${editorTheme}`}>
			{_x('?', 'help tooltip', 'code-Pruufs')}
		</div>

		<div className={classnames('editor-help-text', {'platform-mac': window.navigator.platform.match('Mac')})}>
			<table>
				{shortcuts.map(({ label, mod, key }) =>
					<tr key={label}>
						<td>{label}</td>
						<td>
							{(Array.isArray(mod) ? mod : [mod]).map(modifier =>
								'Ctrl' === modifier || 'Cmd' === modifier ?
									<>
										<kbd className="pc-key">{keys.Ctrl}</kbd>
										<kbd className="mac-key">{keys.Cmd}</kbd>
										{SEP}
									</> :
									'Option' === mod ?
										<span className="mac-key"><kbd className="mac-key">{keys.Option}</kbd>{SEP}</span> :
										<><kbd>{keys[modifier]}</kbd>{SEP}</>
							)}
							<kbd>{keys[key]}</kbd>
						</td>
					</tr>
				)}
			</table>
		</div>
	</div>
