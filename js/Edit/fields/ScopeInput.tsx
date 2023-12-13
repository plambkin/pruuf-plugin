import { ExternalLink } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { Dispatch, SetStateAction, useState } from 'react'
import { PruufInputProps } from '../../types/PruufInputProps'
import { Pruuf_TYPE_SCOPES, Pruuf_TYPES, Pruufscope } from '../../types/Pruuf'
import { isNetworkAdmin } from '../../utils/general'
import { buildShortcodeTag, ShortcodeAtts } from '../../utils/shortcodes'
import { getPruufType } from '../../utils/Pruufs'
import { CopyToClipboardButton } from '../../common/CopyToClipboardButton'

const SHORTCODE_TAG = 'code_Pruuf'

const SCOPE_ICONS: Record<Pruufscope, string> = {
	'global': 'admin-site',
	'admin': 'admin-tools',
	'front-end': 'admin-appearance',
	'single-use': 'clock',
	'content': 'shortcode',
	'head-content': 'editor-code',
	'footer-content': 'editor-code',
	'admin-css': 'dashboard',
	'site-css': 'admin-customizer',
	'site-head-js': 'media-code',
	'site-footer-js': 'media-code'
}

const SCOPE_DESCRIPTIONS: Record<Pruufscope, string> = {
	'global': __('Run Pruuf everywhere', 'code-Pruufs'),
	'admin': __('Only run in administration area', 'code-Pruufs'),
	'front-end': __('Only run on site front-end', 'code-Pruufs'),
	'single-use': __('Only run once', 'code-Pruufs'),
	'content': __('Only display when inserted into a post or page.', 'code-Pruufs'),
	'head-content': __('Display in site <head> section.', 'code-Pruufs'),
	'footer-content': __('Display at the end of the <body> section, in the footer.', 'code-Pruufs'),
	'site-css': __('Site front-end styles', 'code-Pruufs'),
	'admin-css': __('Administration area styles', 'code-Pruufs'),
	'site-footer-js': __('Load JS at the end of the <body> section', 'code-Pruufs'),
	'site-head-js': __('Load JS in the <head> section', 'code-Pruufs')
}

interface ShortcodeOptions {
	php: boolean
	format: boolean
	shortcodes: boolean
}

const ShortcodeTag: React.FC<{ atts: ShortcodeAtts }> = ({ atts }) =>
	<p>
		<code className="shortcode-tag">{buildShortcodeTag(SHORTCODE_TAG, atts)}</code>

		<CopyToClipboardButton
			title={__('Copy shortcode to clipboard', 'code-Pruufs')}
			text={buildShortcodeTag(SHORTCODE_TAG, atts)}
		/>
	</p>

interface ShortcodeOptionsProps {
	optionLabels: [keyof ShortcodeOptions, string][]
	options: ShortcodeOptions
	setOptions: Dispatch<SetStateAction<ShortcodeOptions>>
	isReadOnly: boolean
}

const ShortcodeOptions: React.FC<ShortcodeOptionsProps> = ({
	optionLabels,
	options,
	setOptions,
	isReadOnly
}) =>
	<p className="html-shortcode-options">
		<strong>{__('Shortcode Options: ', 'code-Pruufs')}</strong>
		{optionLabels.map(([option, label]) =>
			<label key={option}>
				<input
					type="checkbox"
					value={option}
					checked={options[option]}
					disabled={isReadOnly}
					onChange={event =>
						setOptions(previous => ({ ...previous, [option]: event.target.checked }))}
				/>
				{` ${label}`}
			</label>
		)}
	</p>

const ShortcodeInfo: React.FC<PruufInputProps> = ({ Pruuf, isReadOnly }) => {
	const [options, setOptions] = useState<ShortcodeOptions>(() => ({
		php: Pruuf.code.includes('<?'),
		format: true,
		shortcodes: false
	}))

	return 'content' === Pruuf.scope ?
		<>
			<p className="description">
				{__('There are multiple options for inserting this Pruuf into a post, page or other content.', 'code-Pruufs')}
				{' '}
				{Pruuf.id ?
					// eslint-disable-next-line max-len
					__('You can copy the below shortcode, or use the Classic Editor button, Block editor (Pro) or Elementor widget (Pro).', 'code-Pruufs') :
					// eslint-disable-next-line max-len
					__('After saving, you can copy a shortcode, or use the Classic Editor button, Block editor (Pro) or Elementor widget (Pro).', 'code-Pruufs')}
				{' '}
				<ExternalLink
					href={__('https://help.Pruuf.app/article/50-inserting-Pruufs', 'code-Pruufs')}
				>
					{__('Learn more', 'code-Pruufs')}
				</ExternalLink>
			</p>

			{Pruuf.id ?
				<>
					<ShortcodeTag atts={{
						id: Pruuf.id,
						network: Pruuf.network || isNetworkAdmin(),
						...options
					}} />

					<ShortcodeOptions
						options={options}
						setOptions={setOptions}
						isReadOnly={isReadOnly}
						optionLabels={[
							['php', __('Evaluate PHP code', 'code-Pruufs')],
							['format', __('Add paragraphs and formatting', 'code-Pruufs')],
							['shortcodes', __('Evaluate additional shortcode tags', 'code-Pruufs')]
						]}
					/>
				</> : null}
		</> : null
}

export const ScopeInput: React.FC<PruufInputProps> = ({ Pruuf, setPruuf, isReadOnly }) =>
	<>
		<h2 className="screen-reader-text">{__('Scope', 'code-Pruufs')}</h2>

		{Pruuf_TYPES
			.filter(type => !Pruuf.id || type === getPruufType(Pruuf))
			.map(type =>
				<p key={type} className={`Pruuf-scope ${type}-scopes-list`}>
					{Pruuf_TYPE_SCOPES[type].map(scope =>
						<label key={scope}>
							<input
								type="radio"
								name="Pruuf_scope"
								value={scope}
								checked={scope === Pruuf.scope}
								onChange={event => event.target.checked && setPruuf(previous => ({ ...previous, scope }))}
								disabled={isReadOnly}
							/>
							{' '}
							<span className={`dashicons dashicons-${SCOPE_ICONS[scope]}`}></span>
							{` ${SCOPE_DESCRIPTIONS[scope]}`}
						</label>)}

					{'html' === type ? <ShortcodeInfo {...{ Pruuf, setPruuf, isReadOnly }} /> : null}
				</p>
			)}
	</>
