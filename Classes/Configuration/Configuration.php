<?php

namespace CarstenWalther\Html2pdf\Configuration;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;

class Configuration
{
    /**
     * EXTENSION_KEY
     */
    public const EXTENSION_KEY = 'html2pdf';

    /**
     * @var array
     */
    protected static array $data = [];

    /**
     * @var LanguageService
     */
    protected static LanguageService $languageService;

    /**
     * @param LanguageServiceFactory $languageServiceFactory
     */
    public function __construct(protected readonly LanguageServiceFactory $languageServiceFactory)
    {
        self::$languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }

    /**
     * @param mixed $name
     * @param $value
     * @return void
     * @throws InvalidArgumentException
     */
    public static function set(mixed $name, $value = null): void
    {
        if (is_string($name)) {
            self::$data[$name] = $value;
        } elseif (is_array($name)) {
            self::$data = array_merge(self::$data, $name);
        } else {
            throw new InvalidArgumentException(sprintf(self::$languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.config.set.error'), $name, self::EXTENSION_KEY));
        }
    }

    /**
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function getBinaryPath(): mixed
    {
        $binPath = self::get('binPath');
        if ($binPath === 'custom') {
            return self::get('binPathCustom');
        }
        return ExtensionManagementUtility::extPath(self::EXTENSION_KEY, 'Resources/Private/bin/' . $binPath);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function get(string $name): mixed
    {
        self::init();
        if (!self::exists($name)) {
            throw new InvalidArgumentException(sprintf(self::$languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.config.get.error'), $name, self::EXTENSION_KEY));
        }
        return self::$data[$name];
    }

    /**
     * @return void
     */
    protected static function init(): void
    {
        if (empty(self::$data)) {
            self::$data = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][self::EXTENSION_KEY];
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function exists(string $name): bool
    {
        self::init();
        return is_array(self::$data) && array_key_exists($name, self::$data);
    }
}
