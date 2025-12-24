import {decodeEntities} from '@wordpress/html-entities';
import Content from './Content';
import Edit from './Edit'
import {getStripeSettings} from '../../utils';

const title = decodeEntities(getStripeSettings('title'));
const description = decodeEntities(getStripeSettings('description', ''));

const Label = (props) => {
    const {PaymentMethodLabel} = props.components
    return <PaymentMethodLabel text={title}/>
}

export const stripeConfig = {
    name: 'wp-express-checkout-stripe',
    title: title,
    description: description,
    label: <Label />,
    content: <Content />,
    edit: <Edit />,
    canMakePayment: () => true,
    ariaLabel: title,
    supports: {
        features: ['products'],
    },
};