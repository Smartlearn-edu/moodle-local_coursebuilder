<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_coursebuilder', get_string('pluginname', 'local_coursebuilder'));
    $ADMIN->add('localplugins', $settings);
    
    // Add an empty HTML or dummy text if needed, or just let it be blank for now.
    $settings->add(new admin_setting_heading('local_coursebuilder_settings', '', get_string('plugin_description', 'local_coursebuilder')));
}
