# MultiTenant

MultiTenant CakePHP Plugin - Use this plugin to easily build SaaS enabled web applications.

## Version notice

This plugin only supports CakePHP 3.x

The project is currently in development and is considered experimental at this stage.

## Introduction

The MultiTenant plugin is best to implement when you begin developing your application, but with some work
you should be able to adapt an existing application to use the Plugin.

The plugin currently implements the following multi-tenancy architecures (Strategy).

### Domain Strategy

* Shared Database, Shared Schema
* Single Application Instance
* Subdomain per Tenant


### Tenants

The plugin introduces the concept of Tenants, a tenant represents each customer account.

A tenant can have it's own users, groups, and records.  Data is scoped to the tenant, tenants are represented
in the database as Accounts (or any Model/Table you designate by configuration).

### Contexts

The MultiTenant plugin introduces the concept of Contexts, context is an intregal part of
how the multitenant plugin works.  There are two predefined contexts, 'tenant' and 'global'.

#### 'global' Context

By default, 'global' maps to www.mydomain.com and is where you will implement non-tenant parts of your
application. ie signup/register code.

#### 'tenant' Context

'tenant' context represents this tenant.  When the user is accessing the application at the subdomain, this is 
considered the 'tenant' context.

#### Custom Contexts

The plugin supports the definition of additional contexts, these custom contexts are mapped
to subdomains.  Your application you can now call `MTApp::getContext()` to implement context-aware
code. 

For example, create a context named 'admin' and map it to the subdomain 'admin.mydomain.com'.

Note: Contexts are not a replacement for role based authorization but in some cases may be complimentary.

### Scopes

Each of your application's Models can implement one of five Scope Behaviors that will dictate 
what data is accessible based on the context.  These scopes are implemented as CakePHP Behaviors.

#### GlobalScope

The data provided in a Global Scoped Model can be queried by any tenant but insert/update/delete operations
are not allowed in the 'tenant' Context.

#### TenantScope

The data provided in a Tenant Scoped Model can only queried by the owner (tenant).  Insert operations are 
scoped to the current tenant.  Update and Delete operations enfore ownership, so that Tenant1 cannot 
update/delete Tenant2's records.

#### MixedScope

Mixed Scope Models provide both Global records as well as Tenant scoped records in the same table.  When 
a tenant queries the table (in the 'tenant' context), that tenant's records are returned along with the
global records that exist in the table.

Any records the tenant inserts are scoped to the tenant.  Tenants cannot update/delete global 
records that exist in the table.  And of course tenants cannot select/insert/update/delete other tenant's
records in the table. 

#### SharedScope

Shared Scope Models act as a community data table.  Tenants can query all records in the table, including other
tenant's records.  Insert operations are scoped to the current tenant.  Tenants cannot update/delete other 
tenant's records.

#### NoScope

No Scope Models add scoping to the Model, it is a verbose way to express that a Model is not scoped at all.
If the table has an account_id field, the inserting tenant's id is used to notate who inserted the record.
Since scope is not enfored, any tenant can delete any record.

## Installation

### composer

The recommended installation method for this plugin is by using composer. Just add this to your `composer.json` configuration:

```json
{
	"require" : {
		"pronique/multitenant": "master-dev"
	}
}
```

### git clone

Alternatively you can just `git clone` the code into your application

```
git clone git://github.com/pronique/multitenant.git app/Plugin/MultiTenant
```

### git submodule

Or add it as a git module, this is recommended over `git clone` since it’s easier to keep up to date with development that way

```
git submodule add git://github.com/pronique/multitenant.git app/Plugin/MultiTenant
```

## Configuration

Add the following to your `app/Config/bootstrap.php`

```php
<?php
Plugin::load('MultiTenant', ['bootstrap' => true, 'routes' => false]);
?>
```

Add the following to the bottom of your applicaiton's Config\app.php

```php
/**
 * MultiTenant Plugin Configuration
 *
 *
 * ## Options
 *
 * - `strategy` - 'domain' is currently the only implemented strategy
 * - `primaryDomain` - The domain for the main application
 *    value to false, when dealing with older versions of IE, Chrome Frame or certain web-browsing devices and AJAX
 * - `model` - The model that represents the tenant, usually 'Accounts'
 * - `redirectInactive` - URI to redirect when the tenant is not active or does not exist.  This should be a uri at the
 *	  primary domain, usually your signup page or feature pitch page with call-to-action signup button.
 * - `reservedDomains` - An array of names that cannot be chosen at signup
 * - `contextMap` - Associative array used to define additional custom contexts besides 'global' and 'tenant', 
 *    i.e. when domain admin.domain.com is matched MTApp::getContext() will return the custom context 'admin'. 
 * - `ScopedBehavior` - Application wide defaults for the ScopedBehavior Behavior
 * - `MixedBehavior` - Application wide defaults for the MixedBehavior Behavior
 *
 */
	'MultiTenant' => [
		'strategy'=>'domain',
		'primaryDomain'=>'www.example.com',
		'model'=>[
		  'className'=>'Accounts',
		  'field'=>'domain', //field of model that holds subdomain/domain tenants
		  'conditions'=>['is_active'=>1] //query conditions to match active accounts
		],
		'redirectInactive'=>'/register',
		'reservedDomains'=>[
			'admin',
			'superuser',
			'system',
			'www'
		],
		'contextMap' => [
			'admin'=>'admin.example.com' //an example of a custom context
		],
		'scopeBehavior'=>[
			'global_value'=>0, //global records are matched by this value
			'foreign_key_field'=>'account_id' //the foreign key field that associates records to tenant model
		]
	]
```

Note:  don't forget to add the , to the bottom config section when pasting the above configuration.  A syntax error in Config\app.php is a silent failure (blank page). 

## Usage

### MTApp

`MTApp` is a static class that you can call from anywhere in your application.

```php
use MultiTenant\Core\MTApp;

//Returns an entity of the current tenant
$tenant = MTApp::tenant();
echo $tenant->id;
//output 1

//Or the same thing in a single line;
echo MTApp::tenant()->id;
//output 1

//Another Example, you can reference any field in the underlying model
echo MTApp::tenant()->name;
//output Acme Corp.
```

```php
use MultiTenant\Core\MTApp;

// Based on URL, we are in a tenant's sudomain, customera.example.com
echo MTApp::getContext();
//output 'tenant'

// Based on URL, we are in at the primary sudomain www.example.com
echo MTApp::getContext();
//output 'global'

// Assumming we have defined a custom context, we are in at the sudomain admin.example.com
echo MTApp::getContext();
//output 'admin'

var_dump( MTApp::isPrimary() );
//returns true if we are at the primaryDomain, false if we are at a tenant's subdomain or in a custom context.
```

You can omit the `use MultiTenant\Core\MTApp;` line by calling the class with full namespace

```php
\MultiTenant\Core\MTApp::tenant();
```
### Behavior usage examples

#### TenantScopeBehavior
```php
class SomeTenantTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.TenantScope');
		...
	}
	...
}
```

#### MixedScopeBehavior
```php
class SomeMixedTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.MixedScope');
		...
	}
	...
}
```

#### GlobalScopeBehavior
```php
class SomeCommonTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.GlobalScope');
		...
	}
	...
}
```

#### SharedScopeBehavior
```php
class SomeSharedTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.SharedScope');
		...
	}
	...
}
```

#### NoScopeBehavior
```php
class JustARegularTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.NoScope');
		...
	}
	...
}
```

# Bugs

If you happen to stumble upon a bug, please feel free to create a pull request with a fix, and a description
of the bug and how it was resolved.

# Features

Pull requests are the best way to propose new features.
