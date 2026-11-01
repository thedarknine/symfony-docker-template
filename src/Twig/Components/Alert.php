<?php

declare(strict_types=1);

/**
 * This file is part of MyProject project.
 * (c) Caroline Noyer <studio@carolinenoyer.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

/**
 * Usage: <twig:Alert type="success" message="Saved!" />
 * or, with HTML content: <twig:Alert type="info">Some <strong>content</strong></twig:Alert>.
 */
#[AsTwigComponent]
final class Alert
{
    private const array STYLES = [
        'success' => ['icon' => 'mdi:check-circle', 'classes' => 'border-green-200 bg-green-50 text-green-800'],
        'error'   => ['icon' => 'mdi:alert-circle', 'classes' => 'border-red-200 bg-red-50 text-red-800'],
        'info'    => ['icon' => 'mdi:information', 'classes' => 'border-sky-200 bg-sky-50 text-sky-800'],
    ];

    public string $type = 'info';

    public ?string $message = null;

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[PreMount]
    public function validateType(array $data): array
    {
        $type = $data['type'] ?? 'info';

        if (!\is_string($type) || !\array_key_exists($type, self::STYLES)) {
            throw new \InvalidArgumentException(\sprintf('Unknown alert type "%s". Use one of: %s.', \is_string($type) ? $type : get_debug_type($type), implode(', ', array_keys(self::STYLES))));
        }

        return $data;
    }

    public function getIcon(): string
    {
        return self::STYLES[$this->type]['icon'];
    }

    public function getClasses(): string
    {
        return self::STYLES[$this->type]['classes'];
    }
}
