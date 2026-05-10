<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    /*
    | Voice browser client: use npm @twilio/voice-sdk (pinned in package.json) via Vite.
    | Admin UI: resources/js/app.js + AuthenticatedLayout.vue. Test pages: caller-page.js, receiver-page.js.
    | Do not load a different major/minor from jsDelivr for those surfaces or behavior can diverge.
    |
    | Twilio Console → Programmable Voice → TwiML Apps → your app:
    |   Voice URL = {TWILIO_WEBHOOK_PUBLIC_ORIGIN or APP_URL}/twilio/voice  (GET|POST; same host you set below)
    | Status callback URLs optional: .../twilio/voice/dial-status and .../twilio/voice/client-status
    |
    | Android staff incoming (FCM): create a Push Credential in Twilio and attach to access tokens if you
    | add push grants later; this Laravel app issues Voice JWT + Client identity for SDK register().
    */
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'api_key' => env('TWILIO_API_KEY'),
        'api_secret' => env('TWILIO_API_SECRET'),
        'twiml_app_sid' => env('TWIML_APP_SID'),
        'admin_identity' => env('ADMIN_IDENTITY'),
        /*
         * Twilio account home region for Voice JWT header (twr), e.g. us1, ie1, au1.
         * Leave empty for default US; set if Twilio Console shows a regional account (often fixes 53000 outside US).
         */
        'voice_home_region' => env('TWILIO_VOICE_HOME_REGION'),
        /*
         * Voice JS SDK edge / signaling PoP (e.g. singapore, sydney, ashburn). Leave empty for SDK default.
         * Must be valid; invalid values cause WebSocket/signaling issues (31005). Often set to singapore for Asia.
         */
        'voice_sdk_edge' => env('TWILIO_VOICE_SDK_EDGE', ''),
        /*
         * Public origin Twilio should call for Voice webhooks (dial status, client status).
         * Use when Laravel sees http://127.0.0.1 but Twilio must hit https://your-domain.com
         * (e.g. behind ngrok / reverse proxy). No trailing slash.
         */
        'webhook_public_origin' => env('TWILIO_WEBHOOK_PUBLIC_ORIGIN', ''),
    ],

    /*
    | Google Sign-In (mobile): Web OAuth 2.0 client ID used as serverClientId in Flutter.
    | The ID token's "aud" claim must match this value.
    */
    'google' => [
        'server_client_id' => env('GOOGLE_SERVER_CLIENT_ID'),
    ],

];
