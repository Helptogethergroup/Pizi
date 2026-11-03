<?php

return [
    /*
     | Can tenants pay rent online (the /pay/{bill} link and "Pay now" in the tenant portal)?
     | Switched OFF while online collection would make the company liable for 18% GST.
     | To turn it on again set RENT_ONLINE_PAYMENT=true in .env.
     |
     | Off means: the link only shows the bill and asks the tenant to pay the PG owner directly;
     | no new Razorpay orders are made. A payment that was already started can still finish and
     | is recorded, so no money is ever taken without a record.
     */
    'online_payment' => (bool) env('RENT_ONLINE_PAYMENT', false),

    'offline_message' => 'Online rent payment is switched off for now. Please pay your PG owner directly (cash or UPI) and ask for a receipt.',
];
