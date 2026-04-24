<?php

return [

    /*
    |--------------------------------------------------------------------------
    | mPDF Khmer Font Configuration
    |--------------------------------------------------------------------------
    |
    | All font files must be present in storage/fonts/.
    | Family names here must match the font-family values used in CSS/blades.
    |
    */

    'font_dir' => storage_path('fonts'),

    'fontdata' => [

        // Khmer OS Siemreap – used as the primary body font
        'khmerossiemreap' => [
            'R'      => 'KhmerOSsiemreap.ttf',
            'useOTL' => 0xFF,
        ],

        // Khmer OS Muol Light – used for headings / bold display text
        'khmerosmuollight' => [
            'R'      => 'KhmerOSMuolLight.ttf',
            'useOTL' => 0xFF,
        ],

        // KhmerOS – generic fallback with normal + bold variants
        'khmeros' => [
            'R'      => 'khmeros_normal_f2d60cc001d19f6608710bf14d85db1e.ttf',
            'B'      => 'khmeros_bold_58d1ac25d214cfb243c0e3bfb55a7b90.ttf',
            'useOTL' => 0xFF,
        ],

    ],

    'default_font' => 'khmerossiemreap',

];
