<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Photo backgrounds callbacks.
 *
 * @package    theme_pictures
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string All fixed Sass for this theme.
 */
function theme_picture_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';

    $fs = get_file_storage();

    // Main CSS - Get the CSS from theme Classic.
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/classic/pre.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/preset/default.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/classic/post.scss');

    // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.
    $pre = file_get_contents($CFG->dirroot . '/theme/picture/scss/pre.scss');

    // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.
    $post = file_get_contents($CFG->dirroot . '/theme/picture/scss/post.scss');

    // Combine them together.
    return $pre . "\n" . $scss . "\n" . $post;
}

/**
 * Returns variables for Sass.
 *
 * We will inject some Sass variables from the settings that the user has defined
 * for the theme.
 *
 * @param theme_config $theme The theme config object.
 * @return String with Sass variables.
 */
function theme_picture_get_pre_scss($theme) {
    // This will be the array to store the Sass variables and values.
    $variables = array();
    $settings = $theme->settings;

    // These are all the background images configurable in the theme settings.
    $backgrounds = array('defaultbackgroundimage', 'loginbackgroundimage', 'frontpagebackgroundimage',
        'dashboardbackgroundimage', 'incoursebackgroundimage');

    foreach ($backgrounds as $background) {
        if (!empty($settings->$background)) {
            $backgroundfile = $theme->setting_file_url($background, $background);
        } else {
            $backgroundfile = '';
        }
        $variables[$background] = "url('" . $backgroundfile . "')";
    }

    // The returned string needs to be valid Sass so we translate our stored variables to Sass
    // $variable : value; lines.
    $scss = '';
    foreach ($variables as $key => $value) {
        $scss .= "$" . $key . ": " . $value . ";\n";
    }

    return $scss;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_picture_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM && strpos($filearea, 'backgroundimage')) {
        $theme = theme_config::load('picture');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}