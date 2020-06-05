<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Html2pdf',
    'description' => 'A wrapper to let TYPO3 generate PDF files from html pages. Uses wkhtmltopdf, a binary that is using the print functionality of the webkit render engine to create PDFs.',
    'category' => 'plugin',
    'author' => 'Carsten Walther',
    'author_email' => 'walther.carsten@web.de',
    'state' => 'beta',
    'internal' => 0,
    'uploadfolder' => 0,
    'createDirs' => 0,
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '9.5.0-9.5.99',
            'filemetadata' => '9.5'
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);
