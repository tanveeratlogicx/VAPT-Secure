// VAPT Secure Admin Entry Point
// Loads all modular components and renders the main App

// Global check-in for diagnostics
window.vaptScriptLoaded = true;

// Debug mode control
var VAPT_DEBUG = window.VAPT_DEBUG || false;

// Helper function for conditional logging
var vaptLog = window.vaptLog || {
  log: (...args) => VAPT_DEBUG && console.log('[VAPT]', ...args),
  warn: (...args) => VAPT_DEBUG && console.warn('[VAPT]', ...args),
  error: (...args) => console.error('[VAPT]', ...args), // Always show errors
  debug: (...args) => VAPT_DEBUG && console.debug('[VAPT]', ...args),
  info: (...args) => VAPT_DEBUG && console.info('[VAPT]', ...args)
};

(function () {
  if (typeof wp === 'undefined') {
    vaptLog.error('"wp" global is missing!');
    return;
  }

  const { render, useState, useEffect, useMemo, Fragment, createElement: el } = wp.element || {};
  const components = wp.components || {};
  const {
    TabPanel, Panel, PanelBody, PanelRow, Button, Dashicon,
    ToggleControl, SelectControl, Modal, TextControl, Spinner,
    Notice, Placeholder, Dropdown, CheckboxControl, BaseControl, Icon,
    TextareaControl, Card, CardHeader, CardBody, Tooltip
  } = {
    TabPanel: components.TabPanel || components.__experimentalTabPanel,
    Panel: components.Panel,
    PanelBody: components.PanelBody,
    PanelRow: components.PanelRow,
    Button: components.Button,
    Dashicon: components.Dashicon,
    ToggleControl: components.ToggleControl,
    SelectControl: components.SelectControl,
    Modal: components.Modal,
    TextControl: components.TextControl,
    Spinner: components.Spinner,
    Notice: components.Notice,
    Placeholder: components.Placeholder,
    Dropdown: components.Dropdown,
    CheckboxControl: components.CheckboxControl,
    BaseControl: components.BaseControl,
    Icon: components.Icon,
    TextareaControl: components.TextareaControl,
    Card: components.Card,
    CardHeader: components.CardHeader,
    CardBody: components.CardBody,
    Tooltip: components.Tooltip || components.__experimentalTooltip
  };
  
  const { __, sprintf } = wp.i18n || {};
  const apiFetch = wp.apiFetch;
  
  // Global Settings from wp_localize_script
  const settings = window.vaptSecureSettings || {};
  const isSuper = settings.isSuper || false;

  // Import modular components
  const App = window.vaptAdmin.App;
  const ApiHelper = window.vaptAdmin.ApiHelper;
  
  // Initialize API helper with global settings
  if (ApiHelper) {
    ApiHelper.init(settings);
  }

  // Apply REST hotpatch
  window.vaptAdmin.applyRestHotpatch(settings);

  // Render the main application
  const container = document.getElementById('vaptsecure-admin');
  if (container && App) {
    render(el(App, { settings, isSuper, apiFetch, __, sprintf }), container);
  } else if (container) {
    vaptLog.error('App component not found in vaptAdmin namespace');
    render(el('div', {}, __('Admin components failed to load', 'vaptsecure')), container);
  }
})();