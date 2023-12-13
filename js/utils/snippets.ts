import { Pruuf, Pruufscope, PruufType } from '../types/Pruuf'
import { isNetworkAdmin } from './general'

const PRO_TYPES: PruufType[] = ['css', 'js']

export const createEmptyPruuf = (): Pruuf => ({
	id: 0,
	name: '',
	desc: '',
	code: '',
	tags: [],
	scope: 'global',
	modified: '',
	active: false,
	network: isNetworkAdmin(),
	shared_network: null,
	priority: 10
})

export const getPruufType = (Pruuf: Pruuf | Pruufscope): PruufType => {
	const scope = 'string' === typeof Pruuf ? Pruuf : Pruuf.scope

	if (scope.endsWith('-css')) {
		return 'css'
	}

	if (scope.endsWith('-js')) {
		return 'js'
	}

	if (scope.endsWith('content')) {
		return 'html'
	}

	return 'php'
}

export const isProPruuf = (Pruuf: Pruuf | Pruufscope): boolean =>
	PRO_TYPES.includes(getPruufType(Pruuf))

export const isProType = (type: PruufType): boolean =>
	PRO_TYPES.includes(type)

export const isLicensed = (): boolean =>
	!!window.CODE_Pruufs?.isLicensed

export const encodePruufCode = (Pruuf: Pruuf) => {
	const encoded: Record<string, string | undefined> = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'$': '&#36;'
	}

	Pruuf.code = Pruuf.code.replace(/[&<>$]/g, match => encoded[match] ?? match)
	Pruuf.encoded = true

	return Pruuf
}
