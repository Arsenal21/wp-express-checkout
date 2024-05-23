import {decodeEntities} from '@wordpress/html-entities';
import {useEffect, useState, useRef} from 'react';
import {getSettings} from './Utils';
import WpecPaypalButtonHandler from "./WpecPaypalButtonHandler";
import styles from './FrontEndContent.module.css';

const FrontEndContent = ({eventRegistration}) => {
    const ajaxUrl = getSettings('ajaxUrl');
    const popup_title = getSettings('popup_title');
    const renderButtonNonce = getSettings('renderButtonNonce');

    const {onCheckoutSuccess} = eventRegistration;

    const [btnData, setBtnData] = useState(null);
    const [priceTag, setPriceTag] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [sdkLoaded, setSdkLoaded] = useState(false);

    // console.log('sdk_args', getSettings('pp_sdk_args'));

    const toggleModal = () => {
        setShowModal(!showModal)
    }

    useEffect(() => {
        onCheckoutSuccess((args) => {
            const {
                redirectUrl,
                orderId,
                customerId,
                orderNotes,
                paymentResult,
            } = args;

            // Retrieve wpec paypal payment button data.
            fetch(ajaxUrl, {
                method: "post",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'wpec_wc_block_payment_button_data',
                    order_id: orderId,
                    modal_title: popup_title,
                    nonce: renderButtonNonce,
                }).toString()
            }).then((response) => {
                return response.json()
            }).then(response => {
                if (response.success !== true) {
                    throw new Error(response.message);
                }

                // console.log(response.data);

                // Set the data related to paypal button generation.
                setBtnData(response.data);
                setPriceTag(response.data.price_tag)

            }).catch(error => {
                console.log(error.messages);
                alert(error.messages);
            })
        });
    }, [onCheckoutSuccess]);

    useEffect(() => {
        if (btnData) {
            const sdk_args = getSettings('pp_sdk_args');
            const sdk_args_query_param = new URLSearchParams(sdk_args).toString()

            const script = document.createElement('script');
            script.src = `https://www.paypal.com/sdk/js?${sdk_args_query_param}`;
            script.setAttribute('data-partner-attribution-id', 'TipsandTricks_SP')
            script.async = true;
            script.onload = () => {
                console.log('WPEC PayPal SDK For WooCommerce Blocks loaded!');
                setSdkLoaded(true);
            };
            document.body.appendChild(script);

            return () => {
                document.body.removeChild(script);
            };
        }
    }, [btnData]);

    useEffect(() => {
        if (sdkLoaded) {
            const ppHandler = new WpecPaypalButtonHandler(btnData, {
                ajaxUrl: ajaxUrl,
                renderTo: '#wpec_wc_paypal_button_container'
            })

            ppHandler.generate_ppec_woocommerce_button()

            // Display the modal with paypal button.
            setShowModal(true)
        }
    }, [sdkLoaded])

    return (
        <>
            <div className={`${styles.modal} ${showModal ? styles.modalShow : ''}`}>
                <div className={styles.modalContent} onClick={e => e.stopPropagation()}>
                    <div className={styles.modalHeader}>
                        <h4>{popup_title}</h4>
                        <button type='button' className={styles.modalCloseBtn}>
                            <span className={styles.modalCloseIcon} onClick={toggleModal}>&times;</span>
                        </button>
                    </div>

                    {priceTag && (
                        <>
                        <h4 dangerouslySetInnerHTML={{__html: priceTag}}/><br/>
                        </>
                    )}

                    <div id="wpec_wc_paypal_button_container">
                        {/* PayPal Button Renders Here */}
                    </div>
                </div>
            </div>

            {decodeEntities(getSettings('description', ''))}
        </>
    )
}

export default FrontEndContent;