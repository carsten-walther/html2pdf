<?php
defined('TYPO3_MODE') || die('Access denied.');

// register report for the TYPO3 report module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['HTML 2 PDF'][] = \Walther\Html2Pdf\Report::class;
