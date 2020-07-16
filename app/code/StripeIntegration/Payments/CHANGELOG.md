# Changelog

## 1.1.2 - 2019-06-10

- Improvements with multi-shipping checkout
- Compatibility improvements with M2EPro and some other 3rd party modules
- New translation entries
- Fixed the street and CVC checks not displaying correctly in the admin order page

## 1.1.1 - 2019-05-30

- Depreciates support for saved cards created through the Sources API
- Improves checkout performance
- Fixed error when trying to capture an expired authorization in the admin area using a saved card
- Fixed a checkout crash with guest customers about the Payment Intent missing a payment method

## 1.1.0 - 2019-05-28

- `MAJOR`: Switched from automatic Payment Intents confirmation at the front-end to manual Payment Intents confirmation on the server side. Resolves reported issue with charges not being associated with a Magento order.
- `MAJOR`: Replaced the Sources API with the new Payment Methods API. Depreciated all fallback scenarios to the Charges API.
- Stripe.js v2 has been depreciated, Stripe Elements is now used everywhere
- When Apple Pay is used on the checkout page, the order is now submitted automatically as soon as the paysheet closes.
- Fixed: In the admin configuration, when the card saving option was set to "Always save cards", it wouldn't have the correct effect
- Fixed: In the admin configuration, when disabling Apple Pay on the product page or the cart, it wouldn't have the correct effect
- Fixed a multishipping page validation error with older versions of Magento 2

## 1.0.0 - 2019-05-14

Initial release.
