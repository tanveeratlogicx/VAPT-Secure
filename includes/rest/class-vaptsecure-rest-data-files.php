<?php

/**
 * Data Files REST Controller for VAPT Secure
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once VAPTSECURE_PATH . 'includes/rest/class-vaptsecure-rest-base.php';

class VAPTSECURE_REST_Data_Files extends VAPTSECURE_REST_Base
{
    public function register_routes()
    {
        register_rest_route(
            'vaptsecure/v1', '/data-files/all', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_data_files'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/data-files', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_data_files'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/update-hidden-files', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_hidden_files'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/data-files/remove', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_data_file'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/upload-json', array(
            'methods'  => 'POST',
            'callback' => array($this, 'upload_json'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/data-files/meta', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_file_meta'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/active-file', array(
            'methods'  => array('GET', 'POST'),
            'callback' => array($this, 'handle_active_file'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    public function get_all_data_files($request)
    {
        $data_dir = VAPTSECURE_PATH . 'data';
        if (!is_dir($data_dir)) {
            return $this->success_response(array('files' => array()));
        }

        $all_json = array_filter(
            scandir($data_dir), function ($f) {
                return strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'json';
            }
        );

        $hidden_files = get_option('vaptsecure_hidden_json_files', array());
        $removed_files = get_option('vaptsecure_removed_json_files', array());
        $hidden_normalized = array_map('sanitize_file_name', $hidden_files);
        $removed_normalized = array_map('sanitize_file_name', $removed_files);

        $files = array();
        foreach ($all_json as $file) {
            $normalized = sanitize_file_name($file);
            $file_path = $data_dir . '/' . $file;
            $files[] = array(
                'name' => $file,
                'normalized' => $normalized,
                'size' => filesize($file_path),
                'modified' => filemtime($file_path),
                'is_hidden' => in_array($normalized, $hidden_normalized),
                'is_removed' => in_array($normalized, $removed_normalized),
                'exists' => file_exists($file_path)
            );
        }

        return $this->success_response(array('files' => $files));
    }

    public function get_data_files($request)
    {
        $default_file = defined('VAPTSECURE_ACTIVE_DATA_FILE') ? VAPTSECURE_ACTIVE_DATA_FILE : 'interface_schema_v2.0.json';
        $requested_file = $request->get_param('file') ?: $default_file;

        $files_to_load = array();
        if ($requested_file === '__all__') {
            $data_dir = VAPTSECURE_PATH . 'data';
            if (is_dir($data_dir)) {
                $all_json = array_filter(
                    scandir($data_dir), function ($f) {
                        return strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'json';
                    }
                );
                $hidden_files = get_option('vaptsecure_hidden_json_files', array());
                $removed_files = get_option('vaptsecure_removed_json_files', array());
                $hidden_normalized = array_map('sanitize_file_name', $hidden_files);
                $removed_normalized = array_map('sanitize_file_name', $removed_files);

                foreach ($all_json as $f) {
                    $normalized = sanitize_file_name($f);
                    if (!in_array($normalized, $hidden_normalized) && !in_array($normalized, $removed_normalized)) {
                        $files_to_load[] = $f;
                    }
                }
            }
        } else {
            $files_to_load = array_filter(explode(',', $requested_file));
        }

        $files_to_load = array_filter(
            $files_to_load, function ($f) {
                return file_exists(VAPTSECURE_PATH . 'data/' . sanitize_file_name($f));
            }
        );

        $available_files = array();
        foreach ($files_to_load as $file) {
            $available_files[] = array(
                'name' => $file,
                'path' => VAPTSECURE_PATH . 'data/' . $file,
                'exists' => true
            );
        }

        return $this->success_response(array('files' => $available_files));
    }

    public function update_hidden_files($request)
    {
        $hidden_files = $request->get_param('hidden_files');
        if (!is_array($hidden_files)) {
            return $this->error_response('hidden_files must be an array', 'invalid_parameter', 400);
        }

        $sanitized = array_map('sanitize_file_name', $hidden_files);
        update_option('vaptsecure_hidden_json_files', $sanitized);

        $this->log_security_event('hidden_files_updated', array(
            'count' => count($sanitized),
            'files' => $sanitized
        ));

        return $this->success_response(array(
            'hidden_files' => $sanitized,
            'count' => count($sanitized)
        ), 'Hidden files updated successfully');
    }

    public function remove_data_file($request)
    {
        $filename = $request->get_param('filename');
        if (empty($filename)) {
            return $this->error_response('filename parameter is required', 'missing_parameter', 400);
        }

        $sanitized = sanitize_file_name($filename);
        $file_path = VAPTSECURE_PATH . 'data/' . $sanitized;

        if (!file_exists($file_path)) {
            return $this->not_found_response('File not found');
        }

        $removed_files = get_option('vaptsecure_removed_json_files', array());
        if (!in_array($sanitized, $removed_files)) {
            $removed_files[] = $sanitized;
            update_option('vaptsecure_removed_json_files', $removed_files);
        }

        $this->log_security_event('data_file_removed', array(
            'filename' => $sanitized,
            'path' => $file_path
        ));

        return $this->success_response(array(
            'filename' => $sanitized,
            'removed' => true
        ), 'File marked as removed');
    }

    public function upload_json($request)
    {
        $files = $request->get_file_params();
        if (empty($files) || !isset($files['file'])) {
            return $this->error_response('No file uploaded', 'no_file', 400);
        }

        $file = $files['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->error_response('File upload error: ' . $file['error'], 'upload_error', 400);
        }

        $filename = sanitize_file_name($file['name']);
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'json') {
            return $this->error_response('Only JSON files are allowed', 'invalid_file_type', 400);
        }

        $target_path = VAPTSECURE_PATH . 'data/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $content = file_get_contents($target_path);
            $json = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                unlink($target_path);
                return $this->error_response('Invalid JSON file', 'invalid_json', 400);
            }

            $removed_files = get_option('vaptsecure_removed_json_files', array());
            $sanitized = sanitize_file_name($filename);
            if (in_array($sanitized, $removed_files)) {
                $removed_files = array_diff($removed_files, array($sanitized));
                update_option('vaptsecure_removed_json_files', $removed_files);
            }

            $this->log_security_event('json_file_uploaded', array(
                'filename' => $filename,
                'size' => $file['size'],
                'target_path' => $target_path
            ));

            return $this->success_response(array(
                'filename' => $filename,
                'path' => $target_path,
                'size' => $file['size'],
                'is_valid_json' => true
            ), 'File uploaded successfully');
        }

        return $this->error_response('Failed to move uploaded file', 'file_move_error', 500);
    }

    public function update_file_meta($request)
    {
        $filename = $request->get_param('filename');
        $meta = $request->get_param('meta');

        if (empty($filename) || !is_array($meta)) {
            return $this->error_response('filename and meta parameters are required', 'missing_parameters', 400);
        }

        $sanitized = sanitize_file_name($filename);
        $file_path = VAPTSECURE_PATH . 'data/' . $sanitized;

        if (!file_exists($file_path)) {
            return $this->not_found_response('File not found');
        }

        $content = file_get_contents($file_path);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return $this->error_response('Invalid JSON file', 'invalid_json', 400);
        }

        foreach ($meta as $key => $value) {
            $data[$key] = $value;
        }

        file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));

        $this->log_security_event('file_meta_updated', array(
            'filename' => $sanitized,
            'meta_keys' => array_keys($meta)
        ));

        return $this->success_response(array(
            'filename' => $sanitized,
            'updated_keys' => array_keys($meta)
        ), 'File metadata updated successfully');
    }

    public function handle_active_file($request)
    {
        if ($request->get_method() === 'GET') {
            $active_file = defined('VAPTSECURE_ACTIVE_DATA_FILE') ? VAPTSECURE_ACTIVE_DATA_FILE : 'interface_schema_v2.0.json';
            return $this->success_response(array('active_file' => $active_file));
        }

        if ($request->get_method() === 'POST') {
            $filename = $request->get_param('filename');
            if (empty($filename)) {
                return $this->error_response('filename parameter is required', 'missing_parameter', 400);
            }

            $sanitized = sanitize_file_name($filename);
            $file_path = VAPTSECURE_PATH . 'data/' . $sanitized;

            if (!file_exists($file_path)) {
                return $this->not_found_response('File not found');
            }

            define('VAPTSECURE_ACTIVE_DATA_FILE', $sanitized);

            $this->log_security_event('active_file_changed', array(
                'filename' => $sanitized,
                'previous' => defined('VAPTSECURE_ACTIVE_DATA_FILE') ? VAPTSECURE_ACTIVE_DATA_FILE : 'interface_schema_v2.0.json'
            ));

            return $this->success_response(array(
                'active_file' => $sanitized,
                'path' => $file_path
            ), 'Active file updated successfully');
        }

        return $this->error_response('Method not allowed', 'method_not_allowed', 405);
    }
}