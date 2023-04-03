/**
 * Payment button block script.
 *
 * @package simple-membership
 *
 * @since 4.3.3
 */

let swpmElement       = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender  = wp.serverSideRender,
	SelectControl     = wp.components.SelectControl,
	InspectorControls = wp.blockEditor.InspectorControls;

registerBlockType(
	'simple-membership/payment-button',
	{
		title: swpm_block_button_str.title,
		description: swpm_block_button_str.description,
		icon: 'money-alt',
		category: 'common',

		edit: function (props) {
			return [
			swpmElement(
				ServerSideRender,
				{
					block: 'simple-membership/payment-button',
					attributes: props.attributes,
				}
			),
			swpmElement(
				InspectorControls,
				{},
				swpmElement(
					SelectControl,
					{
						className: 'block-editor-block-card',
						label: swpm_block_button_str.paymentButton,
						value: props.attributes.btnId,
						options: swpm_button_options,
						onChange: (value) => {
							props.setAttributes( {btnId: value} );
						},
					}
				)
			),
			];
		},

		save: function () {
			return null;
		},
	}
);
