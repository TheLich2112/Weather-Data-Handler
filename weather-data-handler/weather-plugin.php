<?php
/*
Plugin Name: Weather Data Handler
Description: Processes and displays weather data from clientraw.txt
Version: 1.0
Author: Marcus Hazel-McGown - MM0ZIF
*/

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

class WeatherDataHandler {
    private $options;
    
    public function __construct() {
        // Initialize options with defaults
        $this->options = get_option('weather_data_options', [
            'enabled_metrics' => ['temperature', 'humidity'],
            'refresh_rate' => 600
        ]);
        
        // Register AJAX handlers
        add_action('init', function() {
            add_action('wp_ajax_get_weather_data', array($this, 'handle_weather_request'));
            add_action('wp_ajax_nopriv_get_weather_data', array($this, 'handle_weather_request'));
        });
        
        // Load admin settings
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
            new WeatherDataAdminSettings();
        }
        
        // Register shortcode
        add_shortcode('weather_display', array($this, 'weather_shortcode'));
    }
    
    public function handle_weather_request() {
        check_ajax_referer('weather_data_nonce', 'nonce');
        
        $weather_data = $this->fetch_weather_data();
        
        if ($weather_data) {
            wp_send_json_success($this->parse_weather_data($weather_data));
        } else {
            wp_send_json_error(['message' => 'Unable to fetch weather data']);
        }
    }
    
    private function fetch_weather_data() {
        if (empty($this->options['clientraw_url'])) {
            return false;
        }

        $response = wp_remote_get($this->options['clientraw_url']);
        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }
    
    private function parse_weather_data($data) {
        $weatherData = explode(' ', $data);
        
        $metrics = [
            'temperature' => isset($weatherData[4]) ? floatval($weatherData[4]) : null,
            'humidity' => isset($weatherData[5]) ? floatval($weatherData[5]) : null,
            'wind_speed' => isset($weatherData[2]) ? floatval($weatherData[2]) : null,
            'wind_direction' => isset($weatherData[3]) ? floatval($weatherData[3]) : null,
            'rainfall' => isset($weatherData[7]) ? floatval($weatherData[7]) : null,
            'pressure' => isset($weatherData[6]) ? floatval($weatherData[6]) : null,
        ];
        
        // Filter metrics based on enabled settings
        $enabled_metrics = isset($this->options['enabled_metrics']) ? $this->options['enabled_metrics'] : ['temperature', 'humidity'];
        return array_intersect_key($metrics, array_flip($enabled_metrics));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('weather-handler', plugins_url('js/weather-handler.js', __FILE__), ['jquery'], '1.0', true);
        wp_localize_script('weather-handler', 'weatherAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('weather_data_nonce'),
            'refreshRate' => isset($this->options['refresh_rate']) ? $this->options['refresh_rate'] : 600,
            'enabledMetrics' => isset($this->options['enabled_metrics']) ? $this->options['enabled_metrics'] : ['temperature', 'humidity']
        ]);
    }
    
    public function weather_shortcode($atts) {
        $this->enqueue_scripts();
        
        $enabled_metrics = isset($this->options['enabled_metrics']) ? $this->options['enabled_metrics'] : ['temperature', 'humidity'];
        
        $output = '<div class="weather-display">';
        
        // Add title if set
        if (!empty($this->options['weather_title'])) {
            $output .= sprintf('<h3>%s</h3>', esc_html($this->options['weather_title']));
        }
        
        // Add location if set
        if (!empty($this->options['weather_location'])) {
            $output .= sprintf('<div class="weather-location"><strong>Location:</strong> %s</div>', esc_html($this->options['weather_location']));
        }
        
        $metric_labels = [
            'temperature' => 'Temperature: ',
            'humidity' => 'Humidity: ',
            'wind_speed' => 'Wind Speed: ',
            'wind_direction' => 'Wind Direction: ',
            'rainfall' => 'Rainfall: ',
            'pressure' => 'Pressure: '
        ];
        
        foreach ($enabled_metrics as $metric) {
            $output .= sprintf(
                '<div class="weather-%s"><span class="metric-label">%s</span><span id="weather-%s">Loading %s...</span></div>',
                esc_attr($metric),
                esc_html($metric_labels[$metric]),
                esc_attr($metric),
                esc_html(ucfirst($metric))
            );
        }
        $output .= '</div>';
        
        return $output;
    }
}

// Initialize plugin
function init_weather_plugin() {
    global $weather_plugin;
    $weather_plugin = new WeatherDataHandler();
    add_action('wp_enqueue_scripts', array($weather_plugin, 'enqueue_scripts'));
}
add_action('plugins_loaded', 'init_weather_plugin');
