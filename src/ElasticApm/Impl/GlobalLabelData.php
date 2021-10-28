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

namespace Elastic\Apm\Impl;

use Elastic\Apm\Impl\BackendComm\SerializationUtil;
use Elastic\Apm\Impl\Log\LoggableInterface;
use Elastic\Apm\Impl\Log\LoggableTrait;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
class GlobalLabelData implements SerializableDataInterface, LoggableInterface
{
    use LoggableTrait;

    /**
     * @var string|null
     * Snappyflow project name
     */
    public $projectName = null;

    /**
     * @var string|null
     * Snappyflow application name
     */
    public $appName = null;

    /**
     * @var string|null
     * Snappyflow application name
     */
    public $profileId = null;

    /** @inheritDoc */
    public function jsonSerialize()
    {
        $result = [];

        SerializationUtil::addNameValueIfNotNull('_tag_projectName', $this->projectName, /* ref */ $result);
        SerializationUtil::addNameValueIfNotNull('_tag_appName', $this->appName, /* ref */ $result);
        SerializationUtil::addNameValueIfNotNull('_tag_profileId', $this->profileId, /* ref */ $result);

        return SerializationUtil::postProcessResult($result);
    }
}
