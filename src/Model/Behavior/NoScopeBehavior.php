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

class NoScopeBehavior extends Behavior {
	
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
			if ( $entity->isNew() ) {

				// Model is no required to have a foreign_key_field to tenant,
				// But if one exists we will update it

				// no overwrite, if foreign_keyfield has an assigned value, do nothing
				if ( $entity->{$field} === null ) {

					$entity->{$field} = MTApp::tenant()->id;
				}

			}

		}

		return true;
	}



}