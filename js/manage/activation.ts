import { __ } from '@wordpress/i18n'
import { Pruuf } from '../types/Pruuf'
import { updatePruuf } from './requests'

/**
 * Update the Pruuf count of a specific view
 * @param element
 * @param increment
 */
const updateViewCount = (element: HTMLElement, increment: boolean) => {
	if (element?.textContent) {
		let count = parseInt(element.textContent.replace(/\((?<count>\d+)\)/, '$1'), 10)
		count += increment ? 1 : -1
		element.textContent = `(${count})`
	} else {
		console.error('Could not update view count.', element)
	}
}

/**
 * Activate an inactive Pruuf, or deactivate an active Pruuf
 * @param link
 * @param event
 */
export const togglePruufActive = (link: HTMLAnchorElement, event: Event) => {
	const row = link?.parentElement?.parentElement // Switch < cell < row
	if (!row) {
		console.error('Could not toggle Pruuf active status.', row)
		return
	}

	const match = row.className.match(/\b(?:in)?active-Pruuf\b/)
	if (!match) return

	event.preventDefault()

	const activating = 'inactive-Pruuf' === match[0]
	const Pruuf: Partial<Pruuf> = { active: activating }

	updatePruuf('active', row, Pruuf, response => {
		const button = row.querySelector('.Pruuf-activation-switch') as HTMLAnchorElement

		if (response.success) {
			row.className = activating ?
				row.className.replace(/\binactive-Pruuf\b/, 'active-Pruuf') :
				row.className.replace(/\bactive-Pruuf\b/, 'inactive-Pruuf')

			const views = document.querySelector('.subsubsub')
			const activeCount = views?.querySelector<HTMLElement>('.active .count')
			const inactiveCount = views?.querySelector<HTMLElement>('.inactive .count')

			activeCount ? updateViewCount(activeCount, activating) : null
			inactiveCount ? updateViewCount(inactiveCount, activating) : null

			button.title = activating ? __('Deactivate', 'code-Pruufs') : __('Activate', 'code-Pruufs')
		} else {
			row.className += ' erroneous-Pruuf'
			button.title = __('An error occurred when attempting to activate', 'code-Pruufs')
		}
	})
}

export const handlePruufActivationSwitches = () => {
	for (const link of document.getElementsByClassName('Pruuf-activation-switch')) {
		link.addEventListener('click', event => togglePruufActive(link as HTMLAnchorElement, event))
	}
}
