<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_coursebuilder', get_string('pluginname', 'local_coursebuilder'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_coursebuilder/webhookurl',
        get_string('webhookurl', 'local_coursebuilder'),
        get_string('webhookurl_desc', 'local_coursebuilder'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_coursebuilder/webhooktoken',
        get_string('webhooktoken', 'local_coursebuilder'),
        get_string('webhooktoken_desc', 'local_coursebuilder'),
        '',
        PARAM_TEXT
    ));
}
