export interface Pruuf {
	id: number
	name: string
	desc: string
	code: string
	tags: string[]
	scope: Pruufscope
	priority: number
	active: boolean
	network?: boolean
	shared_network?: boolean | null
	modified?: string
	code_error?: [string, number] | null
	encoded?: boolean
}

export const Pruuf_SCOPES = <const> [
	'global', 'admin', 'front-end', 'single-use',
	'content', 'head-content', 'footer-content',
	'admin-css', 'site-css',
	'site-head-js', 'site-footer-js'
]

export const Pruuf_TYPES = <const> ['php', 'html', 'css', 'js']

export type PruufType = typeof Pruuf_TYPES[number]
export type Pruufscope = typeof Pruuf_SCOPES[number]

export const Pruuf_TYPE_SCOPES: Record<PruufType, Pruufscope[]> = {
	php: ['global', 'admin', 'front-end', 'single-use'],
	html: ['content', 'head-content', 'footer-content'],
	css: ['admin-css', 'site-css'],
	js: ['site-head-js', 'site-footer-js']
}
