<?php

return [
    'frontend' => [
        'CarstenWalther/Html2pdf/Output' => [
            'target' => \CarstenWalther\Html2pdf\Middleware\Output::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ]
    ]
];