/**
 * Registers the development payment method with the block checkout.
 *
 * Plain JavaScript on purpose: this is a development aid, and a build step for
 * it would be a build step the project does not otherwise need.
 */
( function ( wc, wp ) {
	if ( ! wc || ! wc.wcBlocksRegistry || ! wp || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var settings = ( window.wc.wcSettings && window.wc.wcSettings.getSetting( 'vr_dev_data', {} ) ) || {};
	var outcomes = settings.outcomes || {};
	var chosen = 'approve';

	function Fields( props ) {
		var eventRegistration = props.eventRegistration;
		var emitResponse = props.emitResponse;

		// Subscribed once, deliberately. The checkout hands new
		// eventRegistration and emitResponse objects on every render, so
		// listing them as dependencies resubscribes forever. The outcome is
		// read when the callback runs, not when it is registered, so the
		// closure staying put costs nothing.
		wp.element.useEffect( function () {
			return eventRegistration.onPaymentSetup( function () {
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: { paymentMethodData: { vr_dev_outcome: chosen } }
				};
			} );
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [] );

		return el(
			'fieldset',
			{ style: { border: 0, padding: 0, margin: 0 } },
			el( 'legend', { className: 'screen-reader-text' }, 'What should the payment provider do?' ),
			el( 'p', null, settings.description || '' ),
			Object.keys( outcomes ).map( function ( value ) {
				return el(
					'p',
					{ key: value, style: { margin: '0 0 .35rem' } },
					el( 'label', null,
						el( 'input', {
							type: 'radio',
							name: 'vr_dev_outcome',
							value: value,
							defaultChecked: 'approve' === value,
							onChange: function () {
								chosen = value;
							}
						} ),
						' ' + outcomes[ value ]
					)
				);
			} )
		);
	}

	wc.wcBlocksRegistry.registerPaymentMethod( {
		name: 'vr_dev',
		label: settings.title || 'Test payment (development only)',
		content: el( Fields, null ),
		edit: el( 'div', null, settings.title || 'Test payment (development only)' ),
		canMakePayment: function () {
			return true;
		},
		ariaLabel: settings.title || 'Test payment (development only)',
		supports: { features: settings.supports || [ 'products' ] }
	} );
}( window.wc, window.wp ) );
