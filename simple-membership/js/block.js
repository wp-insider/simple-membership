var swpmElement = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    ServerSideRender = wp.serverSideRender,
    SelectControl = wp.components.SelectControl,
    InspectorControls = wp.blockEditor.InspectorControls;

registerBlockType('simple-membership/payment-button', {
    title: swpmBlockBtnStr.title,
    description: swpmBlockBtnStr.description,
    icon: 'money-alt',
    category: 'common',

    edit: function (props) {
        return [
            swpmElement(ServerSideRender, {
                block: 'simple-membership/payment-button',
                attributes: props.attributes,
            }),
            swpmElement(InspectorControls, {},
                swpmElement(SelectControl, {
                    className: 'block-editor-block-card',
                    label: swpmBlockBtnStr.paymentButton,
                    value: props.attributes.btnId,
                    options: swpmBtnOpts,
                    onChange: (value) => {
                        props.setAttributes({btnId: value});
                    },
                })
            ),
        ];
    },

    save: function () {
        return null;
    },
});