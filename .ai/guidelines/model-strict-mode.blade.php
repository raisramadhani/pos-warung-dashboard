# Model Strict Mode & Query Efficiency

`Model::shouldBeStrict()` is enabled in local environments via `AppServiceProvider`. All code must work under these constraints.

---

## Always Start Queries with `Model::query()`

Always use `Model::query()` as the entry point for Eloquent queries, not static facade methods. This ensures proper IDE autocompletion, static analysis compatibility, and consistency across the codebase:

```php
// ❌ DON'T — static method call, inconsistent
$users = User::where('active', true)->get();
$merchants = Merchant::where('type', 'merchant')->pluck('name', 'id');

// ✅ DO — explicit query builder entry point
$users = User::query()->where('active', true)->get();
$merchants = Merchant::query()->where('type', 'merchant')->pluck('name', 'id');
```

`Model::query()` is also required for relationships:

```php
// ✅ DO — use query() on relationships too
$record->items()->sum('amount');
$record->items()->where('component_name', 'Bonus')->count();
```

---

## Always Eager Load Relationships

Lazy loading throws `LazyLoadingViolationException` in strict mode. Every relationship access **must** be eager-loaded first with `->load()` or `->with()`:

```php
// ❌ DON'T — lazy load throws exception
$record->items->sum('amount');
$record->user->name;

// ✅ DO — eager load first
$record->load(['items', 'user']);
$record->items->sum('amount');
$record->user->name;
```

### Only load what you access

Don't eager-load relationships that are never used. Every `load()`/`with()` call must be justified by a concrete access in the code that follows.

---

## Prefer Aggregate Queries Over Collection Loading

When you only need `SUM`, `COUNT`, `AVG`, `MIN`, `MAX` from a relationship — use the query builder, not the collection:

```php
// ❌ DON'T — loads all items into memory just to sum
$record->load('items');
$total = $record->items->sum('amount');

// ✅ DO — runs SELECT SUM(amount) in the database
$total = $record->items()->sum('amount');
```

The query builder approach:
- Runs a single aggregate SQL query (O(1) memory)
- Avoids hydrating hundreds of Eloquent models
- Still works under strict mode (no lazy-loading violation)

### When to load the collection

Only load the full relationship when you actually need the models — e.g., iterating items, accessing nested relations, or calling model methods.

---

## Fillable Attributes

All attributes passed to `create()` or `update()` must be listed in the model's `$fillable` array. Strict mode throws when you write to non-fillable attributes.

```php
// ❌ DON'T — 'publish_at' not in $fillable
Post::create(['title' => '...', 'publish_at' => now()]);

// ✅ DO — add to $fillable, or use forceFill for one-off
```

---

## Summary Checklist

Before submitting code, verify:
- [ ] Queries start with `Model::query()` or `$relation->query()`
- [ ] Every relationship access has a corresponding `load()`/`with()`
- [ ] Aggregate operations use query builder (`items()->sum()`, not `items->sum()`)
- [ ] Eager-loaded relationships are actually used later in the code
- [ ] Mass-assigned attributes are in `$fillable`


