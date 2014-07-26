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
namespace MultiTenant\Error;

class DataScopeViolationException extends MultiTenantException {

/**
 * Constructor
 *
 * @param string $message If no message is given 'DataScopeViolation' will be the message
 * @param int $code Status code, defaults to 400
 */
	public function __construct($message = null, $code=null) {
		if (empty($message)) {
			$message = 'DataScopeViolation';
		}
		parent::__construct($message, $code);
	}

}