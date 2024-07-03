<?php
/*
Plugin Name: Custom Email Exporter
Description: This plugin is to Export emails into the CSV File.
Version: 0.01
Author: Ali Nawaz Sahi
*/

function add_export_button_to_email_log_page() {
    global $hook_suffix;

    // Check if we are on the correct page
    if ($hook_suffix == 'toplevel_page_email-log') {
        ?>
        <script>
            jQuery(document).ready(function ($) {
                // Add the "Export CSV" button
                var exportButton = $('<a style="margin-top:10px;" class="button-primary">Export CSV</a>');
                exportButton.insertAfter('.wrap h2');

                // Handle button click
                exportButton.on('click', function (e) {
                  e.preventDefault();

               // Trigger AJAX request to handle the export
                  $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  data: {
                  action: 'handle_export_logs',
                  nonce: '<?php echo wp_create_nonce('export_logs_nonce'); ?>', // Add nonce for security
             },
         success: function (response) {
            // Check if the response is a valid CSV content
            if (response.startsWith('id,')) {
                // Create a Blob from the CSV content
                var blob = new Blob([response], { type: 'text/csv' });

                // Create a download link and trigger the download
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = 'email_logs.csv';
                link.click();
            } else {
                // Display the response in the console for debugging
                console.error('Export failed:', response);
            }
        },
        error: function (error) {
            console.error('AJAX request failed:', error);
        },
    });
});

            });
        </script>
        <?php
    }
}
add_action('admin_footer', 'add_export_button_to_email_log_page');

function handle_export_logs() {
    error_reporting(0);
    // Verify the nonce
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'export_logs_nonce')) {
        global $wpdb;

        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}email_log", ARRAY_A);

        if (!empty($logs)) {
            $csv_file = fopen('email_logs.csv', 'w');

            // Add CSV headers
            fputcsv($csv_file, array_keys($logs[0]));

            // Add data rows
            foreach ($logs as $log) {
                fputcsv($csv_file, $log);
            }

            fclose($csv_file);

            // Trigger file download
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=email_logs.csv');
            readfile('email_logs.csv');
            exit();
        }
    } else {
        wp_die('Access denied.');
    }
}

// The 'wp_ajax_' hook is used for AJAX requests initiated from the admin panel
add_action('wp_ajax_handle_export_logs', 'handle_export_logs');
