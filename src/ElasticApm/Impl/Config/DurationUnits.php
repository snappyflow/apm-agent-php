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
use ElasticApmTests\UnitTests\UtilTests\TimeDurationUnitsTest;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class DurationUnits
{
    use StaticClassTrait;

    public static $MILLISECONDS = 0;
    public static $SECONDS = 1;
    public static $MINUTES = 2;

    public static $MILLISECONDS_SUFFIX = 'ms';
    public static $SECONDS_SUFFIX = 's';
    public static $MINUTES_SUFFIX = 'm';

    /**
     * @var array<array{string, int}> Array should be in descending order of suffix length
     *
     * @see TimeDurationUnitsTest::testSuffixAndIdIsInDescendingOrderOfSuffixLength
     */
    public static  $suffixAndIdPairs
        = [
            ['ms', 0],
            ['s', 1],
            ['m', 2],
        ];
}
