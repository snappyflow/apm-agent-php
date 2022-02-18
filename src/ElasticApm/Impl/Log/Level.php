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

namespace Elastic\Apm\Impl\Log;

use Elastic\Apm\Impl\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class Level
{
    use StaticClassTrait;

    public static  $OFF = 0;
    public static  $CRITICAL = 1;
    public static  $ERROR = 2;
    public static  $WARNING = 3;
    public static  $INFO = 4;
    public static  $DEBUG = 5;
    public static  $TRACE = 6;

    /**
     * @var array<array<string|int>>
     * @phpstan-var array<array{string, int}>
     */
    public static   $nameIntPairs
        = [
            ['OFF', 0],
            ['CRITICAL', 1],
            ['ERROR', 2],
            ['WARNING', 3],
            ['INFO', 4],
            ['DEBUG', 5],
            ['TRACE', 6]
        ];

    /** @var array<int, string> */
    private static $intToName;

    /**
     * @return array<array<string|int>>
     * @phpstan-return array<array{string, int}>
     */
    public static function nameIntPairs(): array
    {
        return self::$nameIntPairs;
    }

    public static function intToName(int $intValueToMap): string
    {
        if (!isset(self::$intToName)) {
            self::$intToName = [];
            foreach (self::$nameIntPairs as $nameIntPair) {
                self::$intToName[$nameIntPair[1]] = $nameIntPair[0];
            }
        }
        return self::$intToName[$intValueToMap];
    }
}
