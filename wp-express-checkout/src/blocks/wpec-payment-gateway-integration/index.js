const {registerPaymentMethod} = window.wc.wcBlocksRegistry

import {paypalConfig} from './payment-methods/paypal';
import {stripeConfig} from './payment-methods/stripe';

registerPaymentMethod(paypalConfig);
registerPaymentMethod(stripeConfig);