<?php

namespace Tests\Unit;

use EloquentWorks\Fellowship\Status;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusTest extends TestCase
{
    #[Test]
    public function it_exposes_all_supported_status_values(): void
    {
        $this->assertSame('pending', Status::PENDING);
        $this->assertSame('accepted', Status::ACCEPTED);
        $this->assertSame('denied', Status::DENIED);
        $this->assertSame('blocked', Status::BLOCKED);
        $this->assertSame('canceled', Status::CANCELED);
        $this->assertSame('expired', Status::EXPIRED);
    }
}
