# MultiTenant

MultiTenant CakePHP Plugin - Use this plugin to easily build SaaS enabled web applications.

# Version notice

This plugin only supports CakePHP 3.x

This project currently in development and is considered experimental at this stage.

## Introduction

The MultiTenant plugin is best implement when you begin developing your application, but with some work
you should be able adapt an existing application to use the Plugin.

The plugin currently implements the following multi-tenancy architecures (Strategy).

### Domain Strategy

Shared Database, Shared Schema
Single Application Instance
Subdomain per Tenant


### Tenants

The plugin introduces the concept of Tenants, a tenant represent each customer account.

A tenant can have it's own users, groups, and records.  Data is scoped to the tenant, tenants are represented
in the database as Accounts (or any Model/Table you designate by configuration).

### Contexts

The MultiTenant plugin introduces the concept of Contexts, context is an intregal part of
how the multitenant plugin works.  There are two predefined contexts, 'tenant' and 'global'.

#### 'global' Context

By default, 'global' maps to www.mydomain.com and is where you will implement non-tenant specific parts of your
application. ie signup/register code.

#### 'tenant' Context

'tenant' context represents this tenant.  When the user is accessing the application at the subdomain, this is 
considered the 'tenant' context.

#### 'custom' Contexts

The plugin supports the definition of additional contexts, these custom contexts are mapped
to subdomains.  Your application you can now call `MTApp::getContext()` to implement context-aware
code. 

For example, create a context named 'admin' and map it to the subdomain 'admin.mydomain.com'.

Note: Contexts are not a replacement for role based authorization but in some cases may be complimentary.

### Scopes

Each of your application's Models can implement one of five Scope Behaviors that will dictate 
what data is accessible based on the context.  These scopes are implemented as CakePHP Behaviors.

#### GlobalScope

The data provided in a Global Scoped Model can be queried by any tenant but insert/update/delete
are not allowed from the 'tenant' Context.

#### TenantScope

The data provided in a Tenant Scoped Model can only queried by the owner (tenant).  Insert operation are 
scoped to the current tenant, Update and Delete operations are enfore ownership, so that Tenant1 cannot 
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


# Bugs

If you happen to stumble upon a bug, please feel free to create a pull request with a fix, and a description
of the bug and how it was resolved.

