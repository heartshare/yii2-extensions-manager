<?php

namespace DevGroup\ExtensionsManager;

use DevGroup\ExtensionsManager\helpers\ExtensionFileWriter;
use DevGroup\ExtensionsManager\models\Extension;
use Yii;
use yii\base\Module;
use yii\helpers\FileHelper;

/**
 * Class ExtensionsManager is the main module in extensions-manager package.
 *
 * @package DevGroup\ExtensionsManager
 */
class ExtensionsManager extends Module
{
    /**
     * Set of constant listed below describes DeferredGroup. Using them we can define what kind of activity was performed.
     * COMPOSER_INSTALL_DEFERRED_GROUP and COMPOSER_UNINSTALL_DEFERRED_GROUP are used in
     * DeferredQueueCompleteHandler as identifiers to rewrite self::$extensionsStorage file after
     * extension install or uninstall process.
     */
    const COMPOSER_INSTALL_DEFERRED_GROUP = 'ext_manager_composer_install';
    const COMPOSER_UNINSTALL_DEFERRED_GROUP = 'ext_manager_composer_uninstall';
    const EXTENSION_ACTIVATE_DEFERRED_GROUP = 'ext_manager_extension_activate';
    const EXTENSION_DEACTIVATE_DEFERRED_GROUP = 'ext_manager_extension_deactivate';
    const EXTENSION_DUMMY_DEFERRED_GROUP = 'ext_manager_dummy_report';

    /**
     * Next set of constants describes tasks what can be performed from front-end. All of this used as a data attributes
     * in buttons markup and then in the ExtensionsController to define what task we need to do.
     */
    const INSTALL_DEFERRED_TASK = 'install-def-task';
    const UNINSTALL_DEFERRED_TASK = 'uninstall-def-task';
    const ACTIVATE_DEFERRED_TASK = 'activate-def-task';
    const DEACTIVATE_DEFERRED_TASK = 'deactivate-def-task';
    const CHECK_UPDATES_DEFERRED_TASK = 'check-updates-def-task';

    /**
     * Migration directions used in the ExtensionsController
     */
    const MIGRATE_TYPE_DOWN = 'down';
    const MIGRATE_TYPE_UP = 'up';

    /**
     * ConfigurationUpdater component is used for writing application configs.
     * You can configure it as usual yii2 Component.
     *
     * @var \DevGroup\ExtensionsManager\helpers\ConfigurationUpdater
     */
    public $configurationUpdater = [
        'class' => 'DevGroup\ExtensionsManager\helpers\ConfigurationUpdater',
        'configs' => [
            'common-generated' => 'commonApplicationAttributes',
            'web-generated' => 'webApplicationAttributes',
            'console-generated' => 'consoleApplicationAttributes',
            'params-generated' => 'appParams',
            'aliases-generated' => 'aliases',
        ],
    ];

    /**
     * Location of extensions storage file. Aliases can be used.
     * File contains of a php return statement with array consisting elements with the following information:
     * - composer_name - name of composer package
     * - composer_type - type of composer package(if is set in composer.json of package)
     * - is_active - boolean, if this extension is active
     * - is_core - boolean, true if it is core extension and should be protected from uninstalling
     *
     * @var string
     */
    public $extensionsStorage = '@app/config/extensions.php';

    /** @var string Packegist URL */
    public $packagistUrl = 'https://packagist.org';

    /**
     * @var string token to access api.github.com.
     * Without this token you will able to perform 60 requests daily only
     */
    public $githubAccessToken;

    /**
     * @var string  due to https://developer.github.com/v3/#user-agent-required
     * you have to pass username or application name in headers while requesting github API
     */
    public $applicationName = 'DevGroup-ru/yii2-extensions-manager';

    /** @var string github API URL */
    public $githubApiUrl = 'https://api.github.com';

    /** @var int number of Extension shown on Extension Search and Extension Index pages */
    public $extensionsPerPage = 10;

    /** @var string path to composer file */
    public $composerPath = './composer.phar';

    /** @var int Show detailed output for composer commands */
    public $verbose = 0;

    /** @var string path to store ignored from git local composer.json file */
    private $localExtensionsPath = '@app/extensions';

    /** @var array default contents of local ignored composer.json */
    private $composerArray = [
        "name" => "devgroup/ext-meta-package",
        "description" => "File to store local extensions",
        "minimum-stability" => "dev",
        "require" => [

        ],
        'config' => [
            'vendor-dir' => '../vendor',
            'process-timeout' => 1800,
            'preferred-install' => 'dist',
            'store-auths' => true
        ],
        'extra' => [
            'asset-installer-paths' => [
                'npm-asset-library' => '../vendor/npm',
                'bower-asset-library' => '../vendor/bower'
            ]
        ]
    ];

    /**
     * @var array Array of extensions descriptions
     */
    private $extensions = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        ExtensionFileWriter::checkLocalStorage(
            Yii::getAlias($this->localExtensionsPath),
            $this->composerArray
        );
        parent::init();
        $this->configurationUpdater = Yii::createObject($this->configurationUpdater);
    }

    /**
     * Returns Extension[] or one Extension array by package name found in self::$extensionsStorage.
     *
     * @param string $packageName
     * @param bool $ignoreCache
     * @return Extension|models\Extension[]
     */
    public function getExtensions($packageName = '', $ignoreCache = false)
    {
        if (count($this->extensions) === 0 || true === $ignoreCache) {
            $fileName = Yii::getAlias($this->extensionsStorage);
            $canLoad = false;
            if (true === file_exists($fileName) && true === is_readable($fileName)) {
                $canLoad = true;
            } else {
                $canLoad = ExtensionFileWriter::generateConfig();
            }
            if (true === $canLoad) {
                $this->extensions = include $fileName;
            } else {
                Yii::$app->session->setFlash('error', Yii::t('extensions-manager', 'Unable to create extensions file'));
            }
        }
        if (false === empty($packageName)) {
            return isset($this->extensions[$packageName]) ? $this->extensions[$packageName] : [];
        }
        return $this->extensions;
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function extensionIsActive($packageName)
    {
        $ext = $this->getExtensions($packageName, true);
        if (true === isset($ext['is_active'])) {
            return 1 == $ext['is_active'];
        }
        return false;
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function extensionIsCore($packageName)
    {
        $ext = $this->getExtensions($packageName);
        if (true === isset($ext['is_core'])) {
            return 1 == $ext['is_core'];
        }
        return false;
    }

    /**
     * @return array
     */
    public static function navLinks()
    {
        return $navItems = [
            'index' => [
                'label' => Yii::t('extensions-manager', 'Extensions'),
                'url' => ['/extensions-manager/extensions/index'],
            ],
            'search' => [
                'label' => Yii::t('extensions-manager', 'Search'),
                'url' => ['/extensions-manager/extensions/search'],
            ],
            'config' => [
                'label' => Yii::t('extensions-manager', 'Configuration'),
                'url' => ['config', 'sectionIndex' => 0],
            ],
        ];
    }

    /**
     * @return bool|string
     */
    public function getLocalExtensionsPath()
    {
        return Yii::getAlias($this->localExtensionsPath);
    }

    /**
     * @return ExtensionsManager|null
     */
    public static function module()
    {
        if (null === $module = Yii::$app->getModule('extensions-manager')) {
            $module = new self('extensions-manager');
        }
        return $module;
    }
}
