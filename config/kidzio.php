<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product version
    |--------------------------------------------------------------------------
    |
    | Shown on About and Settings. There is no store listing, so this is the
    | running app version rather than a marketplace build number.
    |
    */

    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Support inbox
    |--------------------------------------------------------------------------
    |
    | Contact-us stores every message and also mails this address. There is
    | no app-store page and no live chat.
    |
    */

    'support_email' => env('SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

];
