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

class GlobalScopeBehavior extends Behavior {
	
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
		'implementedMethods' => []
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
		//Merge $config with application-wide scopeBehavior config
		$config = array_merge( MTApp::config( 'scopeBehavior' ), $config );
		parent::__construct($table, $config);

		$this->_table = $table;

	}

/**
 * beforeFind callback
 *
 * @param \Cake\Event\Event $event The beforeFind event that was fired.
 * @param \Cake\ORM\Query $query The query.
 * @return void
 */
	public function beforeFind( Event $event, Query $query ) {

		return $query;
	}

/**
 * beforeSave callback
 *
 * Prevent saving if the context is tenant
 *
 * @param \Cake\Event\Event $event The beforeSave event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
	public function beforeSave( Event $event, Entity $entity, $options ) {
		//Prevent saving records in the implementing table if this is the tenant context
		if ( MTApp::getContext() == 'tenant' ) {
			return false;
		}

		return true;
	}


/**
 * beforeDelete callback
 *
 * Prevent delete in the tenant context
 *
 * @param \Cake\Event\Event $event The beforeDelete event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
	public function beforeDelete( Event $event, Entity $entity, $options ) {

		if ( MTApp::getContext() == 'tenant' ) { 
			return false;
		}

		return true;
	}

}