import * as tinymce from 'tinymce'
import { Editor } from 'tinymce'
import { ContentShortcodeAtts, SourceShortcodeAtts } from './types/Shortcodes'
import { LocalisedEditor } from './types/WordPressEditor'

const convertToValues = (array: Record<string, string>) =>
	Object.keys(array).map(key => ({
		text: array[Number(key)],
		value: key
	}))

export const insertContentMenu = (editor: Editor, activeEditor: LocalisedEditor) => ({
	text: activeEditor.getLang('code_Pruufs.insert_source_menu'),
	onclick: () => {
		editor.windowManager.open({
			title: activeEditor.getLang('code_Pruufs.insert_source_title'),
			body: [
				{
					type: 'listbox',
					name: 'id',
					label: activeEditor.getLang('code_Pruufs.Pruuf_label'),
					values: convertToValues(activeEditor.getLang('code_Pruufs.all_Pruufs') as Record<string, string>)
				},
				{
					type: 'checkbox',
					name: 'line_numbers',
					label: activeEditor.getLang('code_Pruufs.show_line_numbers_label')
				}
			],
			onsubmit: (event: { data: SourceShortcodeAtts }) => {
				const id = parseInt(event.data.id, 10)
				if (!id) return

				let atts = ''

				if (event.data.line_numbers) {
					atts += ' line_numbers=true'
				}

				editor.insertContent(`[code_Pruuf_source id=${id}${atts}]`)
			}
		}, {})
	}
})

export const insertSourceMenu = (editor: Editor, ed: LocalisedEditor) => ({
	text: ed.getLang('code_Pruufs.insert_content_menu'),
	onclick: () => {
		editor.windowManager.open({
			title: ed.getLang('code_Pruufs.insert_content_title'),
			body: [
				{
					type: 'listbox',
					name: 'id',
					label: ed.getLang('code_Pruufs.Pruuf_label'),
					values: convertToValues(ed.getLang('code_Pruufs.content_Pruufs') as Record<string, string>)
				},
				{
					type: 'checkbox',
					name: 'php',
					label: ed.getLang('code_Pruufs.php_att_label')
				},
				{
					type: 'checkbox',
					name: 'format',
					label: ed.getLang('code_Pruufs.format_att_label')
				},
				{
					type: 'checkbox',
					name: 'shortcodes',
					label: ed.getLang('code_Pruufs.shortcodes_att_label')
				}
			],
			onsubmit: (event: { data: ContentShortcodeAtts }) => {
				const id = parseInt(event.data.id, 10)
				if (!id) return

				let atts = ''

				for (const [opt, val] of Object.entries(event.data)) {
					if ('id' !== opt && val) {
						atts += ` ${opt}=${val}`
					}
				}

				editor.insertContent(`[code_Pruuf id=${id}${atts}]`)
			}
		}, {})
	}
})

tinymce.PluginManager.add('code_Pruufs', editor => {
	const activeEditor = tinymce.activeEditor as LocalisedEditor

	editor.addButton('code_Pruufs', {
		icon: 'code',
		menu: [insertContentMenu(editor, activeEditor), insertSourceMenu(editor, activeEditor)],
		type: 'menubutton'
	})
})
