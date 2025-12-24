import {decodeEntities} from '@wordpress/html-entities';
import Content from './Content';
import Edit from './Edit';
import {getPayPalSettings} from '../../utils';

const label = decodeEntities(getPayPalSettings('title'))

const Label = (props) => {
    const {PaymentMethodLabel} = props.components
    return <PaymentMethodLabel text={label}/>
}

export const paypalConfig = {
    name: "wp-express-checkout",
    label: <Label/>,
    content: <Content/>,
    edit: <Edit/>,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: getPayPalSettings('supports', []),
    }
}