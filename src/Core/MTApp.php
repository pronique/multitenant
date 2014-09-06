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
use Cake\ORM\TableRegistry;

//TODO Implement Singleton/Caching to eliminate sql query on every call
class MTApp {
  
  use StaticConfigTrait {
    config as public _config;
  }

  protected static $_cachedAccounts = [];


 /**
  * find the current context based on domain/subdomain
  * 
  * @return String 'global', 'tenant', 'custom'
  *
  */
  public static function getContext() {
    //get tenant qualifier
    $qualifier = self::_getTenantQualifier();
    
    if ( $qualifier == self::config('primaryDomain') ) {
      return 'global';
    }

    return 'tenant';
  }

 /**
  *
  *
  */
  public static function isPrimary() {
    //get tenant qualifier
    $qualifier = self::_getTenantQualifier();
    
    if ( $qualifier == self::config('primaryDomain') ) {
      return true;
    }

    return false;
  }
  /**
  * 
  * Can be used throughout Application to resolve current tenant
  * Returns tenant entity
  * 
  * @returns Cake\ORM\Entity
  */
  public static function tenant( ) {

    //get tenant qualifier
    $qualifier = self::_getTenantQualifier();
    
    //if tentant/_findTenant is called at the primary domain the plugin is being used wrong;
    if ( self::isPrimary() ) {
      throw new Exception('MTApp::tenant() cannot be called from primaryDomain context');
    }

    $tenant =  static::_findTenant();

    //Check for inactive/nonexistant domain
    if ( !$tenant ) {
      self::_redirectInactive();
    }

    return $tenant;

  }

  
  protected static function _findTenant() {
    //get tenant qualifier
    $qualifier = self::_getTenantQualifier();
    
    //Read entity from cache if it exists
    if ( array_key_exists($qualifier, self::$_cachedAccounts)) {
      return self::$_cachedAccounts[$qualifier];
    }

    //load model
    $modelConf= self::config('model');
    $tbl = TableRegistry::get( $modelConf['className'] );

    //blend in config defined conditions
    $conditions = array_merge([$modelConf['field']=>$qualifier], $modelConf['conditions']);

    //Query model and store in cache
    self::$_cachedAccounts[$qualifier] = $tbl->find('all')->where($conditions)->first();

    return self::$_cachedAccounts[$qualifier];
  
  } 

  protected static function _redirectInactive() {
    
    $uri = self::config('redirectInactive');
    header( 'Location: ' . env('REQUEST_SCHEME') .'://' . self::config('primaryDomain') . $uri );
    exit;
  
  } 
  protected static function _getTenantQualifier() {
    //for domain this is the SERVER_NAME from $_SERVER
    if ( self::config('strategy') == 'domain' ) {
      return env('SERVER_NAME');
    }

  }
}