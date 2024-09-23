<?php

defined('TYPO3') or die();

// register report for the TYPO3 report module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['html2pdf'][] = \CarstenWalther\Html2pdf\Report\ExtensionStatus::class;

$typo3Version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);

if ($typo3Version->getMajorVersion() < 12) {
    // Register hook for post-processing of page content before being cached:
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']['html2pdf'][] = \CarstenWalther\Html2pdf\Hook\ContentPostProc::class . '->process';
}