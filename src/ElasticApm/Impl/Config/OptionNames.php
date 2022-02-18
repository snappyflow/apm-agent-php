<?php

/*
 * Licensed to Elasticsearch B.V. under one or more contributor
 * license agreements. See the NOTICE file distributed with
 * this work for additional information regarding copyright
 * ownership. Elasticsearch B.V. licenses this file to you under
 * the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

declare(strict_types=1);

namespace Elastic\Apm\Impl\Config;

use Elastic\Apm\Impl\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class OptionNames
{
    use StaticClassTrait;

    public static $API_KEY = 'api_key';
    public static $ASYNC_BACKEND_COMM = 'async_backend_comm';
    public static $BREAKDOWN_METRICS = 'breakdown_metrics';
    public static $DEV_INTERNAL = 'dev_internal';
    public static $DISABLE_INSTRUMENTATIONS = 'disable_instrumentations';
    public static $DISABLE_SEND = 'disable_send';
    public static $ENABLED = 'enabled';
    public static $ENVIRONMENT = 'environment';
    public static $HOSTNAME = 'hostname';
    public static $LOG_LEVEL = 'log_level';
    public static $LOG_LEVEL_SYSLOG = 'log_level_syslog';
    public static $LOG_LEVEL_STDERR = 'log_level_stderr';
    public static $SECRET_TOKEN = 'secret_token';
    public static $SERVER_TIMEOUT = 'server_timeout';
    public static $SERVER_URL = 'server_url';
    public static $SERVICE_NAME = 'service_name';
    public static $SERVICE_NODE_NAME = 'service_node_name';
    public static $SERVICE_VERSION = 'service_version';
    public static $TRANSACTION_IGNORE_URLS = 'transaction_ignore_urls';
    public static $TRANSACTION_MAX_SPANS = 'transaction_max_spans';
    public static $TRANSACTION_SAMPLE_RATE = 'transaction_sample_rate';
    public static $URL_GROUPS = 'url_groups';
    public static $VERIFY_SERVER_CERT = 'verify_server_cert';
    public static $GLOBAL_LABELS = 'global_labels';
}
