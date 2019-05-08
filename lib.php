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

    $preset = !empty($theme->settings->preset) ? $theme->settings->preset : 'light';

    // Main CSS - Get the CSS from theme Classic.
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/classic/pre.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/preset/plain.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/classic/post.scss');

    // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.
    $pre = file_get_contents($CFG->dirroot . '/theme/picture/scss/pre.scss');
    $pre .= file_get_contents($CFG->dirroot . '/theme/picture/scss/' . $preset . '-pre.scss');

    // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.
    $post = file_get_contents($CFG->dirroot . '/theme/picture/scss/post.scss');
    $post .= file_get_contents($CFG->dirroot . '/theme/picture/scss/' . $preset . '-post.scss');

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


/**
 * Get the domain for this theme.
 *
 * @return String domain
 */
function theme_picture_get_preset() {
    global $PAGE;

    $preset = optional_param('preset', 'light', PARAM_TEXT);

    if (!in_array($preset, ['dark', 'light', 'green'])) {
        $preset = 'light';
    }
    return $preset;
}

/**
 * Allows to modify URL and cache file for the theme CSS.
 * For this to work a core hack is required in lib/outputlib.php
 *
 * @param moodle_url[] $urls
 */
function theme_picture_alter_css_urls(&$urls) {
    global $CFG, $PAGE;

    if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
        // No CSS switch during behat runs, or it will take ages to run a scenario.
        return;
    }

    $preset = theme_picture_get_preset();

    foreach (array_keys($urls) as $i) {
        if (!$urls[$i] instanceof moodle_url) {
            continue;
        }
        $pathstyles = preg_quote($CFG->wwwroot.'/theme/styles.php', '|');
        if (preg_match("|^$pathstyles(/_s)?(.*)$|", $urls[$i]->out(false), $matches)) {
            if (!empty($CFG->slasharguments)) {
                $parts = explode('/', $matches[2]);
                $parts[3] = right_to_left() ? 'all-' . $preset . '-rtl' : 'all-' . $preset;
                $urls[$i] = new moodle_url('/theme/picture/css.php');
                $urls[$i]->set_slashargument($matches[1] . join('/', $parts));
            } else {
                continue;
            }
        } else if (strpos($urls[$i]->out(false), $CFG->wwwroot.'/theme/styles_debug.php') === 0) {
            continue;
        }
    }
}