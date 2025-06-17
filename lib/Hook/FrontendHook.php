<?php

/**
 * @copyright 2021 WebStollen GmbH
 * @link https://www.webstollen.de
 */

namespace Plugin\ws5_mollie\lib\Hook;

use Exception;
use JTL\Shop;
use Plugin\ws5_mollie\lib\Model\QueueModel;
use Plugin\ws5_mollie\lib\PluginHelper;
use WS\JTL5\V2_0_5\Hook\AbstractHook;

class FrontendHook extends AbstractHook
{
    /**
     * @param array $args_arr
     * @throws Exception
     */
    public static function execute($args_arr = []): void
    {
        try {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                return;
            }

            // Reset CreditCard-Token after Order!
            if (
                ($key = sprintf('kPlugin_%d_creditcard', PluginHelper::getPlugin()->getID()))
                && array_key_exists($key, $_SESSION) && !array_key_exists('Zahlungsart', $_SESSION)
            ) {
                unset($_SESSION[$key]);
            }

            // append queue script each X request according to plugin setting "asyncModulo"
            if (PluginHelper::getSetting('queue') === 'async') {
                $modulo = 1;
                $settingValue = intval(PluginHelper::getSetting('asyncModulo'));
                if ($settingValue && $settingValue > 1) {
                    $modulo = $settingValue;
                }
                $random = rand(1, $modulo);
                if ($random === 1) {
                    Shop::Smarty()->assign('wsQueueURL', Shop::getURL() . '/ws5_mollie/queue');
                    pq('body')->append(Shop::Smarty()->fetch(PluginHelper::getPlugin()->getPaths()->getFrontendPath() . 'template/queue.tpl', false));
                }
            }
        } catch (Exception $e) {
        }
    }
}
