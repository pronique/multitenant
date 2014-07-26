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

class MixedScopeBehavior extends Behavior {
	
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
		'implementedMethods' => [
			'owner' => 'owner'
		],
		'global_value'=>0,
		'foreign_key_field'=>'account_id'
	];

/**
 * Constructor
 *
 * If events are specified - do *not* merge them with existing events,
 * overwrite the events to listen on
 *
 * @param \Cake\ORM\Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {

		//Implement config from Config\app.php if defined
		//Must happen before parent::__construct() to maintain order
		$appMixedScopeBehaviorConfig = MTApp::config('MixedScopeBehavior' );
		if ( is_array($appMixedScopeBehaviorConfig ) AND array_key_exists('foreign_key_field', $appMixedScopeBehaviorConfig ) ) {
			$this->config('foreign_key_field', $appMixedBehaviorConfig['foreign_key_field'], false);
		} 
		if ( is_array($appMixedScopeBehaviorConfig ) AND array_key_exists('global_value', $appMixedScopeBehaviorConfig ) ) {
			$this->config('global_value', $appMixedScopeBehaviorConfig['global_value'], false);
		} 

		parent::__construct($table, $config);

		$this->_table = $table;

	}

/**
 * beforeFind callback
 *
 * inject where condition if context is 'tenant'
 *
 * @param \Cake\Event\Event $event The beforeFind event that was fired.
 * @param \Cake\ORM\Query $query The query.
 * @return void
 */
	public function beforeFind( Event $event, Query $query ) {
		if ( MTApp::getContext() == 'tenant' ) {
			$query->where(
				[
					$this->_table->alias().'.'.$this->config('foreign_key_field') . ' IN'=> [ 
						$this->config('global_value'),
						MTApp::tenant()->id 
					]
				] 
			);
		}

		return $query;
	}

/**
 * beforeSave callback.
 *
 * 
 *
 * @param \Cake\Event\Event $event The beforeSave event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
	public function beforeSave( Event $event, Entity $entity, $options ) {
		//pr( $options);
		//exit;
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

		if ( MTApp::getContext() == 'tenant' ) { //save new operation

			$field = $this->config('foreign_key_field');

			//tenant cannot delete global records
			if ( $entity->{$field} == $this->config('global_value') ) {
				return false;
			}

			//paranoid check of ownership
			if ( $entity->{$field} != MTApp::tenant()->id ) { //current tenant is NOT owner
				throw new DataScopeViolationException('Tenant->id:' . MTApp::tenant()->id . ' does not own '.$this->_table->alias().'->id:' . $entity->id );
			}

		}

		return true;
	}
	

	public function owner() {
		return MTApp::tenant();
	}

}