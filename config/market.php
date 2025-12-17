<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Live Market Demo Settings
    |--------------------------------------------------------------------------
    |
    | These options control the behavior of the Live Market simulation.
    | When real shifts are scarce, the system can automatically inject
    | "Demo Shifts" to ensure the marketplace looks active and alive.
    |
    */

    'demo_enabled' => true,

    // If real open shifts are fewer than this, demo shifts will be added
    'demo_disable_threshold' => 15,

    // How many demo shifts to generate in the pool
    'demo_shift_count' => 20,
];
