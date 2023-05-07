<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 *  @author DNK Soft <i@prestashop.world>
 *  @copyright  2021-2022 DNK Soft
 *  @license    Valid for 1 website (or project) for each purchase of license
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Dnk_clearcachecron extends Module
{
    public $log_file;

    public function __construct()
    {
        $this->name = 'dnk_clearcachecron';
        $this->tab = 'administration';
        $this->version = '1.0.3';
        $this->author = 'DNK Soft';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Regularly clear cache with a time period or cron job');
        $this->description = $this->l('You can clear the Prestashop cache at the frequency you want, without having to do it manually in the Admin.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->log_file = $this->getLocalPath() . 'clear_cache.log';
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('DNK_CLEARCACHECRON_NON_CRON', false);
        Configuration::updateValue('DNK_CLEARCACHECRON_TOKEN', bin2hex(random_bytes(5)));
        Configuration::updateValue('DNK_CLEARCACHECRON_NEXT_JOB', null);
        Configuration::updateValue('DNK_CLEARCACHECRON_TIME', 0);
        Configuration::updateValue('DNK_CLEARCACHECRON_PERIOD', 0);
        Configuration::updateValue('DNK_CLEARCACHECRON_CACHE_SMARTY', true);
        Configuration::updateValue('DNK_CLEARCACHECRON_CACHE_SSF', true);
        Configuration::updateValue('DNK_CLEARCACHECRON_CACHE_MEDIA', true);
        Configuration::updateValue('DNK_CLEARCACHECRON_CACHE_XML', true);
        Configuration::updateValue('DNK_CLEARCACHECRON_CACHE_APC', false);
        Configuration::updateValue('DNK_CLEARCACHECRON_CACHE_OPCACHE', false);
        Configuration::updateValue('DNK_CLEARCACHECRON_DELETE_LOG', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('DNK_CLEARCACHECRON_NON_CRON');
        Configuration::deleteByName('DNK_CLEARCACHECRON_TOKEN');
        Configuration::deleteByName('DNK_CLEARCACHECRON_NEXT_JOB');
        Configuration::deleteByName('DNK_CLEARCACHECRON_TIME');
        Configuration::deleteByName('DNK_CLEARCACHECRON_PERIOD');
        Configuration::deleteByName('DNK_CLEARCACHECRON_CACHE_SMARTY');
        Configuration::deleteByName('DNK_CLEARCACHECRON_CACHE_SSF');
        Configuration::deleteByName('DNK_CLEARCACHECRON_CACHE_MEDIA');
        Configuration::deleteByName('DNK_CLEARCACHECRON_CACHE_XML');
        Configuration::deleteByName('DNK_CLEARCACHECRON_CACHE_APC');
        Configuration::deleteByName('DNK_CLEARCACHECRON_CACHE_OPCACHE');
        Configuration::deleteByName('DNK_CLEARCACHECRON_DELETE_LOG');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&module_name=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&conf=6';
        $info = '';
        if (Tools::isSubmit('submitDnk_clearcachecronModule') == true) {
            $this->postProcess();
            Tools::redirectAdmin($currentIndex);
        }

        $log_content = nl2br(Tools::file_get_contents($this->log_file));
        $this->context->smarty->assign('log_content', $log_content);
        $this->context->smarty->assign('cron_link', Tools::getShopDomainSsl(true) . $this->_path . 'cache_clear.php?token=' . Configuration::get('DNK_CLEARCACHECRON_TOKEN'));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $info . $this->renderForm() . $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = true;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDnk_clearcachecronModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'col' => 4,
                        'label' => $this->l('Security Token') . ' <i class="icon icon-lock"></i>',
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Security Token for cron service URL. (alphanumeric characters a-Z,0-9)') . '<hr> Cron link <br><a target="_blank" href="' . $this->context->smarty->tpl_vars['cron_link']->value . '">' . $this->context->smarty->tpl_vars['cron_link']->value . '</a>',
                        'name' => 'DNK_CLEARCACHECRON_TOKEN',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Emulate Cron') . ' <i class="icon icon-beaker"></i>',
                        'name' => 'DNK_CLEARCACHECRON_NON_CRON',
                        'is_bool' => true,
                        'desc' => $this->l('Run job to clear cache with any request to your shop after a chosen time.'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Period') . ' <i class="icon  icon-reply"></i>',
                        'name' => 'DNK_CLEARCACHECRON_PERIOD',
                        'desc' => $this->l('Choose period for clear caches'),
                        'options' => [
                            'query' => [
                                [
                                    'id' => 0,
                                    'name' => $this->l('once a day'),
                                ],
                                [
                                    'id' => 1,
                                    'name' => $this->l('once a week'),
                                ],
                                [
                                    'id' => 2,
                                    'name' => $this->l('once a month'),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Time') . ' <i class="icon icon-time"></i>',
                        'name' => 'DNK_CLEARCACHECRON_TIME',
                        'desc' => $this->l('Choose time to start clear caches'),
                        'options' => [
                            'query' => [
                                [
                                    'id' => 0,
                                    'name' => $this->l('00:00'),
                                ],
                                [
                                    'id' => 1,
                                    'name' => $this->l('01:00'),
                                ],
                                [
                                    'id' => 2,
                                    'name' => $this->l('02:00'),
                                ],
                                [
                                    'id' => 3,
                                    'name' => $this->l('03:00'),
                                ],
                                [
                                    'id' => 4,
                                    'name' => $this->l('04:00'),
                                ],
                                [
                                    'id' => 5,
                                    'name' => $this->l('05:00'),
                                ],
                                [
                                    'id' => 6,
                                    'name' => $this->l('06:00'),
                                ],
                                [
                                    'id' => 7,
                                    'name' => $this->l('07:00'),
                                ],
                                [
                                    'id' => 8,
                                    'name' => $this->l('08:00'),
                                ],
                                [
                                    'id' => 9,
                                    'name' => $this->l('09:00'),
                                ],
                                [
                                    'id' => 10,
                                    'name' => $this->l('10:00'),
                                ],
                                [
                                    'id' => 11,
                                    'name' => $this->l('11:00'),
                                ],
                                [
                                    'id' => 12,
                                    'name' => $this->l('12:00'),
                                ],
                                [
                                    'id' => 13,
                                    'name' => $this->l('13:00'),
                                ],
                                [
                                    'id' => 14,
                                    'name' => $this->l('14:00'),
                                ],
                                [
                                    'id' => 15,
                                    'name' => $this->l('15:00'),
                                ],
                                [
                                    'id' => 16,
                                    'name' => $this->l('16:00'),
                                ],
                                [
                                    'id' => 17,
                                    'name' => $this->l('17:00'),
                                ],
                                [
                                    'id' => 18,
                                    'name' => $this->l('18:00'),
                                ],
                                [
                                    'id' => 19,
                                    'name' => $this->l('19:00'),
                                ],
                                [
                                    'id' => 20,
                                    'name' => $this->l('20:00'),
                                ],
                                [
                                    'id' => 21,
                                    'name' => $this->l('21:00'),
                                ],
                                [
                                    'id' => 22,
                                    'name' => $this->l('22:00'),
                                ],
                                [
                                    'id' => 23,
                                    'name' => $this->l('23:00'),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'dnk_title',
                        'title' => '<i class="icon icon-tasks"></i> Options for clear caches',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Clear Smarty Cache') . ' <i class="icon icon-lightbulb"></i>' . $this->helpBoxFetch($this->l('The smarty templates in themes and modules are cached in PrestaShop. These templates are stored in compiled form so that rendering is faster.')),
                        'name' => 'DNK_CLEARCACHECRON_CACHE_SMARTY',
                        'is_bool' => true,
                        'desc' => $this->l('Enable/Disable clear Smarty cache'),
                        'help' => 'text hint',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Clear Symfony Cache') . ' <i class="icon icon-suitcase"></i>' . $this->helpBoxFetch($this->l('This cache exists only in PrestaShop version >= 1.7.x.x. Symfony offers 3 types of the cache by default:
                                   Configuration: config, services (in YML, XML etc) Controllers: YML, Annotations/routing Doctrine: Entity mapping e.g. fields-columns, table')),
                        'name' => 'DNK_CLEARCACHECRON_CACHE_SSF',
                        'is_bool' => true,
                        'desc' => $this->l('Enable/Disable clear Symfony cache'),
                        'help' => 'text hint',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Clear Media Cache') . ' <i class="icon icon-picture"></i>' . $this->helpBoxFetch($this->l('This cache stores the assets cache i.e. CSS and JS files. You can enable/disable this cache in the CCC section of the Performance tab This cache is stored in themes/your-theme/assets/cache directory')),
                        'name' => 'DNK_CLEARCACHECRON_CACHE_MEDIA',
                        'is_bool' => true,
                        'desc' => $this->l('Enable/Disable clear cache Media'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Clear Media Cache') . ' <i class="icon icon-table"></i>' . $this->helpBoxFetch($this->l('The XML cache is stored in config/xml directory.')),
                        'name' => 'DNK_CLEARCACHECRON_CACHE_XML',
                        'is_bool' => true,
                        'desc' => $this->l('Enable/Disable clear XML cache'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Clear APC Cache') . ' <i class="icon icon-flag-checkered"></i>' . $this->helpBoxFetch($this->l('APC is opcode cache for PHP scripts. <br> APC and OPcache enable sites to serve page content significantly faster. Using APC or Opcache with PrestaShop is a great way to speed up your site.')),
                        'name' => 'DNK_CLEARCACHECRON_CACHE_APC',
                        'is_bool' => true,
                        'desc' => $this->clearApcCache() ? 'Enable/Disable clear APC cache' : $this->l('APC Cache extension is not enabled on your server.'),
                        'disabled' => $this->clearApcCache() ? '' : 'disabled',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Clear OPcache') . ' <i class="icon icon-bolt"></i>' . $this->helpBoxFetch($this->l('OPcache is the Alternative PHP Cache (APC) - cache for PHP scripts. <br> APC and OPcache enable sites to serve page content significantly faster. Using APC or Opcache with PrestaShop is a great way to speed up your site.')),
                        'name' => 'DNK_CLEARCACHECRON_CACHE_OPCACHE',
                        'is_bool' => true,
                        'desc' => $this->clearOpCache() ? 'Enable/Disable clear OPcache cache' : $this->l('OPcache extension is not enabled on your server.'),
                        'disabled' => $this->clearOpCache() ? '' : 'disabled',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Delete Prestashop log files') . ' <i class="icon icon-puzzle-piece"></i>' . $this->helpBoxFetch($this->l('For PS <=1.7.3.0 /app/log <br> For PS > 1.7.3.0 /var/log')),
                        'name' => 'DNK_CLEARCACHECRON_DELETE_LOG',
                        'is_bool' => true,
                        'desc' => $this->l('Delete Prestashop log files from the filesystem'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            'DNK_CLEARCACHECRON_NON_CRON' => Configuration::get('DNK_CLEARCACHECRON_NON_CRON', true),
            'DNK_CLEARCACHECRON_TOKEN' => Configuration::get('DNK_CLEARCACHECRON_TOKEN', bin2hex(random_bytes(5))),
            'DNK_CLEARCACHECRON_PERIOD' => Configuration::get('DNK_CLEARCACHECRON_PERIOD', 0),
            'DNK_CLEARCACHECRON_TIME' => Configuration::get('DNK_CLEARCACHECRON_TIME', 0),
            'DNK_CLEARCACHECRON_CACHE_SMARTY' => Configuration::get('DNK_CLEARCACHECRON_CACHE_SMARTY', false),
            'DNK_CLEARCACHECRON_CACHE_SSF' => Configuration::get('DNK_CLEARCACHECRON_CACHE_SSF', false),
            'DNK_CLEARCACHECRON_CACHE_MEDIA' => Configuration::get('DNK_CLEARCACHECRON_CACHE_MEDIA', false),
            'DNK_CLEARCACHECRON_CACHE_XML' => Configuration::get('DNK_CLEARCACHECRON_CACHE_XML', false),
            'DNK_CLEARCACHECRON_DELETE_LOG' => Configuration::get('DNK_CLEARCACHECRON_DELETE_LOG', false),
            'DNK_CLEARCACHECRON_CACHE_APC' => Configuration::get('DNK_CLEARCACHECRON_CACHE_APC', false),
            'DNK_CLEARCACHECRON_CACHE_OPCACHE' => Configuration::get('DNK_CLEARCACHECRON_CACHE_OPCACHE', false),
        ];
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($key == 'DNK_CLEARCACHECRON_CACHE_APC' && !$this->clearApcCache()) {
                Configuration::updateValue($key, false);
            } elseif ($key == 'DNK_CLEARCACHECRON_CACHE_OPCACHE' && !$this->clearOpCache()) {
                Configuration::updateValue($key, false);
            } elseif ($key == 'DNK_CLEARCACHECRON_TOKEN' && !Validate::isDirName(Tools::getValue($key))) {
                Configuration::updateValue($key, bin2hex(random_bytes(5)));
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
        $this->SetNextJob();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        if (Configuration::get('DNK_CLEARCACHECRON_NON_CRON')) {
            $tmptime = strtotime('now');
            if ($tmptime >= Configuration::get('DNK_CLEARCACHECRON_NEXT_JOB')) {
                $this->ClearCache(Configuration::get('DNK_CLEARCACHECRON_TOKEN'));
                $this->SetNextJob();
            }
        }
    }

    /**
     * Delete directory and subdirectories.
     *
     * @param string $dirname Directory name
     */
    public static function deleteDirectory($dirname, $delete_self = true)
    {
        $dirname = rtrim($dirname, '/') . '/';
        if (file_exists($dirname)) {
            if ($files = scandir($dirname, SCANDIR_SORT_NONE)) {
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && $file != '.svn') {
                        if (is_dir($dirname . $file)) {
                            Tools::deleteDirectory($dirname . $file);
                        } elseif (file_exists($dirname . $file)) {
                            unlink($dirname . $file);
                        }
                    }
                }

                if ($delete_self && file_exists($dirname)) {
                    if (!rmdir($dirname)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Calculate next time to start cron job
     */
    public function SetNextJob()
    {
        $t_diff = strtotime('now') >= strtotime('today ' . Configuration::get('DNK_CLEARCACHECRON_TIME') . ':00');
        if (Configuration::get('DNK_CLEARCACHECRON_PERIOD') == '0' && $t_diff) {
            $nextjob = 'tomorrow';
        } elseif (Configuration::get('DNK_CLEARCACHECRON_PERIOD') == '0' && !$t_diff) {
            $nextjob = 'today';
        } elseif (Configuration::get('DNK_CLEARCACHECRON_PERIOD') == '1') {
            $nextjob = 'next week';
        } elseif (Configuration::get('DNK_CLEARCACHECRON_PERIOD') == '2') {
            $nextjob = 'next month';
        } else {
            $nextjob = 'today';
        }
        $nextjob = strtotime($nextjob . ' ' . Configuration::get('DNK_CLEARCACHECRON_TIME') . ':00');
        Configuration::updateValue('DNK_CLEARCACHECRON_NEXT_JOB', $nextjob);
    }

    /**
     * Clear cache methods
     */
    public function ClearCache($token)
    {
        if (Validate::isCleanHtml($token) && $token == Configuration::get('DNK_CLEARCACHECRON_TOKEN')) {
            if (Configuration::get('DNK_CLEARCACHECRON_CACHE_SMARTY')) {
                Tools::clearSmartyCache();
            }

            if (Configuration::get('DNK_CLEARCACHECRON_CACHE_SSF')) {
                $this->clearSf2Cache();
            }

            if (Configuration::get('DNK_CLEARCACHECRON_CACHE_SMARTY') || Configuration::get('DNK_CLEARCACHECRON_CACHE_SSF')) {
                $this->regenerateCache();
            }

            if (Configuration::get('DNK_CLEARCACHECRON_CACHE_MEDIA')) {
                Media::clearCache();
            }

            if (Configuration::get('DNK_CLEARCACHECRON_CACHE_XML')) {
                Tools::clearXMLCache();
            }

            if (Configuration::get('DNK_CLEARCACHECRON_CACHE_APC')) {
                $this->clearApcCache(true);
            }

            if (Configuration::get('DNK_CLEARCACHECRON_CACHE_OPCACHE')) {
                $this->clearOpCache(true);
            }

            if (Configuration::get('DNK_CLEARCACHECRON_DELETE_LOG')) {
                $this->deleteLogFiles();
            }

            $str = $this->l('Cronjob done. Cache was cleaned');
            $this->saveLog($str);
            PrestaShopLogger::addLog($str, 1, null, null, null, true);

            return true;
        }
        $str = $this->l('Cronjob error. Wrong security token!');
        $this->saveLog($str);
        PrestaShopLogger::addLog($str, 2, null, null, null, true);

        return false;
    }

    protected function saveLog(string $str = '')
    {
        $datetime = new DateTime();
        $log_string = $datetime->format('Y-m-d H:i:s') . ' | ' . $str;
        @file_put_contents($this->log_file, $log_string . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function regenerateCache()
    {
        Tools::generateIndex();
        Category::regenerateEntireNtree();
    }

    private function clearApcCache(bool $clear = false)
    {
        if (function_exists('apc_clear_cache')) {
            if ($clear) {
                apc_clear_cache();
                apc_clear_cache('user');
                apc_clear_cache('opcode');
            }

            return true;
        }

        return false;
    }

    private function clearOpCache(bool $clear = false)
    {
        if (function_exists('opcache_get_status')) {
            if ($clear) {
                opcache_reset();
            }

            return true;
        }

        return false;
    }

    private function deleteLogFiles()
    {
        self::deleteDirectory(_PS_CORE_DIR_ . $this->psLogPath(), false);
    }

    private function psLogPath()
    {
        return (Tools::version_compare(_PS_VERSION_, '1.7.3.0', '<=')) ? '/app/log/' : '/var/log/';
    }

    private function helpBoxFetch($text)
    {
        return '<span class="help-box" data-container="body" data-html="true" data-toggle="popover" data-trigger="hover" data-placement="top" title=""  data-content="' . $text . '" ></span>';
    }

    private function clearSf2Cache()
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.1.0', '<')) {
            $sf2Refresh = new \PrestaShopBundle\Service\Cache\Refresh();
            $sf2Refresh->addCacheClear(_PS_MODE_DEV_ ? 'dev' : 'prod');
            $sf2Refresh->execute();
        } else {
            Tools::clearSf2Cache();
        }
    }
}
