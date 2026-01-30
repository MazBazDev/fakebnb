<?php

return [
    'vat_rate' => (float) env('PAYMENT_VAT_RATE', 0.20),
    'service_fee_rate' => (float) env('PAYMENT_SERVICE_FEE_RATE', 0.07),
    'commission_rate' => (float) env('PAYMENT_PLATFORM_COMMISSION_RATE', 0.12),
];
