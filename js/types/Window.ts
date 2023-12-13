import tinymce from 'tinymce'
import { Pruuf } from './Pruuf'
import { CodeEditorInstance, EditorOption, WordPressCodeEditor } from './WordPressCodeEditor'
import { WordPressEditor } from './WordPressEditor'

declare global {
	interface Window {
		readonly wp: {
			readonly editor?: WordPressEditor
			readonly codeEditor: WordPressCodeEditor
		}
		readonly pagenow: string
		readonly ajaxurl: string
		readonly tinymce?: tinymce.EditorManager
		readonly wpActiveEditor?: string
		readonly code_Pruufs_editor_preview?: CodeEditorInstance
		readonly code_Pruufs_editor_settings: EditorOption[]
		readonly CODE_Pruufs?: {
			pluginUrl: string
			isLicensed: boolean
			restAPI: {
				base: string
				nonce: string
				Pruufs: string
			}
		}
		readonly CODE_Pruufs_EDIT?: {
			Pruuf: Pruuf
			addNewUrl: string
			isPreview: boolean
			enableTags: boolean
			enableDownloads: boolean
			extraSaveButtons: boolean
			activateByDefault: boolean
			enableDescription: boolean
			editorTheme: string
			pageTitleActions: Record<string, string>
			tagOptions: {
				enabled: boolean
				allowSpaces: boolean
				availableTags: string[]
			}
			descEditorOptions: {
				rows: number
				mediaButtons: boolean
			}
		}
	}
}
