import {decodeEntities} from "@wordpress/html-entities";
import {getStripeSettings} from "../../utils";

export default () => {
    const description = decodeEntities(getStripeSettings('description', ''));

    return (
        <>
            {description}
        </>
    );
}