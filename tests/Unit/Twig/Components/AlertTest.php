<?php

declare(strict_types=1);

/**
 * This file is part of MyProject project.
 * (c) Caroline Noyer <studio@carolinenoyer.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Unit\Twig\Components;

use App\Twig\Components\Alert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AlertTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideTypes(): iterable
    {
        yield 'success' => ['success', 'mdi:check-circle'];
        yield 'error' => ['error', 'mdi:alert-circle'];
        yield 'info' => ['info', 'mdi:information'];
    }

    #[DataProvider('provideTypes')]
    public function testIconMatchesType(string $type, string $expectedIcon): void
    {
        $alert       = new Alert();
        $alert->type = $type;

        self::assertSame($expectedIcon, $alert->getIcon());
    }

    public function testUnknownTypeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Unknown alert type "warning"');

        new Alert()->validateType(['type' => 'warning']);
    }
}
