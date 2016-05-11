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
namespace MultiTenant\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Association;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\Query;
use MultiTenant\Core\MTApp;
use MultiTenant\Error\DataScopeViolationException;
use MultiTenant\Error\MultiTenantException;

class TenantScopeBehavior extends Behavior {
	
/**
 * Keeping a reference to the table in order to,
 * be able to retrieve table/model attributes
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * Default config
 *
 * These are merged with user-provided config when the behavior is used.
 *
 *
 * @var array
 */
	protected $_defaultConfig = [
		'implementedFinders' => [],
		'implementedMethods' => [],
		'foreign_key_field'=>'account_id'
	];

/**
 * Constructor
 *
 *
 * @param \Cake\ORM\Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {

		//Merge $config with application-wide scopeBehavior config
		$config = array_merge( MTApp::config( 'scopeBehavior' ), $config );
		parent::__construct($table, $config);

		$this->_table = $table;

	}

/**
 * beforeFind callback
 *
 * inject where condition if context is 'tenant'
 *
 * @param \Cake\Event\Event $event The afterSave event that was fired.
 * @param \Cake\ORM\Query $query The query.
 * @return void
 */
	public function beforeFind( Event $event, Query $query, $options) {


		// if context is tenant, add conditions to query
		if ( MTApp::getContext() == 'tenant') {

			// check if find option has "skipTenant", recursive error fix
			if (!isset($options['skipTenantCheck']) || $options['skipTenantCheck'] !== true) {

				// secure the configured tenant table by adding a primary key condition
				if ($this->_table->alias() === MTApp::config('model')['className']) {
					$query->where([$this->_table->alias().'.'.$this->_table->primaryKey()=>MTApp::tenant()->id]);
				} else {
					$query->where([$this->_table->alias().'.'.$this->config('foreign_key_field')=>MTApp::tenant()->id]);
				}
			}
		}

		// tenant scope does not allow global context
		else {
			throw new DataScopeViolationException('Tenant Scoped accessed globally');
		}
		return $query;
	}

/**
 * beforeSave callback
 *
 * Prevent saving if the context is not global
 *
 * @param \Cake\Event\Event $event The beforeSave event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
	public function beforeSave( Event $event, Entity $entity, $options ) {

		if ( MTApp::getContext() == 'tenant' ) { //save new operation

			$field = $this->config('foreign_key_field');
			if ( $entity->isNew()) {
				
				//blind overwrite, preventing user from providing explicit value except primary domain
				if($this->_table->alias() !== MTApp::config('model')['className']){
					$entity->{$field} = MTApp::tenant()->id;
				}

			} else { //update operation

				//paranoid check of ownership
				if ( $entity->{$field} != MTApp::tenant()->id ) { //current tenant is NOT owner
					throw new DataScopeViolationException('Tenant->id:' . MTApp::tenant()->id . ' does not own '.$this->_table->alias().'->id:' . $entity->id );
				}
				
			} // end if

		} 

		// tenant scope does not allow global context
		else {
			throw new DataScopeViolationException('Tenant Scoped accessed globally');
		}

		return true;
	}

/**
 * beforeDelete callback
 *
 * Prevent delete if the context is not global
 *
 * @param \Cake\Event\Event $event The beforeDelete event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
	public function beforeDelete( Event $event, Entity $entity, $options ) {

		if ( MTApp::getContext() == 'tenant' ) { 

			$field = $this->config('foreign_key_field');

			//paranoid check of ownership
			if ( $entity->{$field} != MTApp::tenant()->id ) { //current tenant is NOT owner
				throw new DataScopeViolationException('Tenant->id:' . MTApp::tenant()->id . ' does not own '.$this->_table->alias().'->id:' . $entity->id );
			}

		} 

		// tenant scope does not allow global context
		else {
			throw new DataScopeViolationException('Tenant Scoped accessed globally');
		}

		return true;
	}
	

}