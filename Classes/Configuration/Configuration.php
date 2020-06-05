<?php

namespace Walther\Html2pdf\Configuration;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;

/**
 * Class Configuration
 *
 * @package Walther\Html2pdf\Configuration
 */
class Configuration
{
    /**
     * EXTENSION_KEY
     */
    public const EXTENSION_KEY = 'html2pdf';

    /**
     * @var array
     */
    protected static $data = [];

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected static $languageService;

    /**
     * Configuration constructor.
     *
     * @param \TYPO3\CMS\Core\Localization\LanguageService $languageService
     */
    public function __construct(LanguageService $languageService)
    {
        self::$languageService = $languageService;
        self::$languageService->includeLLFile('EXT:html2pdf/Resources/Private/Language/locallang.xlf');
    }

    /**
     * set
     *
     * set a value of an array of values
     *
     * @param      $name
     * @param null $value
     *
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public static function set($name, $value = null) : void
    {
        if (is_string($name)) {
            self::$data[$name] = $value;
        } elseif (is_array($name)) {
            self::$data = array_merge(self::$data, $name);
        } else {
            throw new InvalidArgumentException(sprintf(self::$languageService->getLL('html2pdf.config.set.error'), $name, self::EXTENSION_KEY));
        }
    }

    /**
     * getBinaryPath
     *
     * get the binary path
     *
     * @return mixed|string
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public static function getBinaryPath()
    {
        $binPath = self::get('binPath');

        if ($binPath === 'custom') {
            return self::get('binPathCustom');
        }

        return ExtensionManagementUtility::extPath(self::EXTENSION_KEY, 'Vendor/wkhtmltopdf/' . $binPath);
    }

    /**
     * get
     *
     * @param $name
     *
     * @return mixed
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public static function get($name)
    {
        self::init();

        if (!self::exists($name)) {
            throw new InvalidArgumentException(sprintf(self::$languageService->getLL('html2pdf.config.get.error'), $name, self::EXTENSION_KEY));
        }

        return self::$data[$name];
    }

    /**
     * init
     *
     * initializing method that will be called as soon as it is needed
     */
    protected static function init() : void
    {
        if (empty(self::$data)) {
            self::$data = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][self::EXTENSION_KEY];
        }
    }

    /**
     * exists
     *
     * check if the value exists
     *
     * @param $name
     *
     * @return boolean
     */
    public static function exists($name) : bool
    {
        self::init();

        return is_array(self::$data) && array_key_exists($name, self::$data);
    }
}
