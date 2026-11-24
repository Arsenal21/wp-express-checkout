# Plugin Tests: what is covered

This file lists what the **Plugin Tests** GitHub Actions workflow checks, and what isn't covered yet.
Update it in the same commit when you add or change a test.

## How to run

GitHub > Actions > **Plugin Tests (WPEC Main)** > Run workflow. Choose the branch and tick the test groups to run (all are ticked by default).
The workflow file is [.github/workflows/plugin-tests-main.yml](../.github/workflows/plugin-tests-main.yml).
Run it before a release and after bigger changes.

A separate, light workflow, [.github/workflows/php-compatibility-test.yml](../.github/workflows/php-compatibility-test.yml), runs on every push
(it activates the plugin and runs the shortcodes). Keep it fast; full tests go in the Plugin Tests workflow.

| Group | What it is | PHP versions | Files |
|---|---|---|---|
| Lint | PHP syntax check of the plugin and the test files (the Stripe SDK in `lib/stripe` and the PayPal SDK in `vendor/paypal` are skipped) | 7.4, 8.5 | (in the workflow) |
| PHPUnit | Tests with the WordPress test library and a real database | 7.4, 8.4 | [phpunit/](phpunit/) |
| Endpoint tests | A real WordPress site with the plugin and fake Stripe and PayPal APIs: sends the checkout requests over HTTP and checks the database | 7.4, 8.4 | [e2e/](e2e/) |

Which group a new test belongs in:
- Plugin functions and classes that need WordPress and the database, and the AJAX handlers' checks: **PHPUnit**.
  [phpunit/includes/class-wpec-ajax-test-case.php](phpunit/includes/class-wpec-ajax-test-case.php) sends an AJAX request as a visitor
  who isn't logged in and keeps the JSON response; it can stop the request right before the plugin calls the Stripe or PayPal API.
- A whole checkout (page, payment requests, return from Stripe, Thank You page, download), a request handler that reads the real
  HTTP request (for example with `filter_input()`), or code that ends with `exit` or a redirect: **Endpoint tests**.

PHPUnit converts PHP notices and warnings into test failures, so a notice in the plugin code fails the test that runs it.
The test files must stay PHP 7.4-compatible (the oldest PHP version in the workflow).

The test requests are built like the browser builds them: [phpunit/includes/class-wpec-test-factory.php](phpunit/includes/class-wpec-test-factory.php)
`frontend_data()` is a copy of `calcTotal()` in `assets/js/public.js` (the unit price after the coupon, the tax per item, the shipping),
and `stripe_request()` / `paypal_order_data()` build the same POST fields as `assets/js/wpec-stripe.js` and `assets/js/wpec-paypal.js`.
If the JavaScript changes how it calculates the amounts, change the factory too.

### Running the tests on your computer

The tests need PHP with the `mysqli` and `curl` extensions, a MySQL or MariaDB server, Composer and Subversion (WP-CLI for the endpoint tests):
```
bash tests/bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest
cd tests/phpunit && composer install && vendor/bin/phpunit --testdox
```
For the endpoint tests, follow the steps of the `endpoint-tests` job in the workflow (a WordPress site, the plugin, the must-use plugin,
`setup-site.php`, `php -S`), then run `tests/e2e/run-endpoint-tests.php` with the same environment variables.

---

## Endpoint tests ([e2e/run-endpoint-tests.php](e2e/run-endpoint-tests.php))

The site is set up with WP-CLI; [e2e/setup-site.php](e2e/setup-site.php) saves the settings (PayPal and Stripe in test mode, manual checkout,
coupons, the emails), creates the products (one-time, physical with tax and shipping, with stock, a donation with a minimum, a download,
coupons turned off, variations), the coupons, and a page with the product shortcode for each product.
[e2e/mu-plugin-e2e-fakes.php](e2e/mu-plugin-e2e-fakes.php) is a fake Stripe API (checkout sessions, tax rates; given to the Stripe SDK as its
HTTP client) and a fake PayPal API (access token, create order, capture order; it replaces the PayPal SDK's `PayPalHttp\Curl` class).
It also keeps the emails instead of sending them, and blocks any other request to Stripe or PayPal.
There is no browser, so a Stripe checkout session is paid as soon as it is created, and a PayPal order is captured for the amount it was
created with; the runner changes the fakes' data directly for the other cases (an unpaid session, a PayPal order made outside the plugin).
The tests read the nonces and the JS data from the product page and build the requests like the browser does (the factory's `calc_total()`).

**Product page and coupon check request**
- The page loads with the checkout data, the nonces and the PayPal, Stripe and manual checkout buttons.
- The coupon check request: a valid code (in any case) with its discount, an unknown code, a coupon only for another product (rejected)
  and only for this product (accepted).

**Stripe** (checkout session request, then the return from Stripe)
- 2 items with a 10% coupon: Stripe is asked to charge 2 x $9.00; the return creates a paid $18.00 order with the session ID and the charge ID,
  the coupon line, the buyer's email address; the coupon is redeemed; the buyer and seller emails are sent; the Thank You page shows the order.
- Tax and shipping: a tax rate on the line item, the shipping option ($5 + $1 per item), the $51.00 total, the tax and shipping lines in the order;
  the saved tax rate is used again.
- The stock goes from 2 to 0 and the product page then shows "Out of stock"; a $25 donation.
- Rejected before Stripe is called: a donation below the minimum, a lower unit price, another currency in the charged data (fixed in 2.5.5), a wrong nonce.
- Returning from an unpaid session, and with an unknown session ID, shows an error and creates no order.

**PayPal** (create order, then capture)
- 3 items: the order sent to PayPal is $30.00; the capture creates a paid order with the PayPal order and capture IDs; the buyer and seller
  emails (the buyer email's merge tags are replaced); the Thank You page shows the order. Capturing the same order again fails and creates no order.
- A price variation (the variation line), a $1 coupon (then the coupon is used up: the coupon check request rejects it, redemption limit 1).
- A donation below the minimum passes the create order request and is rejected after the capture.
- Rejected: a lower total (no PayPal order is created), the capture of a $0.01 PayPal order made outside the plugin (amount mismatch, no paid order),
  a capture request with a wrong nonce.

**Free checkout** (100% coupon)
- A paid $0.00 order; the Thank You page shows the download link; the link delivers the file; a wrong key doesn't.
- Rejected: without a coupon (no order is created), a wrong nonce.

**Manual checkout**
- A pending order with the buyer's quantity; the buyer gets the payment instructions and the seller is notified.
- A lower total is rejected and creates no pending order.

**Thank You page**: without an order ID, and with a wrong nonce (no order details), it shows the error.

**PHP errors**: a PHP fatal error on the test site fails the tests; the plugin's PHP warnings, notices and deprecations are listed in the output.

---

## PHPUnit ([phpunit/tests/](phpunit/tests/))

**Plugin smoke test** (test-plugin-smoke.php)
- The plugin loads; activation saves all the default settings (USD, 2 decimals, PayPal on, Stripe off) and creates the Thank You and Shop pages.
- Activating again (as a plugin update does) keeps the saved settings, adds a missing setting with its default and doesn't create the pages twice.
- The products, orders and coupons post types and the six shortcodes are registered.
- `[wp_express_checkout]` shows the product name, the price and the PayPal and Stripe buttons, and saves the price data for the payment processing.
- Without a PayPal client ID it shows the "Please enter ... Client ID" error instead of the PayPal button; a wrong product ID shows an error;
  an out of stock product shows "Out of stock" and no buttons. `[wpec_show_all_products]` lists a product.

**Stripe checkout session request** (test-stripe-checkout-validation.php, `wpec_stripe_create_checkout_session`)
The request is stopped right before the Stripe API call, and the test checks the line item the plugin would send to Stripe.
- Accepted, with the amount Stripe is asked to charge: one item ($10.00 = 1000 cents), $0.29 (29 cents), 3 items with a 10% coupon,
  3 items with a $2 coupon (the unit price is rounded down to $9.33 by the browser and accepted), a price variation, a physical product
  (the shipping option, base + per item), JPY (no cents, also with a coupon that rounds the unit price), paying more than the price,
  a product with tax (passes the validation; the tax rate is a Stripe API call, so the request is stopped earlier).
- Donations: at the minimum, above it, a coupon taking it below the minimum (allowed), no minimum (any amount).
- Rejected (the price tampering fixed in 2.5.5): a lower unit price than the product price or than the discounted price, a unit price that
  isn't a number, a different currency or quantity in the charged data than in the validated data, a zero quantity, a lower total
  (also without the tax and shipping), another currency, a donation below the minimum (also with only the charged unit price lowered),
  a donation in another currency.
- A coupon that doesn't apply doesn't lower the price: a coupon only for another product, an expired coupon, a made-up code.
- A wrong nonce is rejected before anything else.

**PayPal create order request** (test-paypal-create-order-validation.php, `wpec_pp_create_order`)
The request is stopped right after the validation passed (before the PayPal API call).
- Accepted: one item, 3 items with tax and per-item shipping, a 50% coupon, two price variation groups, variations that make up the whole price,
  a donation with any amount (donations are checked after the capture).
- Rejected: a lower total, a total without the tax, a quantity that doesn't match the total, another currency, a made-up coupon, a wrong nonce
  (the validation doesn't run), empty plugin data, a post that isn't a product.
- The order sent to PayPal: the product name from the database (not the one sent by the browser), cut to 127 characters; the amounts,
  tax, shipping and discount lines; no tax, shipping or discount lines when there are none; the shipping preference.

**PayPal capture processing** (test-paypal-capture-processing.php, `Payment_Processor::wpec_server_side_process_payment()`)
The tests pass a capture response like PayPal's.
- A paid capture creates a paid order (total, currency, gateway, PayPal order and capture IDs, buyer email, title with the quantity),
  redirects to the Thank You page with the order ID and a nonce, runs `wpec_payment_completed` and sends the buyer email; the seller email when it's on.
- Tax and shipping for the buyer's quantity; a coupon (the discount line, the redemption count); a price variation (the variation line);
  the stock goes down; a donation with the buyer's amount; a product sold in 2s can't be paid for 1.
- Rejected (no paid order, no `wpec_payment_completed`, no email): a capture lower than the price, without the tax and shipping, for fewer
  items than ordered, with a made-up coupon, a donation below the minimum, another currency, a capture that isn't completed, more items than
  in stock (the stock doesn't change), a product whose price data wasn't saved (the product wasn't shown).

**Stripe return** (test-stripe-return-processing.php, `Payment_Processor_Stripe::check_if_checkout_session_processed()`)
- A session with an order (the session ID saved as the order's resource ID) is processed, so opening the return URL again doesn't create
  another order (fixed after 2.5.5); a session without an order isn't; an order with the session ID as the capture ID is found too.

**Orders, order tags and emails** (test-orders-and-emails.php)
- A new order is an incomplete "Transaction" with the buyer's IP address; the total is the sum of the items and never negative;
  a unique item replaces the previous one; items can be removed; an order is saved and read back (status, gateway, currency, IDs, email,
  total, data, quantity); a status change runs its action once; a post that isn't an order is rejected.
- The order tags (the email merge tags and the Thank You page parts): names, email, transaction ID, amount (without the symbol),
  currency, coupon code, order ID, product ID and name, quantity, address, date; the product details (item, coupon, total, download link);
  the download link tags; the selected variations.
- The buyer email: to the buyer, with the subject and body tags replaced and the From address, saved as sent on the order; the HTML type;
  a product's own buyer email; none when it's turned off. The seller email: only when it's turned on, to the notification address.
- The Thank You page shortcodes (`[wpec_ty field=...]`, `[wpec_thank_you]`) with a valid nonce, a wrong nonce, no order ID,
  and an order that isn't completed.

**Integrations** (test-integrations.php; the other plugins are faked in [phpunit/includes/integration-fakes.php](phpunit/includes/integration-fakes.php))
- Simple Membership: the PayPal buyer is signed up to the product's level with the buyer details (names, email, address, the country name)
  and the PayPal order ID; a logged-in member is upgraded; a Stripe buyer is signed up with the Stripe customer details and the charge ID;
  nothing for a product without a level; it runs on `wpec_payment_completed`; the PayPal subscription events (cancelled, expired, suspended,
  payment) call the right function; other events and events without a subscription ID do nothing.
- WP eMember: the buyer is signed up to the product's level; a logged-in member is upgraded; a Stripe payment; nothing without a level.
- Software License Manager: one license per item (prefix, status, buyer, transaction ID, the product's domains and expiry, or the defaults
  and one year), the keys replace `{wpec_slm_data}` in the buyer email; nothing for a product without licenses or without an order.
- A subscription product without the updated Subscriptions add-on is treated as a one-time product.

**Settings, PayPal and the product block** (test-settings-paypal-and-blocks.php)
- A setting that isn't saved uses its default.
- The PayPal SDK script: the sandbox or live client ID, the currency, the disabled funding sources and cards, the sandbox buyer country.
- The PayPal API client: the sandbox or live credentials (from the setting, or the mode passed).
- The product block is registered, shows the product, and shows a placeholder without a product.

**Coupons** (test-coupons.php, `Coupons::get_coupon()`, `is_coupon_allowed_for_product()`)
- A valid coupon is found with its discount and type; a coupon below its redemption limit is valid.
- Rejected with the right message: unknown code, inactive, starts later, no start date, expired, redemption limit reached.
- A coupon only for some products is allowed only for them; a coupon for all products is allowed for any product.
- The coupon check request (`wpec_check_coupon`): an unknown code and an empty code are rejected. The other cases need the product ID,
  which the handler reads from the real HTTP request, so they belong in the endpoint tests.

**Price helpers and products** (test-price-helpers.php)
- `round_price()` with the decimals setting, the tax amount (rounded to the cent), the shipping total (base + per item).
- `amount_in_cents()`: whole amounts, cents, amounts that aren't exact in floating point (0.29, 1.15), strings, thousands, more decimals;
  `amount_from_cents()`; the zero-decimal currencies (JPY, KRW, VND, MGA, also lower case); `almost_equal()` (less than a cent).
- Price formatting (symbol position, separators, another currency) and the variation price modifier.
- Products: no saved type is one-time; an old product with "custom amount" is a donation; the donation price (the price or the minimum);
  a page or a non-number ID is rejected; an empty product tax/shipping uses the settings and '0' doesn't; the coupons setting falls back
  to the settings; the stock goes down but not below 0; digital and physical products.

---

## Known issues (tests that report without failing)

A test for a known or suspected bug runs, but doesn't fail the workflow, so the output shows on every run whether the problem still happens:
- PHPUnit: the test calls `markTestIncomplete()` with "KNOWN ISSUE (still happens)" when the bug happens (shown as incomplete, with the reason);
  any other wrong result fails.
- Endpoint tests: the issue is in `KNOWN_ISSUES` in [e2e/run-endpoint-tests.php](e2e/run-endpoint-tests.php); its checks print
  `KNOWN ISSUE (still happens)` or `KNOWN ISSUE NOW PASSES`.

The commit that fixes an issue removes the `markTestIncomplete()` part (or the `KNOWN_ISSUES` entry), so its test becomes a normal test.

| Issue | Tests |
|---|---|
| Coupons turned off (in the settings, or for the product) are not checked on the server: the coupon check request, the pre-submission validation and the order discount all accept a valid coupon code. The coupon field isn't shown, so a buyer needs to know a valid code and send the request themselves. | PHPUnit: `test_a_coupon_does_not_lower_the_price_when_coupons_are_turned_off` (2 cases); Endpoint: `coupons-turned-off` |
| The Stripe checkout session request retrieves the product outside of a try block, so a product ID that isn't a product ends the request with an uncaught exception (PHP fatal error, HTTP 500) instead of an error response. Nothing is charged. | PHPUnit: `test_an_unknown_product_is_rejected_without_a_fatal_error` |

Fixed after 2.5.5 (their tests are normal tests now):
- Opening the Stripe return URL again (a reload, the back button) created another paid order, sent the emails again, lowered the stock and
  redeemed the coupon again: `check_if_checkout_session_processed()` looked for the session ID in the capture ID, but the session ID is saved
  as the resource ID. Tests: test-stripe-return-processing.php, and the endpoint tests' "opening the return URL again" checks.
- A PHP 8.3+ deprecation notice when a coupon was redeemed for the first time (the empty redemption count was incremented).
- Stored XSS: the `[wpec_ty field=...]` Thank You page shortcode showed the buyer details (name, phone, address, coupon code, variations)
  without escaping, and the manual and free checkouts saved them as sent by the browser. Now the HTML order tags are escaped (custom tags
  from add-ons go through `wp_kses_post()`), the plain text tags for the emails are not, and the buyer details are saved as text only.
  Tests: test-buyer-data-escaping.php.

## Decisions (behavior that is kept on purpose)

- **The Stripe unit price check allows one cent (one unit for zero-decimal currencies) less than the expected unit price.**
  With a coupon, the browser rounds the discounted unit price (10 - 2/3 = 9.33), so the charged total can be up to a cent per item lower
  than the validated total. The same allowance applies without a coupon, so a $4.99 donation passes a $5.00 minimum. Kept because the
  difference is at most a cent per item. Decided in November 2026.
- **PayPal donations are not checked when the order is created**, only after the capture (the amount paid must not be less than the minimum).

---

## Not covered yet (ideas for new tests)

Phase C
- The Stripe return doesn't compare the amount Stripe collected with the product price (it trusts the session it created).
- The subscription products (the Subscriptions add-on) are not validated on the server.
- Stripe charges the shipping only for products with "This is a physical product" ticked, but the validation adds the shipping for any product with a shipping cost.
- The manual checkout form sends the address fields shifted by one (`assets/js/wpec-manual-checkout.js`: the city is sent as the state,
  the country as the city, the state as the postal code, the postal code as the country). Found by reading the code; JavaScript isn't tested.
- The WooCommerce integration (the PayPal and Stripe gateways): it needs WooCommerce installed, so it belongs in the endpoint tests
  (a test site with WooCommerce). The admin pages (the settings, the product and coupon edit pages, the orders list).
- PHP 8.2+ deprecation notices in the WooCommerce gateway (dynamic properties), and in the PayPal SDK (`vendor/paypal`, PHP 8.4: implicitly nullable parameters).
- The subscription webhooks (the Subscriptions add-on), tested with the add-on installed.

Manual
- How the payment popup looks and behaves in a browser (the PayPal and Stripe buttons, the coupon field, the variations); real email delivery.

The previous test suite (`wp-express-checkout/tests/` and the `phpunit-tests.yml` workflow: 330 tests, 100 of them empty) was removed
in November 2026, once this suite covered what it checked. Only its WooCommerce gateway tests (with fake WooCommerce classes) aren't
replaced yet (see above).
