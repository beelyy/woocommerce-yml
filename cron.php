<?php

class WOO_YML_Cron {

    // if ( ! wp_next_scheduled( 'wooyml_cron_hook' ) ) {
    //     wp_schedule_event( time(), 'twicedaily', 'wooyml_cron_hook' );
    // }

    function __construct() {
        add_action('wooyml_cron_hook', array(&$this, 'wooyml_cron_job'));
        add_filter('cron_schedules', array(&$this, 'custom_cron_schedules'));        
    }

    public function custom_cron_schedules($schedules) {
        $schedules['weekly'] = array(
            'interval' => 604800, // 604800 seconds = 1 week
            'display'  => __('Weekly', 'wooyml')
        );
        $schedules['monthly'] = array(
            'interval' => 2592000,
            'display'  => __('Monthly', 'wooyml')
        );
        return $schedules;
    }

    /**
     * Update cron schedule.
     *
     * @param string $interval  Cron interval. Accepts: hourly, daily, twicedaily, weekly, monthly.
     */
    public static function wooyml_cron_update($interval) {
        wp_clear_scheduled_hook('wooyml_cron_hook');
        wp_schedule_event(time(), $interval, 'wooyml_cron_hook');
    }

    public function wooyml_cron_job() {
        // +debug
        // ob_start(); print_r('debug'."\n"); $rs = ob_get_contents(); ob_end_clean();
        // $fp = fopen(dirname(__FILE__).'/debug-cron.txt','w'); fputs($fp, $rs); fclose($fp);
        // -debug
        WOO_YML::write_yml_file();
    }

}
new WOO_YML_Cron();
?>
