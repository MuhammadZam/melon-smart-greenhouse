# UI component inventory — flat refinement

## Direction and scope recorded before implementation

White canvas #FFFFFF, secondary surface #F8F9FA, ink #171717, muted text #666666, border #E5E7EB, primary #176B50, error #B42318. Retain the local Plus Jakarta Sans font and all existing workflows. Cards2px, buttons/inputs/dialogs4px, tables0px. Color indicates actions or real status, never sensor identity.

Planned presentation-only changes: all web Blade templates (including public home, auth, dashboard, monitoring/plants CRUD, alarms/thresholds, fuzzy/history/config, actuators/history, Sheets and reports), shared components/layouts, app.css and app.js. Preserve the print PDF template and all backend/config/schema/contracts. Capture before/after in docs/ui_flat using a disposable SQLite copy.

Implementation inventory and verification results follow after browser checks.
