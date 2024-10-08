<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Html2pdf',
    'description' => 'A wrapper to let TYPO3 generate PDF files from html pages. Uses wkhtmltopdf, a binary that is using the print functionality of the webkit render engine to create PDFs.',
    'category' => 'plugin',
    'author' => 'Carsten Walther',
    'author_email' => 'walther.carsten@web.de',
    'author_company' => '',
    'state' => 'stable',
    'clearcacheonload' => true,
    'uploadfolder' => true,
    'version' => '12.4.2',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-12.4.99'
        ]
    ]
];
