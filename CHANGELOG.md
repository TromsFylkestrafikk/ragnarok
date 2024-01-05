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
- Chunks can now be searched/filtered based on ID, import/fetch status
  and import/fetch size.
- Added mass-operation tool for chunk selection.
- Added progress bar of currently running operation with ability to
  cancel.
- Centralized error/exception handling with feedback/presentation on
  front end.
- Indication of mismatched between downloaded chunk and imported versions.
- Queued chunks in a batch operation will not be able to operate on
  and have a gray status until completed/canceled.
- Max age of fetched chunks users can delete.
- Raw fetched chunks from sinks can be downloaded directly
- Added background job for linting stuck chunks or chunks with invalid
  state.
- Fetched chunks are single files stored on disk, maintained in DB.

### Removed
- Example dashboard (authenticated) and welcome (anonymous) scaffolding pages.
