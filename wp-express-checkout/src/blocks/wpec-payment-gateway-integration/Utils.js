const { getSetting } = window.wc.wcSettings

export function getSettings(key, defaultValue = null){
    const settings = getSetting( 'wp-express-checkout_data', {} );
    return settings[key] || defaultValue;
}
