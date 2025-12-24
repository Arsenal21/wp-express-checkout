import {decodeEntities} from "@wordpress/html-entities";
import {getPayPalSettings} from "../../utils";

export default () => {
    const description = decodeEntities(getPayPalSettings('description', ''));

    return (
        <>
            {description}
        </>
    );
}