/**
 * Payment button block script.
 *
 * @package simple-membership
 */

let swpm_element           = wp.element.createElement,
	swpm_registerBlockType = wp.blocks.registerBlockType,
	swpm_serverSideRender  = wp.serverSideRender,
	swpm_selectControl     = wp.components.SelectControl,
	swpm_InspectorControls = wp.blockEditor.InspectorControls;

swpm_registerBlockType(
	'simple-membership/payment-button',
	{
		title: swpm_block_button_str.title,
		description: swpm_block_button_str.description,
		icon: 'money-alt',
		category: 'common',

		edit: function (props) {
			return [
				swpm_element(
					swpm_serverSideRender,
					{
						block: 'simple-membership/payment-button',
						attributes: props.attributes,
					}
				),
				swpm_element(
					swpm_InspectorControls,
					{},
					swpm_element(
						'div',
						{className: 'swpm-payment-block-ic-wrapper'},
						swpm_element(
							swpm_selectControl,
							{
								label: swpm_block_button_str.paymentButton,
								value: props.attributes.btnId,
								options: swpm_button_options,
								onChange: (value) => {
									props.setAttributes( {btnId: value} );
								},
							}
						)
					)
				),
			];
		},

		save: function () {
			return null;
		},
	}
);
