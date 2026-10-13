<?php

declare(strict_types=1);

return [
    'error.not_found' => 'Page or file not found.',
    'error.bad_run_key' => 'The spike run key is missing or wrong. Open the link Claude sent, unchanged.',
    'error.validation_failed' => 'The submitted data is not valid.',
    'error.too_large' => 'The submitted data is too large.',
    'error.not_png' => 'The image is not a PNG.',
    'error.cloud_key_missing' => 'No LiveKit Cloud key in the key file.',
    'error.bad_return_url' => 'The return address is not our app.',
    'error.server_error' => 'The spike server hit an error.',

    'start.title' => 'Flutter web spike',
    'start.intro' => 'Start page for WP-0.5. The automatic run opens every variant several times and records the timings.',
    'start.auto' => 'Run measurements automatically',
    'start.auto_hint' => 'About :n loads in a row. Keep the screen on and do not touch the device until it says it is done.',
    'start.variants' => 'Open one variant',
    'variant.wasm/iso' => 'WebAssembly build with isolation headers',
    'variant.wasm/plain' => 'WebAssembly build without isolation headers',
    'variant.js/iso' => 'JavaScript build with isolation headers',
    'variant.js/plain' => 'JavaScript build without isolation headers',
    'variant.wasmx/iso' => 'Experiment: WebAssembly on every browser',

    'auto.title' => 'Automatic measurement',
    'auto.keep_screen_on' => 'Keep the screen on. The page moves between the app variants by itself.',
    'auto.step' => 'Load :i of :n',
    'auto.cleaning' => 'Clearing caches before the next variant…',
    'auto.done' => 'Measurement finished. The results reached Claude; you can close the page.',
    'auto.back' => 'Back to the start page',

    'pay.title' => 'Test payment page',
    'pay.fake' => 'A fake page on another site. No money moves and no card is asked for.',
    'pay.pay' => 'Pay',
    'pay.cancel' => 'Cancel',
    'pay.no_opener' => 'This window cannot talk to the app. Close it and go back.',
    'pay.sent' => 'The result was sent to the app.',
];
