/* global ppecFrontVars, wpec_stripe_frontend_vars */

class WPECStripeHandler {

    constructor(btnData, wpecHandler) {
        this.btnData = btnData;
        this.wpecHandler = wpecHandler;
        this.ajaxUrl = ppecFrontVars.ajaxUrl;
        this.buttonId = btnData.id;

        document.dispatchEvent(
            new CustomEvent('wpec_before_render_stripe_button', {
                detail: {
                    handler: this,
                }
            })
        );

        this.render();
    }

    render() {
        const checkoutBtnCont = document.getElementById(this.buttonId);

        if (!checkoutBtnCont){
            console.log("Unable to render stripe checkout button.");
            return;
        }

        const checkoutBtn = document.createElement('button');

        const btnWidth = this.btnData?.btnStyle?.width ? this.btnData.btnStyle.width + 'px' : '150px';
        const btnText = this.btnData?.btnStyle?.label ? this.btnData.btnStyle.label : 'Stripe Checkout';
        const btnClasses = this.btnData?.btn_classes ? this.btnData.btn_classes : [];

        checkoutBtn.style.width = btnWidth;
        checkoutBtn.innerText = btnText;
        checkoutBtn.classList.add(...btnClasses);

        checkoutBtn.addEventListener('click', this.onStripeCheckout);

        checkoutBtnCont.appendChild(checkoutBtn);
    }

    onStripeCheckout = async (e) => {
        e.preventDefault();

        this.wpecHandler.calcTotal();

        this.wpecHandler.displayErrors();
        // Get first error input if there is any.
        if (this.hasInputErrors()) {
            return;
        }

        // console.log(this.wpecHandler.data)

        e.target.disabled = true;

        const order_data = {
            total: this.wpecHandler.data.total,
            quantity: this.wpecHandler.data.quantity,
            currency_code: this.wpecHandler.data.currency,
        };

        const payload = {
            action: 'wpec_stripe_create_checkout_session',
            wpec_data: JSON.stringify(this.wpecHandler.data),
            data: JSON.stringify(order_data),
            nonce: wpec_stripe_frontend_vars?.nonce,
        };

        document.dispatchEvent(new CustomEvent('wpec_process_stripe_checkout', {
            detail: {
                handler: this,
                paymentData: payload,
            }
        }));

        try {
            let response = await fetch(this.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(payload),
            });

            if (!response.ok) {
                alert('Stripe Checkout Error!');
                console.log(response);
                return;
            }

            response = await response.json();

            // console.log(response);

            if (!response.success) {
                alert(response.data.message);
                e.target.disabled = false;
            }

            // Redirect to process checkout session.
            if (typeof response.data.redirect_url !== 'undefined') {
                window.location.href = response.data.redirect_url;
            }

        } catch (error) {
            alert("HTTP error occurred during AJAX request. Error code: " + e.status);
            console.log(error.message);

            e.target.disabled = false;
        }
    }

    hasInputErrors() {
        // const errInput = this.wpecHandler.getScCont().querySelector('.hasError');

        const allErrorInputs = [...this.wpecHandler.getScCont().querySelectorAll('.hasError')];
        const errInputs = allErrorInputs.filter(el => !el.closest('.wpec-manual-checkout-form')); // No need to validate mc form inputs.

        const errInput = errInputs.length ? errInputs[0] : null;

        if (errInput) {
            errInput.focus();
            errInput.dispatchEvent(new Event('change'));

            return true;
        }

        return false;
    }
}
