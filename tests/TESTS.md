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

Which group a new test belongs in:
- Plugin functions and classes that need WordPress and the database, and the AJAX handlers: **PHPUnit**.
  [phpunit/includes/class-wpec-ajax-test-case.php](phpunit/includes/class-wpec-ajax-test-case.php) sends an AJAX request as a visitor
  who isn't logged in and keeps the JSON response; it can stop the request right before the plugin calls the Stripe or PayPal API.
- A request handler that reads the real HTTP request (for example with `filter_input()`): the endpoint tests (Phase B, to be added).

PHPUnit converts PHP notices and warnings into test failures, so a notice in the plugin code fails the test that runs it.
The test files must stay PHP 7.4-compatible (the oldest PHP version in the workflow).

The test requests are built like the browser builds them: [phpunit/includes/class-wpec-test-factory.php](phpunit/includes/class-wpec-test-factory.php)
`frontend_data()` is a copy of `calcTotal()` in `assets/js/public.js` (the unit price after the coupon, the tax per item, the shipping),
and `stripe_request()` / `paypal_order_data()` build the same POST fields as `assets/js/wpec-stripe.js` and `assets/js/wpec-paypal.js`.
If the JavaScript changes how it calculates the amounts, change the factory too.

### Running the tests on your computer

The tests need PHP with the `mysqli` extension, a MySQL or MariaDB server, Composer and Subversion:
```
bash tests/bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest
cd tests/phpunit && composer install && vendor/bin/phpunit --testdox
```

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
the test calls `markTestIncomplete()` with "KNOWN ISSUE (still happens)" when the bug happens (shown as incomplete, with the reason);
any other wrong result fails.
The commit that fixes an issue removes the `markTestIncomplete()` part, so its test becomes a normal test.

| Issue | Tests |
|---|---|
| Coupons turned off (in the settings, or for the product) are not checked on the server: the coupon check request, the pre-submission validation and the order discount all accept a valid coupon code. The coupon field isn't shown, so a buyer needs to know a valid code and send the request themselves. | `test_a_coupon_does_not_lower_the_price_when_coupons_are_turned_off` (2 cases) |
| The Stripe checkout session request retrieves the product outside of a try block, so a product ID that isn't a product ends the request with an uncaught exception (PHP fatal error, HTTP 500) instead of an error response. Nothing is charged. | `test_an_unknown_product_is_rejected_without_a_fatal_error` |

## Decisions (behavior that is kept on purpose)

- **The Stripe unit price check allows one cent (one unit for zero-decimal currencies) less than the expected unit price.**
  With a coupon, the browser rounds the discounted unit price (10 - 2/3 = 9.33), so the charged total can be up to a cent per item lower
  than the validated total. The same allowance applies without a coupon, so a $4.99 donation passes a $5.00 minimum. Kept because the
  difference is at most a cent per item. Decided in November 2026.
- **PayPal donations are not checked when the order is created**, only after the capture (the amount paid must not be less than the minimum).

---

## Not covered yet (ideas for new tests)

Phase B (endpoint tests: a real test site with fake Stripe and PayPal APIs)
- The Stripe checkout session through to the return URL (`wpec_process_stripe_ipn`): the order, the emails, the stock, the coupon, the duplicate return.
- The PayPal create and capture requests over HTTP, the manual checkout and the free checkout (100% coupon).
- The coupon check request with a product ID; the Thank You page and the download links.

Phase C
- The Stripe return doesn't compare the amount Stripe collected with the product price (it trusts the session it created).
- The subscription products (the Subscriptions add-on) are not validated on the server.
- Stripe charges the shipping only for products with "This is a physical product" ticked, but the validation adds the shipping for any product with a shipping cost.
- The integrations (Simple Membership, eMember, WooCommerce), the emails' merge tags, the order summary, the admin pages.
- PHP 8.2+ deprecation notices in the WooCommerce gateway (dynamic properties).

Manual
- How the payment popup looks and behaves in a browser (the PayPal and Stripe buttons, the coupon field, the variations); real email delivery.

Old test suite
- [wp-express-checkout/tests/](../wp-express-checkout/tests/) (run by [.github/workflows/phpunit-tests.yml](../.github/workflows/phpunit-tests.yml) on every push to master)
  is the previous suite: 330 tests, 100 of them empty (`markTestIncomplete`). It is removed once the new suite covers what it checks.
