import codecs

file_path = r't:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure\assets\js\admin.js'
with codecs.open(file_path, 'r', 'utf-8') as f:
    text = f.read()

old_payload = """auto_renew: formState.auto_renew ? 1 : 0,"""
new_payload = """auto_renew: (formState.license_type === 'developer' || formState.auto_renew) ? 1 : 0,"""
if old_payload in text:
    text = text.replace(old_payload, new_payload)
else:
    print('Failed to patch payload!')

old_row = """el('div', { style: { display: 'flex', gap: '15px', marginBottom: '15px' } }, [
            el('div', { style: { flex: 1.5 } }, el(TextControl, {
              label: __('License ID', 'vaptsecure'),
              value: formState.license_id,
              disabled: true,
              readOnly: true,
              style: { background: '#f8fafc', color: '#64748b', marginBottom: 0 },
              help: __('Unique License Identifier. Read-only.', 'vaptsecure')
            })),
            el('div', { style: { flex: 1 } }, el(SelectControl, {
              label: __('License Scope', 'vaptsecure'),
              value: formState.license_scope,
              options: [
                { label: __('Single Domain', 'vaptsecure'), value: 'single' },
                { label: __('Multi-Site', 'vaptsecure'), value: 'multisite' }
              ],
              onChange: (val) => setFormState({ ...formState, license_scope: val }),
              style: { marginBottom: 0 }
            }))
          ]),

          el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' } }, ["""

new_row = """el('div', { style: { display: 'flex', gap: '15px', marginBottom: '15px', flexWrap: 'wrap' } }, [
            el('div', { style: { flex: 1 } }, el(SelectControl, {
              label: __('License Scope', 'vaptsecure'),
              value: formState.license_scope,
              options: [
                { label: __('Single Domain', 'vaptsecure'), value: 'single' },
                { label: __('Multi-Site', 'vaptsecure'), value: 'multisite' }
              ],
              disabled: isSaving,
              onChange: (val) => setFormState({ ...formState, license_scope: val }),
              style: { marginBottom: 0 }
            })),
            el('div', { style: { flex: 1.5 } }, el(TextControl, {
              label: __('License ID', 'vaptsecure'),
              value: formState.license_id,
              disabled: true,
              readOnly: true,
              style: { background: '#f8fafc', color: '#64748b', marginBottom: 0 },
              help: __('Unique License Identifier.', 'vaptsecure')
            })),
            formState.license_scope === 'multisite' && el('div', { style: { flex: 1 } }, el(TextControl, {
              label: __('Installation Limit', 'vaptsecure'),
              type: 'number',
              min: 1,
              disabled: isSaving,
              value: formState.installation_limit,
              onChange: (val) => setFormState({ ...formState, installation_limit: parseInt(val) || 1 }),
              style: { marginBottom: 0 }
            }))
          ]),

          el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' } }, ["""

if old_row in text:
    text = text.replace(old_row, new_row)
else:
    print('Failed to patch layout row!')


old_toggle = """el(ToggleControl, {
            label: __('Auto Renew', 'vaptsecure'),
            checked: formState.auto_renew,
            disabled: isSaving || formState.license_type === 'developer',
            onChange: (val) => setFormState({ ...formState, auto_renew: val }),
            help: __('Automatically extend expiry if active.', 'vaptsecure')
          }),

          formState.license_scope === 'multisite' && el('div', { style: { display: 'flex', gap: '20px', marginBottom: '20px', background: '#f8fafc', padding: '15px', borderRadius: '6px' } }, [
            el('div', { style: { flex: 1 } },
              el(TextControl, {
                label: __('Installation Limit', 'vaptsecure'),
                type: 'number',
                min: 1,
                value: formState.installation_limit,
                onChange: (val) => setFormState({ ...formState, installation_limit: parseInt(val) || 1 })
              })
            )
          ]),"""

new_toggle = """el(ToggleControl, {
            label: __('Auto Renew', 'vaptsecure'),
            checked: formState.license_type === 'developer' ? true : formState.auto_renew,
            disabled: isSaving || formState.license_type === 'developer',
            onChange: (val) => setFormState({ ...formState, auto_renew: val }),
            help: __('Automatically extend expiry if active.', 'vaptsecure')
          }),"""

if old_toggle in text:
    text = text.replace(old_toggle, new_toggle)
else:
    print('Failed to patch toggle!')

with codecs.open(file_path, 'w', 'utf-8') as f:
    f.write(text)

# Bump version
vp_path = r't:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure\vaptsecure.php'
with codecs.open(vp_path, 'r', 'utf-8') as vp:
    vp_text = vp.read()
vp_text = vp_text.replace('Version:           2.2.1', 'Version:           2.2.3')
vp_text = vp_text.replace('Version:           2.2.2', 'Version:           2.2.3')
with codecs.open(vp_path, 'w', 'utf-8') as vp:
    vp.write(vp_text)

print('All patches applied!')
