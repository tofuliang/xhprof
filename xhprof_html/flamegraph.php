<?php
//  Copyright (c) 2009 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

/**
 *
 * A flamegraph generator for XHProf.
 *
 * * This file is part of the UI/reporting component,
 *   used for viewing results of XHProf runs from a
 *   browser.
 *
 */

require_once dirname(dirname(__FILE__)) . '/xhprof_lib/defaults.php';
require_once XHPROF_CONFIG;

if (false !== $controlIPs && !in_array($_SERVER['REMOTE_ADDR'], $controlIPs))
{
    die("You do not have permission to view this page.");
}

include_once XHPROF_LIB_ROOT . '/display/xhprof.php';

ini_set('max_execution_time', 100);

$params = array(// run id param
    'run' => array(XHPROF_STRING_PARAM, ''),

    // source/namespace/type of run
    'source' => array(XHPROF_STRING_PARAM, 'xhprof'),

    // the focus function, if it is set, only directly
    // parents/children functions of it will be shown.
    'func' => array(XHPROF_STRING_PARAM, ''),

    // image type, can be 'jpg', 'gif', 'ps', 'png'
    'type' => array(XHPROF_STRING_PARAM, 'png'),

    // only functions whose exclusive time over the total time
    // is larger than this threshold will be shown.
    // default is 0.01.
    'threshold' => array(XHPROF_FLOAT_PARAM, 0.01),

    // whether to show critical_path
    'critical' => array(XHPROF_BOOL_PARAM, true),

    // first run in diff mode.
    'run1' => array(XHPROF_STRING_PARAM, ''),

    // second run in diff mode.
    'run2' => array(XHPROF_STRING_PARAM, '')
);

// pull values of these params, and create named globals for each param
xhprof_param_init($params);

$xhprof_runs_impl = new XHProfRuns_Default();

if (!empty($run)) {
    $description = null;
    list($raw_data, $a) = $xhprof_runs_impl->get_run($run, $source, $description);
    if (!$raw_data) {
        xhprof_error("Raw data is empty");
        return "";
    }
    file_put_contents("{$_xhprof['dot_tempdir']}/xhrof_$run.data",json_encode($raw_data));
    $root = dirname(XHPROF_LIB_ROOT);
    $cmd="{$_xhprof['php_binary']} $root/vendor/bin/xhprof2flamegraph -f {$_xhprof['dot_tempdir']}/xhrof_$run.data|$root/vendor/bin/flamegraph.pl";
    exec($cmd,$svg,$return);
    unlink("{$_xhprof['dot_tempdir']}/xhrof_$run.data");
    $svg = str_replace("\n","",implode($svg));
    xhprof_generate_mime_header('svg', strlen($svg));
    echo $svg;
}
