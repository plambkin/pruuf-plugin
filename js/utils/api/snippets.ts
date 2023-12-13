import { useEffect, useMemo, useState } from 'react'
import { AxiosResponse, CreateAxiosDefaults } from 'axios'
import { addQueryArgs } from '@wordpress/url'
import { ExportPruufs } from '../../types/ExportPruufs'
import { Pruuf } from '../../types/Pruuf'
import { isNetworkAdmin } from '../general'
import { useAxios } from './axios'
import { encodePruufCode } from '../Pruufs'

const ROUTE_BASE = window.CODE_Pruufs?.restAPI.Pruufs

const AXIOS_CONFIG: CreateAxiosDefaults = {
	headers: { 'X-WP-Nonce': window.CODE_Pruufs?.restAPI.nonce }
}

export interface Pruufs {
	fetchAll: (network?: boolean | null) => Promise<AxiosResponse<Pruuf[]>>
	fetch: (PruufId: number, network?: boolean | null) => Promise<AxiosResponse<Pruuf>>
	create: (Pruuf: Pruuf) => Promise<AxiosResponse<Pruuf>>
	update: (Pruuf: Pruuf) => Promise<AxiosResponse<Pruuf>>
	delete: (Pruuf: Pruuf) => Promise<AxiosResponse<void>>
	activate: (Pruuf: Pruuf) => Promise<AxiosResponse<Pruuf>>
	deactivate: (Pruuf: Pruuf) => Promise<AxiosResponse<Pruuf>>
	export: (Pruuf: Pruuf) => Promise<AxiosResponse<ExportPruufs>>
	exportCode: (Pruuf: Pruuf) => Promise<AxiosResponse<string>>
}

const buildURL = ({ id, network }: Pruuf, action?: string) =>
	addQueryArgs(
		[ROUTE_BASE, id, action].filter(Boolean).join('/'),
		{ network: network ? true : undefined }
	)

export const usePruufsAPI = (): Pruufs => {
	const { get, post, del } = useAxios(AXIOS_CONFIG)

	return useMemo((): Pruufs => ({
		fetchAll: network =>
			get<Pruuf[]>(addQueryArgs(ROUTE_BASE, { network })),

		fetch: (PruufId, network) =>
			get<Pruuf>(addQueryArgs(`${ROUTE_BASE}/${PruufId}`, { network })),

		create: Pruuf =>
			post<Pruuf, Pruuf>(`${ROUTE_BASE}`, encodePruufCode(Pruuf)),

		update: Pruuf =>
			post<Pruuf, Pruuf>(buildURL(Pruuf), encodePruufCode(Pruuf)),

		delete: (Pruuf: Pruuf) =>
			del(buildURL(Pruuf)),

		activate: Pruuf =>
			post<Pruuf, never>(buildURL(Pruuf, 'activate')),

		deactivate: Pruuf =>
			post<Pruuf, never>(buildURL(Pruuf, 'deactivate')),

		export: Pruuf =>
			get<ExportPruufs>(buildURL(Pruuf, 'export')),

		exportCode: Pruuf =>
			get<string>(buildURL(Pruuf, 'export-code'))
	}), [get, post, del])
}

export const usePruufs = (): Pruuf[] | undefined => {
	const api = usePruufsAPI()
	const [Pruufs, setPruufs] = useState<Pruuf[]>()

	useEffect(() => {
		if (!Pruufs) {
			api.fetchAll(isNetworkAdmin())
				.then(response => setPruufs(response.data))
		}
	}, [api, Pruufs])

	return Pruufs
}
