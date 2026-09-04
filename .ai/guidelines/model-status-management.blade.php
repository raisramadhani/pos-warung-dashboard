# Model Status Management

When managing model statuses, auditing, and state tracking within this Laravel application, strictly adhere to the following architectural guidelines.

## 1. Package Architecture & Extension

* Always use the `spatie/laravel-model-status` package for tracking model status history.
* Never instantiate or reference the default `Spatie\ModelStatus\Status` model directly. Instead, always use the extended application model `App\Models\Status`.
* Ensure that `config/model-status.php` maps `'status_model'` to `App\Models\Status::class`.
* The config also contains `'model_primary_key_attribute'` (default `'model_id'`). If you publish a custom migration that renames this column, update the config key accordingly.

## 2. Automatic Actor and Timestamp Tracking

* Do not manually pass `user_id` inside the `$extraAttributes` array when calling `$model->setStatus()`.
* Actor tracking must be handled globally and automatically via the `booted` method inside `App\Models\Status`:

```php
protected static function booted()
{
    static::creating(function ($status) {
        if (auth()->check() && !$status->user_id) {
            $status->user_id = auth()->id();
        }
    });
}
```

* Timestamps (`created_at`) serve as the official status change date. Do not create a separate date column for status timing.

## 3. Performance & The Hybrid Strategy

**Strict Rule:** Never use the package's native `currentStatus()` or `otherCurrentStatus()` local scopes for querying or filtering models by status on high-volume tables (e.g., Orders, Invoices, Transactions). They utilize heavy polymorphic subqueries that cause severe performance degradation.

* Always enforce the **Hybrid Strategy**:
  1. Maintain a native string/enum column named `current_status` directly on the parent model's table.
  2. Index the `current_status` column in the database migration.
  3. Alias the trait's `setStatus` method and override it using the exact signature (2 parameters) to keep the local `current_status` column synchronized.

### Parent Model Implementation Example (Corrected Signature)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStatus\HasStatuses;

class Order extends Model
{
    // Alias the trait method to avoid naming conflicts and allow overriding
    use HasStatuses {
        setStatus as spatieSetStatus;
    }

    protected $fillable = ['current_status', 'total_amount'];

    /**
     * Override the setStatus method with correct signature (2 parameters).
     */
    public function setStatus(string|UnitEnum $name, ?string $reason = null): Model
    {
        // Call the aliased trait method with exactly 2 parameters
        $status = $this->spatieSetStatus($name, $reason);

        // Synchronize with the local indexed column
        $this->update(['current_status' => $name]);

        return $status;
    }
}
```

## 4. Querying and Reading Data

* **Filtering Data:** When writing Eloquent queries to filter records by their active status, always query the local column:

```php
// GOOD: Executes an indexed, instant query
$completedOrders = Order::where('current_status', 'completed')->get();

// BAD: Triggers slow polymorphic subqueries
$completedOrders = Order::currentStatus('completed')->get();
```

* **Excluding by Status:** Similarly, avoid `otherCurrentStatus()` for filtering. Use a negated local-column query instead:

```php
// GOOD
$nonPendingOrders = Order::where('current_status', '!=', 'pending')->get();

// BAD
$nonPendingOrders = Order::otherCurrentStatus('pending')->get();
```

* **Eager Loading Logs:** When displaying the audit trail or history log in views or API resources, always eager load the custom status relation along with its actor to avoid $N+1$ query problems:

```php
$order = Order::with('statuses.actor')->find($id);

```

## 5. Type Safety with Enums

* Always use backed PHP Enums (typically `string`) to define allowed states instead of hardcoded strings to prevent invalid statuses from entering the database:

```php
namespace App\Enums;

enum OrderStatus: string {
    case DRAFT = 'draft';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case REJECTED = 'rejected';
}

```

* `setStatus()` accepts both strings and enums. Backed enums are stored using their value; unit enums are stored using their case name.
* Use `$model->statusEnum()` to retrieve the latest status as its enum case. Returns `null` when there is no status yet, or when the stored name does not map to a case in the configured `statusEnumClass()`.

* **Restrict Statuses at the Model Level:** Override the `statusEnumClass()` method on models using `HasStatuses` to enforce allowed statuses directly via the package instead of relying solely on controller-level validation:

```php
use App\Enums\OrderStatus;

class Order extends Model
{
    use HasStatuses;

    public function statusEnumClass(): ?string
    {
        return OrderStatus::class;
    }
}
```

When `statusEnumClass()` returns an enum class, the package will reject any status name not defined in that enum. This is the preferred method — it fails fast at the model layer regardless of where `setStatus()` is called.

Note: `forceSetStatus()` bypasses both `isValidStatus()` validation and the `statusEnumClass()` restriction. Use it only when you explicitly need to bypass all checks.

* Enforce type validation in controllers or service classes before firing the `setStatus` method.

## 6. Custom Status Validation (`isValidStatus` & `forceSetStatus`)

* Override `isValidStatus()` on your model to add custom business rules before a status is set:

```php
public function isValidStatus(string $name, ?string $reason = null): bool
{
    if ($name === 'shipped' && $this->current_status !== 'paid') {
        return false;
    }

    return true;
}
```

A return value of `false` throws a `Spatie\ModelStatus\Exceptions\InvalidStatus` exception.

* Use `forceSetStatus()` to bypass both `isValidStatus()` and `statusEnumClass()` restrictions entirely:

```php
$model->forceSetStatus('override-status');
```

This is useful for internal/system operations where normal validation should not apply.

## 7. Status History Operations

* **Check current status match:**

```php
$model->hasStatus('pending'); // true/false
```

* **Check if a status has ever been assigned:**

```php
$model->hasEverHadStatus('shipped'); // true if shipped was ever set
$model->hasNeverHadStatus('rejected'); // true if rejected was never set
```

* **Retrieve all distinct status names that have been applied:**

```php
$names = $model->getStatusNames(); // Collection of strings
```

* **Retrieve the latest status among specific names (accepts array or variadic args):**

```php
$latest = $model->latestStatus(['pending', 'initiated']);

// or equivalently
$latest = $model->latestStatus('pending', 'initiated');
```

* **Delete a status (or multiple statuses) from the history:**

```php
$model->deleteStatus('draft');           // single
$model->deleteStatus(['draft', 'initiated']); // multiple
```

## 8. Events

* A `Spatie\ModelStatus\Events\StatusUpdated` event is dispatched whenever a status is updated via `setStatus()` or `forceSetStatus()`.
* The event exposes three public properties:

```php
use Spatie\ModelStatus\Events\StatusUpdated;
use Spatie\ModelStatus\Status;
use Illuminate\Database\Eloquent\Model;

class StatusUpdated
{
    public ?Status $oldStatus;
    public Status $newStatus;
    public Model $model;
}
```

* Register a listener in your service provider to react to status changes:

```php
use App\Listeners\LogStatusChange;
use Spatie\ModelStatus\Events\StatusUpdated;

public function boot(): void
{
    Event::listen(
        StatusUpdated::class,
        LogStatusChange::class,
    );
}
```
