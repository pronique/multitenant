<?php

/**
 * MultiTenant Plugin
 * Copyright (c) PRONIQUE Software (http://pronique.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) PRONIQUE Software (http://pronique.com)
 * @link          http://github.com/pronique/multitenant MultiTenant Plugin Project
 * @since         0.5.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace MultiTenant\Core;

use Cake\Core\StaticConfigTrait;
use Cake\Core\Exception\Exception;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

//TODO Implement Singleton/Caching to eliminate sql query on every call
class MTApp
{

    use StaticConfigTrait {
        config as public _config;
    }

    protected static $_cachedAccounts = [];


    /**
     * find the current context based on domain/subdomain
     *
     * @return string 'global', 'tenant', 'custom'
     *
     */
    public static function getContext()
    {
        //get tenant qualifier
        $qualifier = self::_getTenantQualifier();

        if ($qualifier == '') {
            return 'global';
        }

        return 'tenant';
    }

    /**
     * Checks if the current tenant is primary.
     *
     * @returns boolean Primary domain indicator
     *
     */
    public static function isPrimary()
    {
        // Get tenant qualifier
        $qualifier = self::_getTenantQualifier();

        // The domain is primary if we are not in any subdomain, or if it is a primary subdomain.
        return $qualifier == '' || in_array($qualifier, self::config('primarySubdomains'));
    }

    /**
     *
     * Can be used throughout Application to resolve current tenant
     * Returns tenant entity
     *
     * @returns Cake\ORM\Entity
     */
    public static function tenant()
    {
        //if tentant/_findTenant is called at the primary domain the plugin is being used wrong;
        if (self::isPrimary()) {
            throw new Exception('MTApp::tenant() cannot be called from primaryDomain context');
        }

        $tenant = static::_findTenant();

        //Check for inactive/nonexistant domain
        if (!$tenant) {
            self::_redirectInactive();
        }

        return $tenant;
    }


    /**
     * Return the current tenant data.
     *
     * @return Entity Current tenant entity.
     */
    protected static function _findTenant()
    {
        // if tentant/_findTenant is called at the primary domain the plugin is being used wrong;
        if (self::isPrimary()) {
            throw new Exception('MTApp::tenant() cannot be called from primaryDomain context');
        }

        //get tenant qualifier
        $qualifier = self::_getTenantQualifier();

        //Read entity from cache if it exists
        if (array_key_exists($qualifier, self::$_cachedAccounts)) {
            return self::$_cachedAccounts[$qualifier];
        }

        //load model
        $modelConf = self::config('model');
        $tbl = TableRegistry::get($modelConf['className']);
        $conditions = array_merge([$modelConf['field'] => $qualifier], $modelConf['conditions']);

        //Query model and store in cache
        self::$_cachedAccounts[$qualifier] = $tbl->find('all', ['skipTenantCheck' => true])->where($conditions)->first();

        return self::$_cachedAccounts[$qualifier];
    }


    /**
     * Redirects inactive tenants to the same URI withing the primary domain.
     */
    protected static function _redirectInactive()
    {
        $uri = self::config('redirectInactive');

        if (strpos($uri, 'http') !== false) {
            $fullUri = $uri;
        } else {
            $fullUri = env('REQUEST_SCHEME') . '://' . self::config('primaryDomain') . $uri;
        }

        header('Location: ' . $fullUri);
        exit;
    }


    /**
     * Returns the current tenant qualifier extracting it from the server name
     * without the primaryDomain.
     *
     * @return string Tenant qualifier
     */
    protected static function _getTenantQualifier()
    {
        $tenant = '';
        //for domain this is the SERVER_NAME from $_SERVER
        if (self::config('strategy') == 'domain') {
            // check if tenant is available and server name valid
            if (substr_count(env('SERVER_NAME'), self::config('primaryDomain')) > 0 &&
                substr_count(env('SERVER_NAME'), '.') > 1) {
                $tenant = str_replace('.' . self::config('primaryDomain'), '', env('SERVER_NAME'));
            }
        }

        return $tenant;
    }
}
