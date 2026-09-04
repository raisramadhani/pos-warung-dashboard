## Guidelines for Laravel and Filament Feature Testing

Follow these strict guidelines when generating feature tests for Laravel and Filament applications.

### 1. Test Coverage Scope

* **Resource Classes (`.../Resource.php`)**: Test for general availability and basic rendering. Verify that standard pages (Create, Edit, List, View) can be accessed and that Infolists can be displayed. Focus on checking if the data/components can be rendered.
* **Resource Pages (`.../Pages/...`)**: Test comprehensively.
* **Tables/Lists**: Verify that specific columns render, and explicitly test `searchable`, `sortable`, and `grouping` functionalities.
* **Forms**: Verify that specific form fields exist, can be filled, and form submissions work.
* **Business Logic**: Thoroughly test creation, editing, and ensure all validation rules are applied correctly.
* **Actions**: Briefly verify that specific expected actions exist on the page.


* **Relation Managers**: Test comprehensively, treating them similarly to List pages. Verify that `searchable`, `sortable`, and `grouping` functions work based on the manager's specific content.
* **Standalone Actions (`.../Actions/...`)**: Test comprehensively. Focus entirely on ensuring the underlying business logic executes correctly.
* **Exclusions**: Do not write feature tests for database schemas or tables.

### 2. Test Scenario Requirements

Every component tested must explicitly include the following scenarios:

* **Happy Path**: The ideal scenario where inputs are correct and operations succeed.
* **Sad Path**: Scenarios involving invalid inputs, validation failures, or unauthorized access.
* **Edge Cases**: Boundary conditions, extreme values, or unusual but possible data states.

### 3. File and Directory Structure Rules

Test case locations must strictly mirror the original application file structure, placed inside the `tests/Feature/` directory.

**Example Mapping:**

*Source Files:*
`app/Filament/Resource/Users/UserResource.php`
`app/Filament/Admin/Resources/Users/Pages/CreateUser.php`
`app/Filament/Admin/Resources/Users/Pages/EditUser.php`
`app/Filament/Admin/Resources/Users/Pages/ListUsers.php`
`app/Filament/Admin/Resources/Users/Pages/ViewUser.php`
`app/Filament/Admin/Resources/Users/Actions/ChangePasswordAction.php`

*Target Test Files:*
`tests/Feature/Filament/Resource/Users/UserResource.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/CreateUser.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/EditUser.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/ListUsers.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/ViewUser.php`
`tests/Feature/Filament/Admin/Resources/Users/Actions/ChangePasswordAction.php`


### 4. Running Tests

Test suites in this project can take a very long time because the database is migrated and seeded on every run. Additionally, direct terminal output can be silenced or swallowed by subshell buffering. **Always clear the previous log file, redirect execution output to it, and read the file afterwards.** Follow these strict rules:

* **DO NOT SPAM COMMANDS WHILE TESTS ARE RUNNING. WAIT UNTIL EXECUTION COMPLETES BEFORE INTERACTING WITH THE TERMINAL.**
* **Always remove the old log file and redirect both stdout and stderr:** Ensure a clean slate by deleting previous test logs before execution, then pipe all output:
```bash
rm -f storage/logs/pest-test.log && ./vendor/bin/pest --parallel --compact > storage/logs/pest-test.log 2>&1
```
*(For targeted tests: `rm -f storage/logs/pest-test.log && ./vendor/bin/pest --parallel --compact <test-path> > storage/logs/pest-test.log 2>&1`)*
* **Inspect the log file to evaluate results:** Once the command exits and returns a prompt, read the newly generated log to check status, assertions, and stack traces:
```bash
cat storage/logs/pest-test.log
# or inspect recent output
tail -n 50 storage/logs/pest-test.log

```

* **Never interrupt an ongoing test run:** Once triggered, do not send secondary commands, key strokes (like `^C`), or poll the terminal stream aggressively. Allow the process to return its exit code naturally.
* **Wait for true completion:** A run is only complete when the subshell returns control with an exit code. Immediately verify test results by reading the redirected log file rather than checking standard terminal scrollback.
* **Use focused runs during development:** Run single files or directories using the clean-and-pipe pattern for active changes, and only execute the full suite before finalizing tasks.
