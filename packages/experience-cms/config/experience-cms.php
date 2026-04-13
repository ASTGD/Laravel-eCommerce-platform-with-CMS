<?php

return [
    /*
     * Native Bagisto storefront is the default.
     * Set to "cms" when the structured CMS storefront should override
     * the public home/category/product routes and preview routes.
     */
    'storefront_mode' => env('EXPERIENCE_CMS_STOREFRONT_MODE', 'native'),
];
