// Client Dashboard Entry Point
// Phase 6 Implementation - IDE Workbench Redesign
(function () {
  console.log('VAPT Secure: client.js loaded');
  if (typeof wp === 'undefined') return;

  const { render, useState, useEffect, useMemo, Fragment, createElement: el } = wp.element || {};
  const { Button, ToggleControl, Spinner, Notice, Card, CardBody, CardHeader, CardFooter, Icon, Tooltip, Modal } = wp.components || {};
  const settings = window.vaptSecureSettings || {};
  const isSuper = settings.isSuper || false;

  // 🛡️ GLOBAL REST HOTPATCH (v3.8.16)
  if (wp.apiFetch && !wp.apiFetch.__vaptsecure_patched) {
    let localBroken = localStorage.getItem('vaptsecure_rest_broken') === '1';
    const originalApiFetch = wp.apiFetch;
    const patchedApiFetch = (args) => {
      const getFallbackUrl = (pathOrUrl) => {
        if (!pathOrUrl) return null;
        const path = typeof pathOrUrl === 'string' && pathOrUrl.includes('/wp-json/')
          ? pathOrUrl.split('/wp-json/')[1]
          : pathOrUrl;
        const cleanHome = settings.homeUrl.replace(/\/$/, '');
        const cleanPath = path.replace(/^\//, '').split('?')[0];
        const queryParams = path.includes('?') ? '&' + path.split('?')[1] : '';
        return cleanHome + '/?rest_route=/' + cleanPath + queryParams;
      };

      if (localBroken && (args.path || args.url) && settings.homeUrl) {
        const fallbackUrl = getFallbackUrl(args.path || args.url);
        if (fallbackUrl) {
          const fallbackArgs = Object.assign({}, args, { url: fallbackUrl });
          delete fallbackArgs.path;
          return originalApiFetch(fallbackArgs);
        }
      }

      return originalApiFetch(args).catch(err => {
        const status = err.status || (err.data && err.data.status);
        const isFallbackTrigger = status === 404 || err.code === 'rest_no_route' || err.code === 'invalid_json';

        if (isFallbackTrigger && (args.path || args.url) && settings.homeUrl) {
          const fallbackUrl = getFallbackUrl(args.path || args.url);
          if (!fallbackUrl) throw err;

          if (!localBroken) {
            console.warn('VAPT Secure: Switching to Pre-emptive Mode (Silent) for REST API.');
            localBroken = true;
            localStorage.setItem('vaptsecure_rest_broken', '1');
          }

          const fallbackArgs = Object.assign({}, args, { url: fallbackUrl });
          delete fallbackArgs.path;
          return originalApiFetch(fallbackArgs);
        }
        throw err;
      });
    };

    Object.keys(originalApiFetch).forEach(key => { patchedApiFetch[key] = originalApiFetch[key]; });
    patchedApiFetch.__vaptsecure_patched = true;
    wp.apiFetch = patchedApiFetch;
  }

  const apiFetch = wp.apiFetch;
  const { __, sprintf } = wp.i18n || {};

  const GeneratedInterface = window.VAPTSECURE_GeneratedInterface || window.vapt_GeneratedInterface;

  const STATUS_LABELS = {
    'All': __('All Lifecycle', 'vaptsecure'),
    'Develop': __('Develop', 'vaptsecure'),
    'Release': __('Release', 'vaptsecure')
  };

  const ClientDashboard = () => {
    const [features, setFeatures] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [error, setError] = useState(null);
    const [activeSeverity, setActiveSeverity] = useState('all');
    const [saveStatus, setSaveStatus] = useState(null);
    const [enforceStatusMap, setEnforceStatusMap] = useState({});
    const [verifFeature, setVerifFeature] = useState(null);

    const SEVERITY_ORDER = ['Critical', 'High', 'Medium', 'Low', 'Info'];

    const setEnforceStatus = (featureKey, msg, type = 'success') => {
      setEnforceStatusMap(prev => ({ ...prev, [featureKey]: { message: msg, type } }));
      setTimeout(() => {
        setEnforceStatusMap(prev => {
          const next = { ...prev };
          delete next[featureKey];
          return next;
        });
      }, 2200);
    };

    const SecurityStatsView = () => {
      const [stats, setStats] = useState(null);
      const [logs, setLogs] = useState([]);
      const [loadingStats, setLoadingStats] = useState(true);
      const [purging, setPurging] = useState(false);
      const [retention, setRetention] = useState(30);

      const fetchStats = async () => {
        setLoadingStats(true);
        try {
          const [summaryData, logsData] = await Promise.all([
            apiFetch({ path: 'vaptsecure/v1/stats/summary' }),
            apiFetch({ path: 'vaptsecure/v1/stats/logs' })
          ]);
          setStats(summaryData);
          setLogs(logsData);
          setRetention(summaryData.retention || 30);
        } catch (e) {
          console.error('[VAPT] Failed to fetch stats:', e);
        } finally {
          setLoadingStats(false);
        }
      };

      useEffect(() => { fetchStats(); }, []);

      const handlePurge = async () => {
        if (!confirm(__('Are you sure you want to clear all security logs? This action cannot be undone.', 'vaptsecure'))) return;
        setPurging(true);
        try {
          await apiFetch({ path: 'vaptsecure/v1/stats/purge', method: 'POST' });
          fetchStats();
        } catch (e) {
          alert(__('Failed to purge logs.', 'vaptsecure'));
        } finally {
          setPurging(false);
        }
      };

      const handleRetentionChange = async (newVal) => {
        try {
          await apiFetch({
            path: 'vaptsecure/v1/stats/settings',
            method: 'POST',
            data: { retention: parseInt(newVal) }
          });
          setRetention(newVal);
        } catch (e) {
          alert(__('Failed to update retention setting.', 'vaptsecure'));
        }
      };

      if (loadingStats) return el('div', { style: { padding: '50px', textAlign: 'center' } }, [el(Spinner), el('p', null, __('Loading Security Insights...', 'vaptsecure'))]);

      return el('div', { className: 'vapt-stats-view' }, [
        el('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '20px', marginBottom: '40px' } }, [
          el(Card, { style: { borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)' } }, [
            el(CardBody, { style: { padding: '24px' } }, [
              el('div', { style: { fontSize: '12px', fontWeight: 600, color: '#64748b', textTransform: 'uppercase', marginBottom: '8px' } }, __('Total Blocks')),
              el('div', { style: { fontSize: '32px', fontWeight: 800, color: '#1d4ed8' } }, stats?.total_blocks || 0),
              el('div', { style: { fontSize: '11px', color: '#94a3b8', marginTop: '4px' } }, __('Persistent Protection Since Launch', 'vaptsecure'))
            ])
          ]),
          el(Card, { style: { borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)' } }, [
            el(CardBody, { style: { padding: '24px' } }, [
              el('div', { style: { fontSize: '12px', fontWeight: 600, color: '#64748b', textTransform: 'uppercase', marginBottom: '8px' } }, __('Top Targeted Risk')),
              el('div', { style: { fontSize: '20px', fontWeight: 700, color: '#1e293b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' } }, stats?.top_risks?.[0]?.feature_key || __('None yet', 'vaptsecure')),
              el('div', { style: { fontSize: '11px', color: '#94a3b8', marginTop: '4px' } }, sprintf(__('%d attempts detected', 'vaptsecure'), stats?.top_risks?.[0]?.count || 0))
            ])
          ]),
          el(Card, { style: { borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)' } }, [
            el(CardBody, { style: { padding: '24px' } }, [
              el('div', { style: { fontSize: '12px', fontWeight: 600, color: '#64748b', textTransform: 'uppercase', marginBottom: '8px' } }, __('Log Management')),
              el('div', { style: { display: 'flex', alignItems: 'center', gap: '10px', marginTop: '10px' } }, [
                el('select', {
                  value: retention,
                  onChange: (e) => handleRetentionChange(e.target.value),
                  style: { padding: '6px 10px', borderRadius: '6px', border: '1px solid #e2e8f0', fontSize: '12px' }
                }, [
                  el('option', { value: 30 }, __('30 Days', 'vaptsecure')),
                  el('option', { value: 60 }, __('60 Days', 'vaptsecure')),
                  el('option', { value: 90 }, __('90 Days', 'vaptsecure'))
                ]),
                el(Button, { isDestructive: true, isSmall: true, isBusy: purging, onClick: handlePurge }, __('Clear', 'vaptsecure'))
              ])
            ])
          ])
        ]),

        el('h3', { style: { fontSize: '18px', fontWeight: 700, marginBottom: '20px', color: '#1e293b' } }, __('Live Security Log')),
        el(Card, { style: { borderRadius: '12px', overflow: 'hidden', border: 'none', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)' } }, [
          el('table', { style: { width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13px' } }, [
            el('thead', { style: { background: '#f8fafc', borderBottom: '1px solid #e2e8f0' } }, [
              el('tr', null, [
                el('th', { style: { padding: '15px 20px', fontWeight: 600, color: '#475569' } }, __('Timestamp')),
                el('th', { style: { padding: '15px 20px', fontWeight: 600, color: '#475569' } }, __('Risk Category')),
                el('th', { style: { padding: '15px 20px', fontWeight: 600, color: '#475569' } }, __('Target URI')),
                el('th', { style: { padding: '15px 20px', fontWeight: 600, color: '#475569' } }, __('IP Address')),
                el('th', { style: { padding: '15px 20px', fontWeight: 600, color: '#475569' } }, __('Action')),
              ])
            ]),
            el('tbody', null, logs.length > 0 ? logs.map(log => el('tr', { key: log.id, style: { borderBottom: '1px solid #f1f5f9' } }, [
              el('td', { style: { padding: '12px 20px' } }, log.created_at),
              el('td', { style: { padding: '12px 20px', fontWeight: 500, color: '#111827' } }, log.feature_key),
              el('td', { style: { padding: '12px 20px', color: '#64748b', fontSize: '12px', maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, log.request_uri),
              el('td', { style: { padding: '12px 20px', fontFamily: 'monospace' } }, log.ip_address),
              el('td', { style: { padding: '12px 20px' } }, el('span', { style: { background: '#fee2e2', color: '#991b1b', padding: '2px 8px', borderRadius: '4px', fontSize: '11px', fontWeight: 600, textTransform: 'uppercase' } }, log.event_type))
            ])) : el('tr', null, [el('td', { colSpan: 5, style: { padding: '40px', textAlign: 'center', color: '#94a3b8' } }, __('No security events logged yet.', 'vaptsecure'))]))
          ])
        ])
      ]);
    };

    useEffect(() => {
      if (saveStatus && saveStatus.type === 'success') {
        const timer = setTimeout(() => setSaveStatus(null), 1500);
        return () => clearTimeout(timer);
      }
    }, [saveStatus]);

    const normalizeSeverity = (s) => {
      if (!s) return 'Info';
      return s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
    };

    const fetchData = (refresh = false) => {
      if (refresh) setIsRefreshing(true);
      else setLoading(true);

      const domain = settings.currentDomain || window.location.hostname;
      apiFetch({ path: `vaptsecure/v1/features?scope=client&domain=${domain}` })
        .then(data => {
          const uniqueFeatures = Array.from(new Map((data.features || []).map(item => {
            const feature = { ...item, severity: normalizeSeverity(item.severity) };
            return [feature.key, feature];
          })).values());

          setFeatures(uniqueFeatures);
          setLoading(false);
          setIsRefreshing(false);
        })
        .catch(err => {
          setError(err.message || 'Failed to load features');
          setLoading(false);
          setIsRefreshing(false);
        });
    };

    useEffect(() => {
      fetchData();
    }, []);

    const updateFeature = (key, data, successMsg, silent = false) => {
      setFeatures(prev => prev.map(f => f.key === key ? { ...f, ...data } : f));
      if (!silent) {
        setSaveStatus({ message: __('Saving...', 'vaptsecure'), type: 'info' });
      }

      return apiFetch({
        path: 'vaptsecure/v1/features/update',
        method: 'POST',
        data: { key, ...data }
      })
        .then((res) => {
          if (!silent) {
            setSaveStatus({ message: successMsg || __('Saved', 'vaptsecure'), type: 'success' });
          }
          return res;
        })
        .catch(err => {
          console.error('Save failed:', err);
          if (!silent) {
            setSaveStatus({ message: __('Save Failed', 'vaptsecure'), type: 'error' });
          }
          throw err;
        });
    };

    const statusFeatures = useMemo(() => {
      return features.filter(f => {
        const s = (f.status || '').toLowerCase();
        return s === 'release';
      });
    }, [features]);

    const severityGroups = useMemo(() => {
      const groups = {};
      statusFeatures.forEach(f => {
        const sev = f.severity;
        if (!groups[sev]) groups[sev] = [];
        groups[sev].push(f);
      });
      return groups;
    }, [statusFeatures]);

    const availableSeverities = useMemo(() => {
      return SEVERITY_ORDER.filter(s => severityGroups[s] && severityGroups[s].length > 0);
    }, [severityGroups]);

    const renderFeatureCard = (f) => {
      const schema = typeof f.generated_schema === 'string' ? JSON.parse(f.generated_schema) : (f.generated_schema || { controls: [] });
      const implControls = schema.controls ? schema.controls.filter(c =>
        !['test_action', 'risk_indicators', 'assurance_badges', 'test_checklist', 'evidence_list'].includes(c.type) &&
        !c.label?.toLowerCase().includes('notes')
      ) : [];

      const automControls = schema.controls ? schema.controls.filter(c => c.type === 'test_action') : [];
      const noteControls = (schema.controls || []).filter(c => {
        const isNote = c.label?.toLowerCase().includes('notes') || c.key?.includes('notes');
        if (!isNote) return false;
        const implData = f.implementation_data ? (typeof f.implementation_data === 'string' ? JSON.parse(f.implementation_data) : f.implementation_data) : {};
        const val = implData[c.key];
        return val && val.toString().trim().length > 0;
      });

      return el(Card, { key: f.key, style: { borderRadius: '12px', border: '1px solid #e5e7eb', boxShadow: '0 1px 3px rgba(0,0,0,0.05)', display: 'flex', flexDirection: 'column' } }, [
        el(CardHeader, { style: { borderBottom: '1px solid #f3f4f6', padding: '15px 20px', background: '#fafafa' } }, [
          el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', width: '100%' } }, [
            el('div', { style: { flex: 1 } }, [
              el('h3', { style: { margin: 0, fontSize: '15px', fontWeight: 700, color: '#111827' } }, f.label),
              f.description && el('p', { style: { margin: '4px 0 0', fontSize: '11px', color: '#64748b', lineHeight: '1.4' } }, f.description)
            ]),
            el('div', { style: { marginLeft: '15px' } }, [
              (() => {
                const driver = schema.enforcement?.driver || (schema.client_deployment?.enforcement?.driver);
                const isHtaccess = driver === 'htaccess';
                const isEnforced = isHtaccess ? true : ((f.is_enforced === undefined || f.is_enforced === null) ? true : (f.is_enforced == 1));

                return el('div', { style: { display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px' } }, [
                  el('div', { style: { display: 'flex', alignItems: 'center', gap: '8px', zIndex: 10 } }, [
                    el('span', { style: { fontSize: '11px', fontWeight: 600, color: '#475569' } }, __('Enforced')),
                    el(ToggleControl, {
                      checked: isEnforced,
                      disabled: isHtaccess,
                      onChange: (val) => {
                        if (isHtaccess) return;
                        setEnforceStatus(f.key, val ? __('Applying...', 'vaptsecure') : __('Removing...', 'vaptsecure'), 'info');
                        updateFeature(f.key, { is_enforced: val, implementation_data: f.implementation_data }, null, true)
                          .then(() => setEnforceStatus(f.key, val ? __('Applied', 'vaptsecure') : __('Removed', 'vaptsecure'), 'success'));
                      },
                      __nextHasNoMarginBottom: true
                    })
                  ]),
                  enforceStatusMap[f.key] && el('span', { style: { fontSize: '9px', fontWeight: '700', color: enforceStatusMap[f.key].type === 'success' ? '#059669' : '#0369a1' } }, enforceStatusMap[f.key].message)
                ]);
              })()
            ])
          ])
        ]),
        el(CardBody, { style: { padding: '24px', flex: 1 } }, [
          el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '25px', alignItems: 'start' } }, [
            el('div', { className: 'vapt-impl-panel', style: { padding: '20px', background: '#fff', borderRadius: '8px', border: '1px solid #e2e8f0' } }, [
              el('h4', { style: { margin: '0 0 20px 0', fontSize: '13px', fontWeight: 700, color: '#1e293b', borderBottom: '1px solid #f1f5f9', paddingBottom: '10px' } }, __('Functional Implementation')),
              GeneratedInterface && el(GeneratedInterface, {
                feature: { ...f, generated_schema: { ...schema, controls: implControls } },
                onUpdate: (data) => updateFeature(f.key, { implementation_data: data }),
                hideProtocol: true,
                showCategoryAsTooltip: true
              })
            ]),
            el('div', { className: 'vapt-verif-panel', style: { display: 'flex', flexDirection: 'column', gap: '15px' } }, [
              automControls.length > 0 && el('div', null, [
                el('h4', { style: { margin: '0 0 15px 0', fontSize: '13px', fontWeight: 700, color: '#0f766e', borderBottom: '1px solid #f1f5f9', paddingBottom: '10px' } }, __('Verification Engine')),
                el(GeneratedInterface, {
                  feature: { ...f, generated_schema: { ...schema, controls: automControls } },
                  onUpdate: (data) => updateFeature(f.key, { implementation_data: data }),
                  hideMonitor: true,
                  hideOpNotes: true
                })
              ]),
              !!f.include_manual_protocol && el(Button, { isSecondary: true, isSmall: true, onClick: () => setVerifFeature(f), icon: 'shield', style: { width: '100%', justifyContent: 'center' } }, __('Verification Protocol', 'vaptsecure'))
            ])
          ])
        ])
      ]);
    };

    const FeatureGrid = ({ items }) => (
      el('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '20px', alignItems: 'start' } }, items.map(renderFeatureCard))
    );

    if (loading) return el('div', { className: 'vapt-loading', style: { display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '300px' } }, [el(Spinner), el('p', null, __('Loading VAPT Admin Dashboard...', 'vaptsecure'))]);
    if (error) return el(Notice, { status: 'error', isDismissible: false }, error);

    return el('div', { className: 'vapt-workbench-root', style: { display: 'flex', minHeight: 'calc(100vh - 120px)', background: '#f9fafb' } }, [
      el('aside', { style: { width: '240px', borderRight: '1px solid #e5e7eb', background: '#fff', flexShrink: 0 } }, [
        el('div', { style: { padding: '30px 20px 10px', fontSize: '11px', fontWeight: 700, color: '#9ca3af', textTransform: 'uppercase' } }, __('Severity Filter')),
        el('div', { style: { display: 'flex', flexDirection: 'column' } }, [
          el('button', {
            onClick: () => setActiveSeverity('all'),
            style: { width: '100%', border: 'none', background: activeSeverity === 'all' ? '#eff6ff' : 'transparent', color: activeSeverity === 'all' ? '#1d4ed8' : '#4b5563', padding: '15px 20px', textAlign: 'left', cursor: 'pointer', borderRight: activeSeverity === 'all' ? '3px solid #1d4ed8' : 'none', fontWeight: 600 }
          }, [__('All Severities', 'vaptsecure'), el('span', { style: { float: 'right', fontSize: '11px', opacity: 0.6 } }, statusFeatures.length)]),
          SEVERITY_ORDER.map(sev => {
            const count = (severityGroups[sev] || []).length;
            if (count === 0) return null;
            return el('button', {
              key: sev,
              onClick: () => setActiveSeverity(sev),
              style: { width: '100%', border: 'none', background: activeSeverity === sev ? '#eff6ff' : 'transparent', color: activeSeverity === sev ? '#1d4ed8' : '#4b5563', padding: '15px 20px', textAlign: 'left', cursor: 'pointer', borderRight: activeSeverity === sev ? '3px solid #1d4ed8' : 'none', fontWeight: activeSeverity === sev ? 600 : 500 }
            }, [sev, el('span', { style: { float: 'right', fontSize: '11px', opacity: 0.6 } }, count)]);
          })
        ]),
        el('div', { style: { padding: '30px 20px 10px', fontSize: '11px', fontWeight: 700, color: '#9ca3af', textTransform: 'uppercase' } }, __('Insights & Logs')),
        el('button', {
          onClick: () => setActiveSeverity('stats'),
          style: { width: '100%', border: 'none', background: activeSeverity === 'stats' ? '#eff6ff' : 'transparent', color: activeSeverity === 'stats' ? '#1d4ed8' : '#4b5563', padding: '15px 20px', textAlign: 'left', cursor: 'pointer', borderRight: activeSeverity === 'stats' ? '3px solid #1d4ed8' : 'none', fontWeight: 600, display: 'flex', gap: '10px', alignItems: 'center' }
        }, [el(Icon, { icon: 'chart-bar', size: 18 }), __('Security Stats & Logs', 'vaptsecure')])
      ]),

      el('main', { style: { flex: 1, padding: '30px', overflowY: 'auto' } }, [
        el('header', { style: { marginBottom: '30px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }, [
          el('div', null, [
            el('h2', { style: { margin: 0, fontSize: '24px', fontWeight: 800, color: '#111827', display: 'flex', alignItems: 'center', gap: '10px' } }, [
              activeSeverity === 'stats' ? __('Security Insights & Logs') : __('VAPT Admin Dashboard'),
              el('span', { style: { fontSize: '12px', color: '#9ca3af', fontWeight: 500 } }, `v${settings.pluginVersion}`)
            ]),
            el('p', { style: { margin: '5px 0 0', color: '#64748b' } }, sprintf(__('Active Protection for %s', 'vaptsecure'), settings.currentDomain || window.location.hostname))
          ]),
          el('div', { style: { display: 'flex', gap: '10px', alignItems: 'center' } }, [
            el(Button, { icon: 'update', isSecondary: true, isBusy: isRefreshing, onClick: () => fetchData(true) }, __('Refresh', 'vaptsecure'))
          ])
        ]),

        activeSeverity === 'stats' ? (
          el(SecurityStatsView, null)
        ) : (
          activeSeverity === 'all' ? (
            availableSeverities.map(sev => el('div', { key: sev, style: { marginBottom: '40px' } }, [
              el('div', { style: { background: '#f8fafc', borderLeft: '4px solid #1d4ed8', padding: '12px 20px', marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '12px' } }, [
                el('h3', { style: { margin: 0, fontSize: '16px', fontWeight: 700 } }, sev),
                el('span', { style: { fontSize: '11px', background: '#eff6ff', color: '#1d4ed8', padding: '2px 8px', borderRadius: '10px' } }, sprintf(__('%d Features', 'vaptsecure'), severityGroups[sev].length))
              ]),
              el(FeatureGrid, { items: severityGroups[sev] })
            ]))
          ) : (
            el(FeatureGrid, { items: severityGroups[activeSeverity] || [] })
          )
        )
      ]),

      verifFeature && el(Modal, {
        title: sprintf(__('Verification Protocol: %s', 'vaptsecure'), verifFeature.label),
        onRequestClose: () => setVerifFeature(null),
        style: { maxWidth: '750px', width: '90%' }
      }, (() => {
        const protocol = verifFeature.test_method || '';
        const checklist = typeof verifFeature.verification_steps === 'string' ? JSON.parse(verifFeature.verification_steps) : (verifFeature.verification_steps || []);
        return el('div', { style: { display: 'flex', flexDirection: 'column', gap: '20px', padding: '10px' } }, [
          el('div', { style: { padding: '20px', background: '#f8fafc', borderRadius: '12px' } }, [
            el('h4', { style: { margin: '0 0 15px 0', fontSize: '12px', fontWeight: 700, color: '#475569', textTransform: 'uppercase' } }, __('Manual Steps')),
            protocol && el('ol', { style: { margin: 0, paddingLeft: '20px', fontSize: '13px', lineHeight: '1.6' } }, protocol.split('\n').filter(l => l.trim()).map((l, i) => el('li', { key: i }, l.replace(/^\d+\.\s*/, '')))),
            checklist.length > 0 && el('div', { style: { marginTop: '20px' } }, [
              el('h5', { style: { fontSize: '11px', fontWeight: 700, color: '#0369a1', textTransform: 'uppercase' } }, __('Checklist')),
              checklist.map((step, i) => el('label', { key: i, style: { display: 'flex', gap: '10px', fontSize: '13px', alignItems: 'center', marginTop: '5px' } }, [el('input', { type: 'checkbox' }), el('span', null, step)]))
            ])
          ]),
          GeneratedInterface && el(GeneratedInterface, { feature: verifFeature, onUpdate: (data) => updateFeature(verifFeature.key, { implementation_data: data }), isGuidePanel: true })
        ]);
      })())
    ]);
  };

  const init = () => {
    const container = document.getElementById('vapt-client-root');
    if (container) render(el(ClientDashboard), container);
  };
  if (document.readyState === 'complete') init(); else document.addEventListener('DOMContentLoaded', init);
})();
