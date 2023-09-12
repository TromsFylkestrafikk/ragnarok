## [Unreleased]

This initial release is based on Laravel jetstream using the inertia
stack, where we shoe-horn in Vuetify as our main UI.

### Added
- Added vuetify UI component framework.
- Added rudimentary plugin system for sinks.
- DB and model of sink imports.
- Broadcast system.
- UI for assigning roles and permissions to users.
- Fetch UI and API (stage 1 import) from sinks.
- UI and API for CRUD management of users.
- Batched queue jobs of fetch, import and deletion of such from sinks.

### Removed
- Example dashboard (authenticated) and welcome (anonymous) scaffolding pages.
