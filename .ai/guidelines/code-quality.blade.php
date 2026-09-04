## Code Quality

- All changes and feature additions must pass `composer lint` (Pint + PHPStan) without errors before committing.
- Any changes to Models (new model, adding relationships, modifying fillable/casts/attributes) must run `composer generate && composer lint` to ensure IDE helper files and static analysis stay in sync.
- Run `composer lint` as the final verification step after completing any task.
