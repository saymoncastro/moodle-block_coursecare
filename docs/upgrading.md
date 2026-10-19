# CourseCare — Upgrading

1. Back up your site as for any plugin upgrade.
2. Replace the plugin folder with the new release (or upload the new ZIP in Site administration >
   Plugins > Install plugins).
3. Run the upgrade in Site administration > Notifications or `admin/cli/upgrade.php`.

The upgrade keeps every diagnostic and every history point. Upgrade steps are idempotent: running the
upgrade again changes nothing.

| From | What the upgrade does |
| --- | --- |
| 0.1.x | Creates the diagnostic tables (0.2.0) and the history table (0.6.0) |
| 0.2.x – 0.5.x | Creates the history table and turns the current result of each course into its first history point, with its real date (0.6.0) |
| 0.6.x | No database change (0.7.0) |

**Upgrading Moodle itself** (for example from 4.5 to 5.3): install the CourseCare release that declares
support for the target version first (0.7.0 declares 4.5 and 5.3). On Moodle 5.1 and later the plugin
lives in `public/blocks/coursecare`: put it there in the new Moodle code before running the Moodle
upgrade. The diagnostics and the history are kept across the Moodle upgrade.

The version history is in [CHANGES.md](../CHANGES.md).
