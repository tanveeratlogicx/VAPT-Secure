# TASK: Restrict the display of `x-vapt-feature:` only for the SuperAdmins - under the Technical Trace
## Date/Time: 20260325_@0108

## Description
The user requested to restrict the display of the `x-vapt-feature:` header trace only for SuperAdmins under the "Technical Trace" section of the VAPT Secure Workbench.

## Changes Required:
1. File: `assets/js/modules/generated-interface.js`
   Location: The `Technical Trace` section rendering logic (around line 1216).
   Action: Add logic to filter out any occurrences of `x-vapt-feature:` from `result.raw` before rendering it within the `<pre>` tag if `window.vaptSecureSettings.isSuper` is false.

## Implementation Steps:
- YYYYMMDD_@HHMM - 20260325_@0108 
- Identify the UI rendering block for "Technical Trace", specifically where `result.raw` is wrapped in the `<pre>` tag.
- Inject a regex `.replace()` filter for `finalRaw` right before presentation to strip out any `x-vapt-feature:` strings.
- Only run the filter if `!(window.vaptSecureSettings && window.vaptSecureSettings.isSuper)`.

## Latest Comments/Suggestions
- The check guarantees the restriction without needing to augment all underlying probe logic, keeping the code clean.
