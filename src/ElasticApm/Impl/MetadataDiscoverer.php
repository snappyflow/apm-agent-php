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

use Elastic\Apm\ElasticApm;
use Elastic\Apm\Impl\Config\Snapshot as ConfigSnapshot;
use Elastic\Apm\Impl\Log\LogCategory;
use Elastic\Apm\Impl\Log\Logger;
use Elastic\Apm\Impl\Log\LoggerFactory;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class MetadataDiscoverer
{
    public static $AGENT_NAME = 'php';
    public static $LANGUAGE_NAME = 'PHP';
    public static $DEFAULT_SERVICE_NAME = 'Unnamed PHP service';

    /** @var ConfigSnapshot */
    private $config;

    /** @var Logger */
    private $logger;

    private function __construct(ConfigSnapshot $config, LoggerFactory $loggerFactory)
    {
        $this->config = $config;
        $this->logger = $loggerFactory->loggerForClass(LogCategory::$BACKEND_COMM, __NAMESPACE__, __CLASS__, __FILE__);
    }

    public static function discoverMetadata(ConfigSnapshot $config, LoggerFactory $loggerFactory): Metadata
    {
        return (new MetadataDiscoverer($config, $loggerFactory))->doDiscoverMetadata();
    }

    public function doDiscoverMetadata(): Metadata
    {
        $result = new Metadata();

        $result->process = MetadataDiscoverer::discoverProcessData();
        $result->service = MetadataDiscoverer::discoverServiceData($this->config);
        $result->system = MetadataDiscoverer::discoverSystemData($this->config);
        $result->labels = MetadataDiscoverer::discoverGlobalData($this->config);

        return $result;
    }

    public static  function adaptServiceName(string $configuredName): string
    {
        if (empty($configuredName)) {
            return self::$DEFAULT_SERVICE_NAME;
        }

        $charsAdaptedName = preg_replace('/[^a-zA-Z0-9 _\-]/', '_', $configuredName);
        return is_null($charsAdaptedName)
            ? MetadataDiscoverer::$DEFAULT_SERVICE_NAME
            : Tracer::limitKeywordString($charsAdaptedName);
    }

    public static function setKeywordStringIfNotNull($srcCfgVal, &$dstProp)
    {
        if ($srcCfgVal !== null) {
            $dstProp = Tracer::limitKeywordString($srcCfgVal);
        }
    }

    public function discoverServiceData(ConfigSnapshot $config): ServiceData
    {
        $result = new ServiceData();

        self::setKeywordStringIfNotNull($config->environment(), /* ref */ $result->environment);

        $result->name = $config->serviceName() === null
            ? MetadataDiscoverer::$DEFAULT_SERVICE_NAME
            : MetadataDiscoverer::adaptServiceName($config->serviceName());

        self::setKeywordStringIfNotNull($config->serviceNodeName(), /* ref */ $result->nodeConfiguredName);
        self::setKeywordStringIfNotNull($config->serviceVersion(), /* ref */ $result->version);

        $result->agent = new ServiceAgentData();
        $result->agent->name = self::$AGENT_NAME;
        $result->agent->version = ElasticApm::$VERSION;

        $result->language = $this->buildNameVersionData(MetadataDiscoverer::$LANGUAGE_NAME, PHP_VERSION);

        $result->runtime = $result->language;

        return $result;
    }

    public function discoverSystemData(ConfigSnapshot $config): SystemData
    {
        $result = new SystemData();

        if ($config->hostname() !== null) {
            $result->configuredHostname = Tracer::limitKeywordString($config->hostname());
            $result->hostname = $result->configuredHostname;
        } else {
            $detectedHostname = gethostname();
            if ($detectedHostname !== false) {
                $result->detectedHostname = Tracer::limitKeywordString($detectedHostname);
                $result->hostname = $result->detectedHostname;
            }
        }

        return $result;
    }

    public function discoverGlobalData(ConfigSnapshot $config): GlobalLabelData
    {
        $result = new GlobalLabelData();
        $dict = [];
        if ($this->config->globalLabels() !== null) {
            // $serializedMetadata .= "\n";
           
            $labels = explode (",", $this->config->globalLabels());
            foreach ($labels as $label) {
                $label_arr = explode("=", $label);
                $dict[$label_arr[0]] = $label_arr[1];
            }
            $result->projectName = $dict['_tag_projectName'];
            $result->appName = $dict['_tag_appName'];
            $result->profileId = $dict['_tag_profileId'];
            // $serializedMetadata = substr($serializedMetadata, 0, -1);
            // $serializedMetadata .= "}}";
        }

        return $result;
    }

    public function buildNameVersionData(string $name, string $version): NameVersionData
    {
        $result = new NameVersionData();

        $result->name = $name;
        $result->version = $version;

        return $result;
    }

    public function discoverProcessData(): ProcessData
    {
        $result = new ProcessData();

        $currentProcessId = getmypid();
        if ($currentProcessId === false) {
            ($loggerProxy = $this->logger->ifErrorLevelEnabled(__LINE__, __FUNCTION__))
            && $loggerProxy->log('Failed to get current process ID - setting it to 0 instead');
            $result->pid = 0;
        } else {
            $result->pid = $currentProcessId;
        }

        return $result;
    }
}
