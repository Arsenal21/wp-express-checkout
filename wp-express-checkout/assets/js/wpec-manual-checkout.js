/* global ppecFrontVars, wpec_manual_checkout_frontend_vars */

class WPECManualCheckout {

    constructor(btnData, wpecHandler) {
        this.wpecHandler = wpecHandler;
        this.buttonId = btnData.id;
        this.mcProceedBtn = document.getElementById('wpec-proceed-manual-checkout-'+ this.buttonId);
        this.mcForm = document.getElementById( 'wpec-manual-checkout-form-' + this.buttonId );

        this.mcFormSubmitBtn = this.mcForm.querySelector('button.wpec-place-order-btn');

        // TODO: May not required any more.
        // this.tosCheckbox = document.getElementById( 'wpec-tos-' + this.buttonId );
        // this.customQuantityInput = document.querySelector( '#wp-ppec-custom-quantity[data-ppec-button-id="' + this.buttonId + '"]' );
        // this.customAmountInput = document.querySelector( '#wp-ppec-custom-amount[data-ppec-button-id="' + this.buttonId + '"]' );
        // document.addEventListener('wpec_validate_order', (e) => {
        //     this.wpecHandler.toggleVisibility(
        //         document.getElementById( 'wpec-manual-checkout-section-'+ this.buttonId ),
        //         'block',
        //         !this.wpecHandler.isElementVisible(document.getElementById( 'place-order-' + wpecHandler.data.id ))
        //     );
        // })

        document.dispatchEvent(
            new CustomEvent('wpec_before_render_manual_checkout_button', {
                detail: {
                    handler: this,
                }
            })
        );

        this.init();
    }

    init(){
        this.mcForm?.querySelector( '.wpec_same_billing_shipping_enable' )?.addEventListener( 'change', () => {
            const wpec_address_wrap = this.mcForm?.querySelector( '.wpec_address_wrap' );
            this.wpecHandler.toggleVisibility(wpec_address_wrap?.querySelector( '.wpec_shipping_address_container' ), 'inherit');
            this.mcForm?.querySelector( '.wpec_address_wrap' )?.classList.toggle('shipping_enabled');
        } );

        // TODO: May not required any more.
        // this.wpecHandler.validateInput( this.tosCheckbox, this.wpecHandler.ValidatorTos );
        // this.wpecHandler.validateInput( this.customQuantityInput, this.wpecHandler.ValidatorQuantity );
        // this.wpecHandler.validateInput( this.customAmountInput, this.wpecHandler.ValidatorAmount );
        this.mcForm?.querySelectorAll( '.wpec_required' ).forEach( (element) => {
            this.wpecHandler.validateInput( element, this.wpecHandler.ValidatorBilling );
        } );

        this.mcForm?.addEventListener('submit', (e) => {
            e.preventDefault();

            this.mcForm.setAttribute("disabled", true);

            const formData = new FormData( this.mcForm);

            this.wpecHandler.displayErrors();

            if (this.mcFormSubmitBtn){
                this.mcFormSubmitBtn.querySelector("svg").style.display = 'inline';
                this.mcFormSubmitBtn.setAttribute("disabled", true);
            }

            // Get first error input if there is any.
            if (this.hasInputErrors()){
                this.mcForm.removeAttribute("disabled");
                if (this.mcFormSubmitBtn) {
                    this.mcFormSubmitBtn.querySelector("svg").style.display = 'none';
                    this.mcFormSubmitBtn.removeAttribute("disabled");
                }
                return;
            }

            const billing_address = {
                address_line_1: formData.get('wpec_billing_address'),
                admin_area_1: formData.get('wpec_billing_city'),
                admin_area_2: formData.get('wpec_billing_country'),
                postal_code: formData.get('wpec_billing_state'),
                country_code: formData.get('wpec_billing_postal_code'),
            };
            const shipping_address = formData.get('wpec_same_billing_shipping_enable') ? billing_address : {
                address_line_1: formData.get('wpec_shipping_address'),
                admin_area_1: formData.get('wpec_shipping_city'),
                admin_area_2: formData.get('wpec_shipping_country'),
                postal_code: formData.get('wpec_shipping_state'),
                country_code: formData.get('wpec_shipping_postal_code'),
            };

            const paymentData = {
                payer: {
                    name: {
                        given_name: formData.get('wpec_billing_first_name'),
                        surname: formData.get('wpec_billing_last_name'),
                    },
                    email_address: formData.get('wpec_billing_email'),
                    phone: formData.get('wpec_billing_phone'),
                    address: billing_address,
                    shipping_address: shipping_address,
                },
            }

            document.dispatchEvent(new CustomEvent('wpec_process_manual_checkout', {
                detail: {
                    paymentData,
                    handler: this,
                }
            }));

            this.wpecHandler.processPayment(paymentData, 'wpec_process_manual_checkout' );
        } );

        this.mcForm?.addEventListener('reset', (e)=>{
            const form = e.target;
            form.removeAttribute("disabled");
            if (this.mcFormSubmitBtn){
                this.mcFormSubmitBtn.querySelector("svg").style.display = 'none';
                this.mcFormSubmitBtn.removeAttribute("disabled");
            }

            this.toggleManualCheckout();
        })

        this.mcProceedBtn?.addEventListener('click',  (e) =>{
            this.toggleManualCheckout();
        });
    }

    hasInputErrors(){
        const errInput = this.wpecHandler.getScCont().querySelector('.hasError');

        if ( errInput ) {
            errInput.focus();
            errInput.dispatchEvent( new Event('change') );

            return true;
        }

        return false;
    }

    toggleManualCheckout(){
        this.wpecHandler.toggleVisibility(this.mcForm, 'inherit');
        this.wpecHandler.toggleVisibility(this.mcProceedBtn, 'inherit');

        // this.parent = this.wpecHandler; // TODO: For BPCFT support, need to fix this later
        document.dispatchEvent(new CustomEvent('wpec_toggle_manual_checkout_form', {
            detail: {
                handler: this,
            }
        }));
    }

}
