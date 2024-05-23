import {decodeEntities} from '@wordpress/html-entities';
import FrontEndContent from './FrontEndContent';
const {registerPaymentMethod} = window.wc.wcBlocksRegistry
import {getSettings} from './Utils';

// console.log("WP Express Checkout gateway bBlock script loaded");

const label = decodeEntities(getSettings('title'))

const EditPageContent = () => {
    return decodeEntities(getSettings('description', ''));
}

const Label = (props) => {
    const {PaymentMethodLabel} = props.components
    return <PaymentMethodLabel text={label}/>
}

registerPaymentMethod({
    name: "wp-express-checkout",
    label: <Label/>,
    content: <FrontEndContent/>,
    edit: <EditPageContent/>,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: getSettings('supports', []),
    }
})
