# Model Docblocks

- Model PHPDoc blocks contain intentional human-readable inline descriptions on
  `@property` lines, e.g. `@property MerchantType $type Tipe merchant: warehouse
  (gudang) atau merchant (outlet)`. These are deliberate and must be preserved.
- `composer generate` (ide-helper) rewrites model docblocks and strips these
  trailing inline descriptions. After running it, restore any stripped
  descriptions manually.
- When editing model docblocks, do NOT remove or shorten these inline
  descriptions — only change lines that correspond to an actual code/schema
  change.
