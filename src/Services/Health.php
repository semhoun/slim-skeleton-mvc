<?php

declare(strict_types=1);

namespace App\Services;

use App\Lib\DateTime;

final readonly class Health
{
    public function __construct(
        private Settings $settings
    ) {
    }

    /**
     * Retrieves the current status of the service.
     *
     * @return array<string|DateTime> Returns an array containing the service status information.
     */
    public function status(): array
    {
		$now = new \DateTime();
        return [
            'version' => $this->settings->get('version'),
            'date' => $now->format(DATE_ISO8601),
        ];
    }
}
