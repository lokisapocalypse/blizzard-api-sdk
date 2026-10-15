<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

/**
 * A Guzzle adapter that records how long it would wait between retries instead of sleeping, so tests stay fast.
 */
class SleeplessGuzzleAdapter extends GuzzleAdapter
{
    /**
     * Seconds the adapter would have waited, in order.
     *
     * @var list<float>
     */
    public array $waits = [];

    /**
     * Record the wait instead of sleeping.
     *
     * @param float $seconds how long the adapter would wait
     * @return void
     */
    protected function sleep(float $seconds): void
    {
        $this->waits[] = $seconds;
    }
}
