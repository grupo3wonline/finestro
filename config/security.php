<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Account security and reminder policy
|--------------------------------------------------------------------------
*/

return [

    // A password must be changed every N days. After that the user can only
    // reach the "change password" screen until a new password is set.
    'password_max_age_days' => (int) env('PASSWORD_MAX_AGE_DAYS', 90),

    // Show the in-app alert and send the e-mail reminder when the password
    // expires within N days.
    'password_warning_days' => (int) env('PASSWORD_WARNING_DAYS', 7),

    // Show the in-app alert and send the e-mail reminder when the Professional
    // subscription period ends (renews or lapses) within N days.
    'renewal_warning_days' => (int) env('RENEWAL_WARNING_DAYS', 7),

];
