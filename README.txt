Installation
------------

1. Extract and copy modules directory into WHMCS directory
2. In Admin Panel go to Setup>Other>Order Statuses and add an Order Status with name "Processed" and "Include in Pending" option. This status will be used as paid but not activated yet.
3. In Admin Panel go to Setup>Payments>Payment Gateways, activate Paysera.com/Mokejimai.lt module and fill in all information.
Note: all fields are required
4. In Paysera.com project make sure you have ticked "Redirect customers to accepturl link only after the confirmation of the payment" in
Macro payments

Contacts
--------

If any problems occur please feel free to seek help via support@paysera.com

Fix
---

2013.03.06 Fixed paysera exception when paying via printed invoices