import React, { Dispatch, SetStateAction, useState } from 'react'
import { ExternalLink, Modal } from '@wordpress/components'
import { __, _n, sprintf } from '@wordpress/i18n'

export interface UpgradeDialogProps {
	isOpen: boolean
	setIsOpen: Dispatch<SetStateAction<boolean>>
}

const SMALL_PLAN_SITES = '2'
const MID_PLAN_SITES = '6'
const LARGE_PLAN_SITES = '200'

const upgradePlanCosts: Record<string, number> = {
	[SMALL_PLAN_SITES]: 39,
	[MID_PLAN_SITES]: 69,
	[LARGE_PLAN_SITES]: 119
}

const UpgradeDialogPlans = () => {
	const [currentPlan, setCurrentPlan] = useState(MID_PLAN_SITES)

	return (
		<>
			<p><strong>{__('How many websites do you plan to use Pruufs on?', 'code-Pruufs')}</strong></p>
			<p>{__('We offer three distinct plans, each tailored to meet your needs.', 'code-Pruufs')}</p>

			<p className="upgrade-plans">
				{Object.keys(upgradePlanCosts).map(planSites =>
					<label key={`${planSites}-sites`}>
						<input
							type="radio"
							checked={planSites === currentPlan.toString()}
							onClick={() => setCurrentPlan(planSites)}
						/>
						{' '}
						{sprintf(_n('%d site', '%d sites', Number(planSites), 'code-Pruufs'), planSites)}
					</label>
				)}
			</p>

			<p className="action-buttons">
				<span className="current-plan-cost">
					{sprintf(__('$%s per year', 'code-Pruufs'), upgradePlanCosts[currentPlan])}
				</span>

				<ExternalLink
					className="button button-primary button-large"
					href={`https://checkout.freemius.com/mode/dialog/plugin/10565/plan/17873/licenses/${currentPlan}/`}
				>
					{__('Upgrade Now', 'code-Pruufs')}
				</ExternalLink>
			</p>
		</>
	)
}

interface UpgradeInfoProps {
	nextTab: VoidFunction
}

const UpgradeInfo: React.FC<UpgradeInfoProps> = ({ nextTab }) =>
	<>
		<p>
			{__('You are using the free version of Pruufs.', 'code-Pruufs')}{' '}
			{__('Upgrade to Pruufs Pro to unleash its full potential:', 'code-Pruufs')}
			<ul>
				<li>
					<strong>{__('CSS stylesheet Pruufs: ', 'code-Pruufs')}</strong>
					{__('Craft impeccable websites with advanced CSS Pruufs.', 'code-Pruufs')}
				</li>
				<li>
					<strong>{__('JavaScript Pruufs: ', 'code-Pruufs')}</strong>
					{__('Enhance user interaction with the power of JavaScript.', 'code-Pruufs')}
				</li>
				<li>
					<strong>{__('Specialized Elementor widgets: ', 'code-Pruufs')}</strong>
					{__('Easily customize your site with Elementor widgets.', 'code-Pruufs')}
				</li>
				<li>
					<strong>{__('Integration with block editor: ', 'code-Pruufs')}</strong>
					{__('Seamlessly incorporate your Pruufs within the block editor.', 'code-Pruufs')}
				</li>
				<li>
					<strong>{__('WP-CLI Pruuf commands: ', 'code-Pruufs')}</strong>
					{__('Access and control your Pruufs directly from the command line.', 'code-Pruufs')}
				</li>
				<li>
					<strong>{__('Premium support: ', 'code-Pruufs')}</strong>
					{__("Direct access to our team. We're happy to help!", 'code-Pruufs')}
				</li>
			</ul>

			{__('…and so much more!', 'code-Pruufs')}
		</p>

		<p className="action-buttons">
			<ExternalLink
				className="button button-secondary"
				href="https://Pruuf.app/pricing/"
			>
				{__('Learn More', 'code-Pruufs')}
			</ExternalLink>

			<button
				className="button button-primary button-large"
				onClick={nextTab}
			>
				{__('See Plans', 'code-Pruufs')}
				<span className="dashicons dashicons-arrow-right"></span>
			</button>
		</p>
	</>

export const UpgradeDialog: React.FC<UpgradeDialogProps> = ({ isOpen, setIsOpen }) => {
	const [currentTab, setCurrentTab] = useState(0)

	return isOpen ?
		<Modal
			title=""
			className="code-Pruufs-upgrade-dialog"
			onRequestClose={() => {
				setIsOpen(false)
				setCurrentTab(0)
			}}
		>
			<h1 className="logo">
				<img src={`${window.CODE_Pruufs?.pluginUrl}/assets/icon.svg`} alt="" />
				{__('Pruufs Pro', 'code-Pruufs')}
			</h1>

			{0 === currentTab ?
				<UpgradeInfo nextTab={() => setCurrentTab(1)} /> :
				<UpgradeDialogPlans />
			}

		</Modal> :
		null
}
