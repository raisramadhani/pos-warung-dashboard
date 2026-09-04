# Workflow

- Wants the agent to plan and understand the existing codebase first before making changes ("plan dulu pahami dulu") — exploration/planning phase before implementation. Confidence: 0.8
- Prefers minimal, direct changes over adding new mechanisms or abstractions — e.g., "cukup hidden aja" (just hide it) rather than building configurable/brand-conditional feature flags; "tanpa merombak fitur" (don't overhaul features) — remove unused form sections and delete unused seeders rather than leaving empty scaffolding. Confidence: 0.8
- When preparing seed/demo data for a project copy of a new brand, wants the data fully localized to that brand and its business/location (merchant & outlet names, addresses/coordinates, categories, menu items, raw materials, assets, transaction/cashflow references) with no leftover old-brand strings in active code paths. Confidence: 0.8
- Scopes brand renames narrowly: update only user-facing config (settings default + .env/.env.example) and explicitly leave docs/README/domain/package metadata and hidden pages untouched. Confidence: 0.7
- Asks the agent to commit finished work ("commitkan") and delegates commit mechanics to it — staging, message wording, and execution — rather than committing or dictating steps himself. Confidence: 0.6
