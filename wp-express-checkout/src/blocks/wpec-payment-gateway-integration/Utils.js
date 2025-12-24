const { getSetting } = window.wc.wcSettings

function getSettings(key, settingsGroup, defaultValue = null){
    const settings = getSetting( settingsGroup, {} );

    return settings[key] || defaultValue;
}

export function getPayPalSettings(key, defaultValue = null){
    return getSettings(key, "wp-express-checkout_data", defaultValue);
}

export function getStripeSettings(key, defaultValue = null){
    return getSettings(key, "wp-express-checkout-stripe_data", defaultValue);
}