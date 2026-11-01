<?php

declare(strict_types=1);

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
        $alert = new Alert();
        $alert->type = $type;

        self::assertSame($expectedIcon, $alert->getIcon());
    }

    public function testUnknownTypeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown alert type "warning"');

        (new Alert())->validateType(['type' => 'warning']);
    }
}
