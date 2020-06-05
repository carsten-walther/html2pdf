<?php

namespace Walther\Html2Pdf;

/**
 * Class Config
 * @package Walther\Html2Pdf
 */
class Config
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
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected static $languageService;

    /**
     * Report constructor.
     */
    public function __construct()
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        self::$languageService = $this->objectManager->get(\TYPO3\CMS\Core\Localization\LanguageService::class);
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
            throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(sprintf(self::$languageService->getLL('html2pdf.config.set.error'), $name, self::EXTENSION_KEY));
        }
    }

    /**
     * init
     *
     * initializing method that will be called as soon as it is needed
     */
    protected static function init() : void
    {
        if (empty(self::$data)) {
            if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 9000000) {
                self::$data = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][self::EXTENSION_KEY];
            } else {
                self::$data = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::EXTENSION_KEY], NULL);
            }
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

        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(self::EXTENSION_KEY, 'Vendor/wkhtmltopdf/' . $binPath);
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
            throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(sprintf(self::$languageService->getLL('html2pdf.config.get.error'), $name, self::EXTENSION_KEY));
        }

        return self::$data[$name];
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
