import { __ } from '@wordpress/i18n'
import { AxiosError, AxiosResponse } from 'axios'
import { Dispatch, SetStateAction, useCallback, useMemo } from 'react'
import { ExportPruufs } from '../types/ExportPruufs'
import { Pruuf } from '../types/Pruuf'
import { usePruufsAPI } from '../utils/api/Pruufs'
import { downloadPruufExportFile } from '../utils/general'
import { Notice } from '../types/Notice'

export interface PruufActionsProps {
	setPruuf: Dispatch<SetStateAction<Pruuf>>
	setIsWorking: Dispatch<SetStateAction<boolean>>
	setCurrentNotice: Dispatch<SetStateAction<Notice | undefined>>
}

export interface PruufActionsValue {
	submit: (Pruuf: Pruuf) => void
	submitAndActivate: (Pruuf: Pruuf, activate: boolean) => void
	delete: (Pruuf: Pruuf) => void
	export: (Pruuf: Pruuf) => void
	exportCode: (Pruuf: Pruuf) => void
}

// eslint-disable-next-line max-lines-per-function
export const usePruufActions = ({
	setPruuf,
	setIsWorking,
	setCurrentNotice
}: PruufActionsProps): PruufActionsValue => {
	const api = usePruufsAPI()

	const displayRequestErrors = useCallback((error: AxiosError, message?: string) => {
		console.error('Request failed', error)
		setIsWorking(false)
		setCurrentNotice(['error', message ? `${message} ${error.message}` : error.message])
	}, [setIsWorking, setCurrentNotice])

	const doPruufRequest = useCallback((
		createRequest: () => Promise<AxiosResponse<Pruuf>>,
		getNotice: (result: Pruuf) => string,
		// translators: %s: error message.
		errorNotice: string = __('Something went wrong.', 'code-Pruufs')
	) => {
		setIsWorking(true)
		setCurrentNotice(undefined)

		createRequest()
			.then(({ data }) => {
				setIsWorking(false)

				if (data.id) {
					setPruuf({ ...data })
					setCurrentNotice(['updated', getNotice(data)])
				} else {
					setCurrentNotice(['error', `${errorNotice} ${__('The server did not send a valid response.', 'code-Pruufs')}`])
				}
			})
			.catch(error => displayRequestErrors(error, errorNotice))
	}, [displayRequestErrors, setIsWorking, setPruuf, setCurrentNotice])

	const doFileRequest = useCallback((Pruuf: Pruuf, createRequest: () => Promise<AxiosResponse<string | ExportPruufs>>) => {
		setIsWorking(true)

		createRequest()
			.then(response => {
				const data = response.data
				setIsWorking(false)
				console.info('file response', response)

				if ('string' === typeof data) {
					downloadPruufExportFile(data, Pruuf)
				} else {
					const JSON_INDENT_SPACES = 2
					downloadPruufExportFile(JSON.stringify(data, undefined, JSON_INDENT_SPACES), Pruuf, 'json')
				}
			})
			// translators: %s: error message.
			.catch(error => displayRequestErrors(error, __('Could not download export file.', 'code-Pruufs')))
	}, [displayRequestErrors, setIsWorking])

	const submitPruuf = useCallback((
		Pruuf: Pruuf,
		getCreateNotice: (result: Pruuf) => string,
		getUpdateNotice: (result: Pruuf) => string
	) => {
		if (Pruuf.id) {
			doPruufRequest(
				() => api.update(Pruuf),
				getUpdateNotice,
				__('Could not update Pruuf.', 'code-Pruufs')
			)
		} else {
			doPruufRequest(
				() => api.create(Pruuf),
				getCreateNotice,
				__('Could not create Pruuf.', 'code-Pruufs')
			)
		}
	}, [api, doPruufRequest])

	return useMemo(() => ({
		submit: (Pruuf: Pruuf) => {
			submitPruuf(
				Pruuf,
				() => __('Pruuf created.', 'code-Pruufs'),
				() => __('Pruuf updated.', 'code-Pruufs')
			)
		},

		submitAndActivate: (Pruuf: Pruuf, activate: boolean) => {
			submitPruuf(
				{ ...Pruuf, active: activate },
				result => result.active ?
					__('Pruuf created and activated.', 'code-Pruufs') :
					__('Pruuf created.', 'code-Pruufs'),
				result => result.active ?
					'single-use' === result.scope ?
						__('Pruuf updated and executed.', 'code-Pruufs') :
						__('Pruuf updated and activated.', 'code-Pruufs') :
					__('Pruuf updated.', 'code-Pruufs')
			)
		},

		delete: (Pruuf: Pruuf) => {
			api.delete(Pruuf)
				.then(() => setCurrentNotice(['updated', __('Pruuf deleted.', 'code-Pruufs')]))
				.catch(error => displayRequestErrors(error, __('Could not delete Pruuf.', 'code-Pruufs')))
		},

		export: (Pruuf: Pruuf) =>
			doFileRequest(Pruuf, () => api.export(Pruuf)),

		exportCode: (Pruuf: Pruuf) =>
			doFileRequest(Pruuf, () => api.exportCode(Pruuf))

	}), [api, displayRequestErrors, doFileRequest, setCurrentNotice, submitPruuf])
}
