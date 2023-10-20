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
- Scheduled imports of all sinks with the ability for sinks to
  customize their cron entry.
- Sinks can now be searched/filtered based on ID and import/fetch
  status.
- Added mass-operation tool for chunk selection.
- Added progress bar of currently running operation with ability to
  cancel.
- Centralized error/exception handling with feedback/presentation on
  front end.

### Removed
- Example dashboard (authenticated) and welcome (anonymous) scaffolding pages.
