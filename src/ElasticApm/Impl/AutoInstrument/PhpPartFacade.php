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

namespace Elastic\Apm\Impl\AutoInstrument;

use Elastic\Apm\Impl\GlobalTracerHolder;
use Elastic\Apm\Impl\Tracer;
use Elastic\Apm\Impl\Util\Assert;
use Elastic\Apm\Impl\Util\ElasticApmExtensionUtil;
use Elastic\Apm\Impl\Util\HiddenConstructorTrait;
use RuntimeException;
use Throwable;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * Called by elastic_apm extension
 *
 * @noinspection PhpUnused
 */
final class PhpPartFacade
{
    /**
     * Constructor is hidden because instance() should be used instead
     */
    use HiddenConstructorTrait;

    /** @var self|null */
    private static $singletonInstance = null;

    /** @var TransactionForExtensionRequest|null */
    private $transactionForExtensionRequest = null;

    /** @var InterceptionManager|null */
    private $interceptionManager = null;

    private function __construct(float $requestInitStartTime)
    {
        if (!ElasticApmExtensionUtil::isLoaded()) {
            throw new RuntimeException(ElasticApmExtensionUtil::$EXTENSION_NAME . ' extension is not loaded');
        }

        $tracer = self::buildTracer();
        if (is_null($tracer)) {
            BootstrapStageLogger::logDebug(
                'Cutting bootstrap sequence short - tracing is disabled',
                __LINE__,
                __FUNCTION__
            );
            return;
        }

        $this->transactionForExtensionRequest = new TransactionForExtensionRequest($tracer, $requestInitStartTime);
        $this->interceptionManager = new InterceptionManager($tracer);
    }

    /**
     * Called by elastic_apm extension
     *
     * @noinspection PhpUnused
     *
     * @param int   $maxEnabledLogLevel
     * @param float $requestInitStartTime
     *
     * @return bool
     */
    public static  function bootstrap(int $maxEnabledLogLevel, float $requestInitStartTime): bool
    {
        BootstrapStageLogger::configure($maxEnabledLogLevel);
        BootstrapStageLogger::logDebug(
            'Starting bootstrap sequence...' . " maxEnabledLogLevel: $maxEnabledLogLevel",
            __LINE__,
            __FUNCTION__
        );

        if (!is_null(self::$singletonInstance)) {
            BootstrapStageLogger::logCritical(
                'bootstrap() is called even though singleton instance is already created'
                . ' (probably bootstrap() is called more than once)',
                __LINE__,
                __FUNCTION__
            );
            return false;
        }

        try {
            self::$singletonInstance = new self($requestInitStartTime);
        } catch (Throwable $throwable) {
            BootstrapStageLogger::logCriticalThrowable(
                $throwable,
                'One of the steps in bootstrap sequence let a throwable escape',
                __LINE__,
                __FUNCTION__
            );
            return false;
        }

        BootstrapStageLogger::logDebug('Successfully completed bootstrap sequence', __LINE__, __FUNCTION__);
        return true;
    }

    private static function singletonInstance(): self
    {
        if (is_null(self::$singletonInstance)) {
            throw new RuntimeException(
                'Trying to use singleton instance that is not set'
                . ' (probably either before call to bootstrap() or after failed call to bootstrap())'
            );
        }

        return self::$singletonInstance;
    }

    /**
     * Called by elastic_apm extension
     *
     * @noinspection PhpUnused
     *
     * @param int         $interceptRegistrationId
     * @param object|null $thisObj
     * @param mixed       ...$interceptedCallArgs
     *
     * @return bool
     */
    public static function interceptedCallPreHook(
        int $interceptRegistrationId,
        $thisObj,
        ...$interceptedCallArgs
    ): bool {
        $interceptionManager = self::singletonInstance()->interceptionManager;
        if ($interceptionManager === null) {
            return false;
        }

        return $interceptionManager->interceptedCallPreHook(
            $interceptRegistrationId,
            $thisObj,
            $interceptedCallArgs
        );
    }

    /**
     * Called by elastic_apm extension
     *
     * @noinspection PhpUnused
     *
     * @param bool  $hasExitedByException
     * @param mixed $returnValueOrThrown
     */
    public static function interceptedCallPostHook(bool $hasExitedByException, $returnValueOrThrown)
    {
        $interceptionManager = self::singletonInstance()->interceptionManager;
        assert($interceptionManager !== null);

        $interceptionManager->interceptedCallPostHook(
            1 /* <- $numberOfStackFramesToSkip */,
            $hasExitedByException,
            $returnValueOrThrown
        );
    }

    /**
     * Called by elastic_apm extension
     *
     * @noinspection PhpUnused
     */
    public static function shutdown()
    {
        BootstrapStageLogger::logDebug('Starting shutdown sequence...', __LINE__, __FUNCTION__);

        if (is_null(self::$singletonInstance)) {
            BootstrapStageLogger::logWarning(
                'Shutdown sequence is invoked even though singleton instance is not created'
                . ' (probably because bootstrap sequence failed)',
                __LINE__,
                __FUNCTION__
            );
            return;
        }

        try {
            self::singletonInstance()->shutdownImpl();
        } catch (Throwable $throwable) {
            BootstrapStageLogger::logCriticalThrowable(
                $throwable,
                'One of the steps in shutdown sequence let a throwable escape - skipping the rest of the steps',
                __LINE__,
                __FUNCTION__
            );
        }

        self::$singletonInstance = null;
        BootstrapStageLogger::logDebug('Successfully completed shutdown sequence', __LINE__, __FUNCTION__);
    }

    private function shutdownImpl()
    {
        if (!is_null($this->transactionForExtensionRequest)) {
            $this->transactionForExtensionRequest->onShutdown();
        }
    }

    /**
     * @return Tracer|null
     */
    private static function buildTracer(): Tracer
    {
        ($assertProxy = Assert::ifEnabled())
        && $assertProxy->that(!GlobalTracerHolder::isSet())
        && $assertProxy->withContext(
            '!GlobalTracerHolder::isSet()',
            ['GlobalTracerHolder::get()' => GlobalTracerHolder::get()]
        );

        $tracer = GlobalTracerHolder::get();
        if ($tracer->isNoop()) {
            return null;
        }

        ($assertProxy = Assert::ifEnabled())
        && $assertProxy->that($tracer instanceof Tracer)
        && $assertProxy->withContext('$tracer instanceof Tracer', ['get_class($tracer)' => get_class($tracer)]);
        assert($tracer instanceof Tracer);

        return $tracer;
    }
}
