import { Pruuf } from '../types/Pruuf'
import { updatePruuf } from './requests'

/**
 * Update the priority of a Pruuf
 */
export const updatePruufPriority = (element: HTMLInputElement) => {
	const row = element.parentElement?.parentElement
	const Pruuf: Partial<Pruuf> = { priority: parseFloat(element.value) }
	if (row) {
		updatePruuf('priority', row, Pruuf)
	} else {
		console.error('Could not update Pruuf information.', Pruuf, row)
	}
}


export const handlePruufPriorityChanges = () => {
	for (const field of document.getElementsByClassName('Pruuf-priority') as HTMLCollectionOf<HTMLInputElement>) {
		field.addEventListener('input', () => updatePruufPriority(field))
		field.disabled = false
	}
}
