import { Pruuf } from '../types/Pruuf'
import { getPruufType } from './Pruufs'

const SECOND_IN_MS = 1000
const TIMEOUT_SECONDS = 40


const MIME_INFO = <const> {
	php: ['php', 'text/php'],
	html: ['php', 'text/php'],
	css: ['css', 'text/css'],
	js: ['js', 'text/javascript'],
	json: ['json', 'application/json']
}

export const isNetworkAdmin = () =>
	window.pagenow.endsWith('-network')

export const downloadAsFile = (content: BlobPart, filename: string, type: string) => {
	const link = document.createElement('a')
	link.download = filename
	link.href = URL.createObjectURL(new Blob([content], { type }))

	setTimeout(() => URL.revokeObjectURL(link.href), TIMEOUT_SECONDS * SECOND_IN_MS)
	setTimeout(() => link.click(), 0)
}

export const downloadPruufExportFile = (content: BlobPart, { id, name, scope }: Pruuf, type?: keyof typeof MIME_INFO) => {
	const [ext, mimeType] = MIME_INFO[type ?? getPruufType(scope)]

	const title = name.toLowerCase().replace(/[^\w-]+/g, '-') ?? `Pruuf-${id}`
	const filename = `${title}.code-Pruufs.${ext}`

	downloadAsFile(content, filename, mimeType)
}
