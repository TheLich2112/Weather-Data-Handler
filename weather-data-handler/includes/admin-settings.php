<?php
class WeatherDataAdminSettings {
    private $options;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
    }

    public function add_plugin_page() {
        add_options_page(
            'Weather Data Settings',
            'Weather Data',
            'manage_options',
            'weather-data-settings',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page() {
        $this->options = get_option('weather_data_options'); ?>
        <div class="wrap">
            <h2>Weather Data Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('weather_data_group');
                do_settings_sections('weather-data-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'weather_data_group',
            'weather_data_options',
            [$this, 'sanitize']
        );

        add_settings_section(
            'weather_data_section',
            'General Settings',
            [$this, 'section_info'],
            'weather-data-settings'
        );

        add_settings_field(
            'weather_title',
            'Weather Display Title',
            [$this, 'weather_title_callback'],
            'weather-data-settings',
            'weather_data_section'
        );

        add_settings_field(
            'weather_location',
            'Weather Station Location',
            [$this, 'weather_location_callback'],
            'weather-data-settings',
            'weather_data_section'
        );

        add_settings_field(
            'clientraw_url',
            'Clientraw.txt URL',
            [$this, 'clientraw_url_callback'],
            'weather-data-settings',
            'weather_data_section'
        );

        add_settings_field(
            'refresh_rate',
            'Refresh Rate',
            [$this, 'refresh_rate_callback'],
            'weather-data-settings',
            'weather_data_section'
        );

        add_settings_field(
            'enabled_metrics',
            'Enabled Metrics',
            [$this, 'metrics_callback'],
            'weather-data-settings',
            'weather_data_section'
        );
    }

    public function sanitize($input) {
        $new_input = [];
        
        if(isset($input['clientraw_url']))
            $new_input['clientraw_url'] = esc_url_raw($input['clientraw_url']);
        
        if(isset($input['refresh_rate']))
            $new_input['refresh_rate'] = sanitize_text_field($input['refresh_rate']);
        
        if(isset($input['enabled_metrics']))
            $new_input['enabled_metrics'] = array_map('sanitize_text_field', $input['enabled_metrics']);

        if(isset($input['weather_title']))
            $new_input['weather_title'] = sanitize_text_field($input['weather_title']);

        if(isset($input['weather_location']))
            $new_input['weather_location'] = sanitize_text_field($input['weather_location']);
        
        return $new_input;
    }

    public function section_info() {
        echo 'Configure your weather data display settings below:';
    }

    public function weather_title_callback() {
        printf(
            '<input type="text" id="weather_title" name="weather_data_options[weather_title]" value="%s" class="regular-text" />',
            isset($this->options['weather_title']) ? esc_attr($this->options['weather_title']) : ''
        );
    }

    public function weather_location_callback() {
        printf(
            '<input type="text" id="weather_location" name="weather_data_options[weather_location]" value="%s" class="regular-text" />',
            isset($this->options['weather_location']) ? esc_attr($this->options['weather_location']) : ''
        );
    }

    public function clientraw_url_callback() {
        printf(
            '<input type="text" id="clientraw_url" name="weather_data_options[clientraw_url]" value="%s" class="regular-text" />',
            isset($this->options['clientraw_url']) ? esc_attr($this->options['clientraw_url']) : ''
        );
    }

    public function refresh_rate_callback() {
        $refresh_rates = [
            '60' => '1 Minute',
            '600' => '10 Minutes',
            '1800' => '30 Minutes'
        ];
        echo '<select id="refresh_rate" name="weather_data_options[refresh_rate]">';
        foreach ($refresh_rates as $value => $label) {
            $selected = isset($this->options['refresh_rate']) && $this->options['refresh_rate'] == $value ? 'selected' : '';
            echo "<option value='$value' $selected>$label</option>";
        }
        echo '</select>';
    }

    public function metrics_callback() {
        $available_metrics = [
            'temperature' => 'Temperature',
            'humidity' => 'Humidity',
            'wind_speed' => 'Wind Speed',
            'wind_direction' => 'Wind Direction',
            'rainfall' => 'Rainfall',
            'pressure' => 'Pressure'
        ];
        
        foreach ($available_metrics as $metric => $label) {
            $checked = isset($this->options['enabled_metrics']) && 
                      in_array($metric, $this->options['enabled_metrics']) ? 'checked' : '';
            echo "<label><input type='checkbox' name='weather_data_options[enabled_metrics][]' 
                  value='$metric' $checked> $label</label><br>";
        }
    }
}
