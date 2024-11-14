jQuery(document).ready(function($) {
    let refreshTimer;
    
    function startWeatherRefresh() {
        // Initial fetch
        fetchWeatherData();
        
        // Set up timer based on admin settings
        const refreshRate = parseInt(weatherAjax.refreshRate) * 1000; // Convert to milliseconds
        refreshTimer = setInterval(fetchWeatherData, refreshRate);
    }
    
    function fetchWeatherData() {
        $.ajax({
            url: weatherAjax.ajaxurl,
            type: 'GET',
            data: {
                action: 'get_weather_data',
                nonce: weatherAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateWeatherDisplay(response.data);
                }
            }
        });
    }
    
    function updateWeatherDisplay(data) {
        const enabledMetrics = weatherAjax.enabledMetrics;
        
        enabledMetrics.forEach(metric => {
            if (data[metric]) {
                $(`#weather-${metric}`).text(data[metric]);
            }
        });
    }
    
    startWeatherRefresh();
});
