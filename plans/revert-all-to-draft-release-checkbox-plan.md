# Implementation Plan: Add "Include Release Features" Checkbox to Revert All to Draft

## Overview

Add a checkbox option in the "Revert All to Draft" modal to allow users to also revert features that are currently at "Release" status, in addition to the existing "Develop" status features.

## Current Implementation Analysis

### Frontend (admin.js)

- **State Variables**: `includeBroken` (boolean) - already exists for including broken features
- **Functions**:
  - `previewBatchRevert()` - Calls `vaptsecure/v1/features/preview-revert?include_broken=`
  - `executeBatchRevert()` - Calls `vaptsecure/v1/features/batch-revert` with `include_broken` in POST data
- **Modal**: `BatchRevertModal` component displays:
  - Toggle for including broken features
  - Summary of affected features (Develop count + optional broken count)
  - Feature list table

### Backend (class-vaptsecure-rest.php)

- **Endpoints**:
  - `GET /vaptsecure/v1/features/preview-revert` - Calls `VAPTSECURE_Workflow::preview_revert_to_draft($include_broken)`
  - `POST /vaptsecure/v1/features/batch-revert` - Calls `VAPTSECURE_Workflow::batch_revert_to_draft($note, $include_broken)`

### Backend (class-vaptsecure-workflow.php)

- **preview_revert_to_draft($include_broken)**:
  - Queries features with `status = 'Develop'`
  - Optionally queries "broken" features (Draft status with history records)
  - Returns preview data with counts

- **batch_revert_to_draft($note, $include_broken)**:
  - Gets all features in Develop status
  - Optionally includes broken features
  - Deletes history records, clears generated_schema, implementation_data
  - Sets status to 'Draft'

## Proposed Changes

### 1. Frontend - admin.js

#### Add New State Variable

```javascript
// Around line 5400 (near other state declarations)
const [includeRelease, setIncludeRelease] = useState(false);
```

#### Update previewBatchRevert Function

```javascript
const previewBatchRevert = () => {
  setBatchRevertModal({ previewData: null, isLoading: true, isExecuting: false });
  apiFetch({
    path: 'vaptsecure/v1/features/preview-revert?include_broken=' + (includeBroken ? '1' : '0') + '&include_release=' + (includeRelease ? '1' : '0'),
    method: 'GET',
  }).then(res => {
    setBatchRevertModal({ previewData: res, isLoading: false, isExecuting: false });
  }).catch(err => {
    setSaveStatus({ message: err.message || __('Failed to preview revert', 'vaptsecure'), type: 'error' });
    setBatchRevertModal(null);
  });
};
```

#### Update executeBatchRevert Function

```javascript
const executeBatchRevert = () => {
  if (!batchRevertModal?.previewData) return;
  setBatchRevertModal(prev => ({ ...prev, isExecuting: true }));

  apiFetch({
    path: 'vaptsecure/v1/features/batch-revert',
    method: 'POST',
    data: { 
      note: 'Batch revert to Draft via Workbench', 
      include_broken: includeBroken,
      include_release: includeRelease
    }
  }).then(res => {
    setBatchRevertModal(null);
    setSaveStatus({
      message: sprintf(__('Successfully reverted %d features to Draft', 'vaptsecure'), res.reverted_count),
      type: 'success'
    });
    fetchData(selectedFile);
    setTimeout(() => setSaveStatus(null), 5000);
  }).catch(err => {
    setSaveStatus({ message: err.message || __('Batch revert failed', 'vaptsecure'), type: 'error' });
    setBatchRevertModal(prev => ({ ...prev, isExecuting: false }));
  });
};
```

#### Update BatchRevertModal Component

Add new props: `includeRelease`, `onToggleIncludeRelease`

Add UI for Release checkbox (after the broken features toggle):

```javascript
// After the broken features toggle section (around line 1758)
el('div', { 
  key: 'toggle-release',
  style: { background: '#f0f9f0', padding: '12px', borderRadius: '4px', marginBottom: '15px', border: '1px solid #00a32a' }
}, [
  el(ToggleControl, {
    label: sprintf(__('Include %d Release feature(s)', 'vaptsecure'), releaseCount),
    checked: includeRelease,
    onChange: (val) => { onToggleIncludeBroken(val); onRefresh(); },
    disabled: isExecuting
  }),
  el('p', { 
    style: { margin: '5px 0 0 0', fontSize: '11px', color: '#646970', fontStyle: 'italic' }
  }, __('Release features are currently active in production. Reverting them will disable enforcement.', 'vaptsecure'))
]),
```

Update summary section to show Release count:

```javascript
el('div', null, [
  el('strong', null, developCount),
  __(' Develop features', 'vaptsecure'),
  includeBroken && includedBrokenCount > 0 && el('span', { style: { color: '#856404' } }, sprintf(__(' + %d broken', 'vaptsecure'), includedBrokenCount)),
  includeRelease && releaseCount > 0 && el('span', { style: { color: '#d63638' } }, sprintf(__(' + %d release', 'vaptsecure'), releaseCount))
]),
```

#### Update Modal Props Pass

```javascript
// Around line 5640
batchRevertModal && el(BatchRevertModal, {
  isOpen: !!batchRevertModal,
  previewData: batchRevertModal.previewData,
  isLoading: batchRevertModal.isLoading,
  isExecuting: batchRevertModal.isExecuting,
  includeBroken: includeBroken,
  onToggleIncludeBroken: setIncludeBroken,
  includeRelease: includeRelease,  // NEW
  onToggleIncludeRelease: setIncludeRelease,  // NEW
  onRefresh: previewBatchRevert,
  onConfirm: executeBatchRevert,
  onCancel: () => setBatchRevertModal(null)
}),
```

### 2. Backend - class-vaptsecure-rest.php

#### Update preview_revert_to_draft

```php
public function preview_revert_to_draft($request)
{
  $include_broken = (bool) $request->get_param('include_broken');
  $include_release = (bool) $request->get_param('include_release');  // NEW
  $result = VAPTSECURE_Workflow::preview_revert_to_draft($include_broken, $include_release);
  return new WP_REST_Response($result, 200);
}
```

#### Update batch_revert_to_draft

```php
public function batch_revert_to_draft($request)
{
  $note = $request->get_param('note') ?: 'Batch revert to Draft via Workbench';
  $include_broken = (bool) $request->get_param('include_broken');
  $include_release = (bool) $request->get_param('include_release');  // NEW

  $result = VAPTSECURE_Workflow::batch_revert_to_draft($note, $include_broken, $include_release);

  if (!$result['success']) {
    return new WP_REST_Response($result, 207);
  }

  return new WP_REST_Response($result, 200);
}
```

### 3. Backend - class-vaptsecure-workflow.php

#### Update preview_revert_to_draft Method

```php
public static function preview_revert_to_draft($include_broken = false, $include_release = false)
{
  global $wpdb;

  $table_status = $wpdb->prefix . 'vaptsecure_feature_status';
  $table_history = $wpdb->prefix . 'vaptsecure_feature_history';
  $table_meta = $wpdb->prefix . 'vaptsecure_feature_meta';

  // 1. Get all features in 'Develop' status
  $develop_features = $wpdb->get_results($wpdb->prepare(
    "SELECT feature_key, implemented_at, assigned_to, 'develop' as source FROM $table_status WHERE status = %s",
    'Develop'
  ), ARRAY_A);

  // 2. Get BROKEN features (Draft status + has history records)
  $broken_features = $wpdb->get_results(
    "SELECT DISTINCT s.feature_key, s.implemented_at, s.assigned_to, 'broken' as source 
    FROM $table_status s
    INNER JOIN $table_history h ON s.feature_key = h.feature_key
    WHERE s.status = 'Draft'",
    ARRAY_A
  );

  // 3. Get RELEASE features (NEW)
  $release_features = array();
  if ($include_release) {
    $release_features = $wpdb->get_results($wpdb->prepare(
      "SELECT feature_key, implemented_at, assigned_to, 'release' as source FROM $table_status WHERE status = %s",
      'Release'
    ), ARRAY_A);
  }

  // 4. Merge based on flags
  $all_features = $develop_features ?: array();
  if ($include_broken && $broken_features) {
    $all_features = array_merge($all_features, $broken_features);
  }
  if ($include_release && $release_features) {
    $all_features = array_merge($all_features, $release_features);
  }

  // ... rest of the method remains the same ...
  
  // Add release_count to return array
  return array(
    'success' => true,
    'count' => count($preview),
    'broken_count' => count($broken_features ?: array()),
    'develop_count' => count($develop_features ?: array()),
    'release_count' => count($release_features ?: array()),  // NEW
    'included_broken_count' => $broken_count,
    'included_release_count' => $include_release ? count($release_features ?: array()) : 0,  // NEW
    'features' => $preview,
    // ... rest of return data ...
  );
}
```

#### Update batch_revert_to_draft Method

```php
public static function batch_revert_to_draft($note = 'Batch revert to Draft', $include_broken = false, $include_release = false)
{
  global $wpdb;

  $table_status = $wpdb->prefix . 'vaptsecure_feature_status';
  $table_history = $wpdb->prefix . 'vaptsecure_feature_history';

  // 1. Get all features in 'Develop' status
  $develop_features = $wpdb->get_col($wpdb->prepare(
    "SELECT feature_key FROM $table_status WHERE status = %s",
    'Develop'
  ));

  // 2. Get BROKEN features (Draft status + has history records)
  $broken_features = $wpdb->get_col(
    "SELECT DISTINCT s.feature_key
    FROM $table_status s
    INNER JOIN $table_history h ON s.feature_key = h.feature_key
    WHERE s.status = 'Draft'"
  );

  // 3. Get RELEASE features (NEW)
  $release_features = array();
  if ($include_release) {
    $release_features = $wpdb->get_col($wpdb->prepare(
      "SELECT feature_key FROM $table_status WHERE status = %s",
      'Release'
    ));
  }

  // 4. Merge based on flags
  $all_features = $develop_features ?: array();
  if ($include_broken && $broken_features) {
    $all_features = array_unique(array_merge($all_features, $broken_features));
  }
  if ($include_release && $release_features) {
    $all_features = array_unique(array_merge($all_features, $release_features));
  }

  // ... rest of the method remains the same ...
  
  // Add release count to return
  return array(
    'success' => true,
    'reverted_count' => $reverted_count,
    'broken_count' => count($broken_features ?: array()),
    'develop_count' => count($develop_features ?: array()),
    'release_count' => count($release_features ?: array()),  // NEW
    'message' => sprintf('Reverted %d features to Draft.', $reverted_count)
  );
}
```

## Files to Modify

1. **VAPT-Secure/assets/js/admin.js**
   - Add `includeRelease` state variable
   - Update `previewBatchRevert()` function
   - Update `executeBatchRevert()` function
   - Update `BatchRevertModal` component props and UI
   - Update modal invocation to pass new props

2. **VAPT-Secure/includes/class-vaptsecure-rest.php**
   - Update `preview_revert_to_draft()` method
   - Update `batch_revert_to_draft()` method

3. **VAPT-Secure/includes/class-vaptsecure-workflow.php**
   - Update `preview_revert_to_draft()` method signature and logic
   - Update `batch_revert_to_draft()` method signature and logic

## UI/UX Considerations

1. **Visual Distinction**: The Release features toggle should have a distinct color (green `#00a32a`) to differentiate from the broken features toggle (blue `#2271b1`).

2. **Warning Message**: Include a warning that Release features are currently active in production and reverting them will disable enforcement.

3. **Summary Display**: The summary section should clearly show:
   - Develop features count
   - Broken features count (if included)
   - Release features count (if included)
   - Total history records to be deleted
   - Total schemas to be cleared

4. **Safety**: Both toggles should be disabled while `isExecuting` is true to prevent changes during the revert operation.

## Testing Checklist

- [ ] Verify checkbox appears in the modal
- [ ] Verify checkbox toggles correctly
- [ ] Verify preview API call includes `include_release` parameter
- [ ] Verify execute API call includes `include_release` parameter
- [ ] Verify Release features are counted in preview when checkbox is checked
- [ ] Verify Release features are reverted when checkbox is checked
- [ ] Verify Release features are NOT reverted when checkbox is unchecked
- [ ] Verify modal summary displays Release count correctly
- [ ] Verify warning message is appropriate for Release features
- [ ] Verify both toggles work independently
- [ ] Verify the operation completes successfully with mixed feature statuses

## Security Considerations

- The `include_release` parameter is properly sanitized as a boolean
- Permission checks remain in place via `check_permission` callback
- No additional security risks introduced - existing permission model applies
