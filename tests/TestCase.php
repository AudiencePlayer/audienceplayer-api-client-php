<?php

declare(strict_types=1);

namespace Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use TestCaseMockHelper;

    protected $currentProcessId = null;

    /**
     * @return bool
     */
    protected function isParallelTesting()
    {
        return false !== getenv('TEST_TOKEN');
    }

    /**
     * @return array|false|int|string
     */
    protected function fetchCurrentProcessId()
    {
        if (is_null($this->currentProcessId)) {

            if ($this->isParallelTesting()) {
                // assume "paratest" (parallel phpunit testing)
                $this->currentProcessId = getenv('TEST_TOKEN');
            } else {
                // assume single thread phpunit testing
                $this->currentProcessId = 1;
            }

        }

        return $this->currentProcessId;
    }

}
