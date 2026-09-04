# Multi-Tenancy

> **For planning agents**: Copy the non-obvious details into your plan. Agents
> commonly miss security requirements and validation rule scoping.

Docs: https://filamentphp.com/docs/5.x/panels/tenancy

## When to Use

Use Filament's tenancy for many-to-many (user belongs to multiple teams).

For simple one-to-many (user has ONE team), use global scopes instead - don't
set up Filament's tenancy system.

## Panel Configuration

```
Panel:
  Config:
    ->tenant(Team::class)
    ->tenant(Team::class, slugAttribute: 'slug')  // Use slug in URLs
    ->tenant(Team::class, ownershipRelationship: 'owner')  // Non-standard relationship
```

## User Model Requirements

```
User Model:
  Implements: Filament\Models\Contracts\HasTenants
  Methods:
    getTenants(Panel $panel): Collection
      Returns: $this->teams
    canAccessTenant(Model $tenant): bool
      Returns: $this->teams->contains($tenant)
      SECURITY: This is the ONLY check preventing URL manipulation attacks
```

## Validation Rules (Critical)

Laravel's `unique` and `exists` rules do NOT respect global scopes. Use scoped
versions:

```
Field: email
  Validation: scopedUnique:customers,email   // NOT unique:customers,email

Field: category_id
  Validation: scopedExists:categories,id     // NOT exists:categories,id
```

**Gotcha**: Soft-deleted records from other tenants can bypass `unique` rule.
`scopedUnique` prevents this.

## Resource Scoping

By default, all resources are scoped to the current tenant via
`$isScopedToTenant = true`.

For shared resources (e.g., system settings):

```
Resource: SettingsResource
  Property: $isScopedToTenant = false
```

## Security Gotchas

1. **canAccessTenant() is mandatory** - Only defense against URL manipulation

2. **withoutGlobalScopes() is dangerous** - Removes tenant scope too:

   ```
   Dangerous: ->withoutGlobalScopes()
   Safe: ->withoutGlobalScopes([SoftDeletingScope::class])
   Safe: ->withoutGlobalScope(filament()->getTenancyScopeName())
   ```

3. **Models without resources aren't auto-scoped** - Must use tenant middleware

4. **Early middleware queries leak data** - Queries before tenant identification
   are not scoped. Use `->tenantMiddleware()` instead of regular middleware.

5. **v4 breaking change** - v4 auto-scopes ALL queries in panel (v3 only scoped
   resource queries). Remove manual scoping code if upgrading.

## Optional: Tenant Registration/Profile

```
Panel:
  TenantRegistration: App\Filament\Pages\Tenancy\RegisterTeam
  TenantProfile: App\Filament\Pages\Tenancy\EditTeamProfile
```

Both pages extend base Filament classes and implement `form()` method.

## Common Mistakes

- Using `unique` instead of `scopedUnique` (data leaks across tenants)
- Not implementing `canAccessTenant()` (URL guessing attacks)
- Using `withoutGlobalScopes()` without specifying which scopes
- Expecting models without resources to be auto-scoped
- Queries in service providers running before tenant is set

## Don't Write

| Bad (vague)                         | Good (specific)                                         |
| ----------------------------------- | ------------------------------------------------------- |
| "Make it tenant-scoped"             | `Panel: Config: ->tenant(Team::class)`                  |
| "Validate uniqueness within tenant" | `Validation: scopedUnique:customers,email`              |
| "Prevent cross-tenant access"       | Implement `canAccessTenant()` method on User model      |
| "Some resources are shared"         | `Resource: SettingsResource: $isScopedToTenant = false` |
