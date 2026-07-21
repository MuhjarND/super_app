<?php

namespace Tests\Unit;

use App\Services\IntegratedCalendarService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class IntegratedCalendarServiceTest extends TestCase
{
    public function testDuplicateEventIdKeepsOnlyLatestPayload()
    {
        $events = collect([
            ['id' => 'rapat-10', 'title' => 'Jadwal lama'],
            ['id' => 'agenda-2', 'title' => 'Agenda lain'],
            ['id' => 'rapat-10', 'title' => 'Jadwal terbaru'],
        ]);

        $method = new ReflectionMethod(IntegratedCalendarService::class, 'deduplicateEvents');
        $method->setAccessible(true);
        /** @var Collection $result */
        $result = $method->invoke(new IntegratedCalendarService(), $events);

        $this->assertCount(2, $result);
        $this->assertSame('Jadwal terbaru', $result->firstWhere('id', 'rapat-10')['title']);
    }
}
