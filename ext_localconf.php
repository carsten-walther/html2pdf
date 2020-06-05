<?php

defined('TYPO3_MODE') or die();

// register report for the TYPO3 report module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['html2pdf'][] = \Walther\Html2pdf\Report\ExtensionStatus::class;

// hook is called before outputting no matter if content comes from cache or was just generated
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \Walther\Html2pdf\Controller\Html2pdfController::class . '->hookOutput';

// called before page is written to cache (if it is supposed to)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = \Walther\Html2pdf\Controller\Html2pdfController::class;
