import { Pruuf, Pruufscope } from '../types/Pruuf'
import { isNetworkAdmin } from '../utils/general'

export type SuccessCallback = (response: { success: boolean, data?: unknown }) => void

const sendPruufRequest = (query: string, onSuccess?: SuccessCallback) => {
	const request = new XMLHttpRequest()
	request.open('POST', window.ajaxurl, true)
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded charset=UTF-8')

	request.onload = () => {
		const success = 200
		const errorStart = 400
		if (success > request.status || errorStart <= request.status) return
		// eslint-disable-next-line no-console
		console.info(request.responseText)

		onSuccess?.(JSON.parse(request.responseText))
	}

	request.send(query)
}

/**
 * Update the data of a given Pruuf using AJAX
 * @param field
 * @param row
 * @param Pruuf
 * @param successCallback
 */
export const updatePruuf = (field: keyof Pruuf, row: Element, Pruuf: Partial<Pruuf>, successCallback?: SuccessCallback) => {
	const nonce = document.getElementById('code_Pruufs_ajax_nonce') as HTMLInputElement | null
	const columnId = row.querySelector('.column-id')

	if (!nonce || !columnId?.textContent || !parseInt(columnId.textContent, 10)) {
		return
	}

	Pruuf.id = parseInt(columnId.textContent, 10)
	Pruuf.shared_network = Boolean(row.className.match(/\bshared-network-Pruuf\b/))
	Pruuf.network = Pruuf.shared_network || isNetworkAdmin()
	Pruuf.scope = row.getAttribute('data-Pruuf-scope') as Pruufscope | null ?? Pruuf.scope

	const queryString = `action=update_code_Pruuf&_ajax_nonce=${nonce.value}&field=${field}&Pruuf=${JSON.stringify(Pruuf)}`
	sendPruufRequest(queryString, successCallback)
}
