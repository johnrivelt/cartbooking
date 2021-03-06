<?php

namespace Test\Unit\Booking;

use CartBooking\Model\Booking\Booking;
use CartBooking\Model\Booking\BookingId;
use CartBooking\Model\Publisher\Publisher;
use Test\AutoMockingTest;

class BookingTest extends AutoMockingTest
{
    /** @var  Booking */
    private $booking;

    public function setUp()
    {
        parent::setUp();
        $this->booking = new Booking(new BookingId(), 1, \DateTimeImmutable::createFromFormat('Y-m-d', '2017-01-01'));
    }

    public function testSetOverseerOnly()
    {
        $overseer = $this->prophesize(Publisher::class);
        $overseer->isMale()->willReturn(true);
        $overseerId = 1;
        $overseer->getId()->willReturn($overseerId);
        $this->booking->setPublishers([$overseer->reveal()]);
        static::assertSame($overseerId, $this->booking->getOverseerId());
        static::assertSame(0, $this->booking->getPioneerId());
        static::assertSame(0, $this->booking->getPioneerBId());
        static::assertFalse($this->booking->isConfirmed());
    }

    public function testSetPioneerOnly()
    {
        $pioneer = $this->prophesize(Publisher::class);
        $pioneer->isMale()->willReturn(false);
        $pioneerId = 1;
        $pioneer->getId()->willReturn($pioneerId);
        $publishers = [$pioneer->reveal()];
        $this->booking->setPublishers($publishers);
        static::assertSame($publishers, $this->booking->getPublishers());
    }

    public function testSetOverseerAndPublisher()
    {
        $overseer = $this->prophesize(Publisher::class);
        $pioneer = $this->prophesize(Publisher::class);
        $overseerId = 1;
        $pioneerId = 2;
        $overseer->isMale()->willReturn(true);
        $overseer->getId()->willReturn($overseerId);
        $overseer->isRelativeTo($pioneer->reveal())->willReturn(false);
        $pioneer->isMale()->willReturn(false);
        $pioneer->getId()->willReturn($pioneerId);
        $publishers = [$overseer->reveal(), $pioneer->reveal()];
        $this->booking->setPublishers($publishers);
        static::assertSame($publishers, $this->booking->getPublishers());
        static::assertFalse($this->booking->isConfirmed());
    }

    public function testSetOverseerAnd2Pioneers()
    {
        $overseerId = 1;
        $pioneerId = 2;
        $pioneerBId = 3;
        $overseer = $this->prophesize(Publisher::class);
        $pioneer = $this->prophesize(Publisher::class);
        $pioneerB = $this->prophesize(Publisher::class);
        $overseer->isMale()->willReturn(true);
        $overseer->getId()->willReturn($overseerId);
        $pioneer->isMale()->willReturn(false);
        $pioneer->getId()->willReturn($pioneerId);
        $pioneerB->isMale()->willReturn(true);
        $pioneerB->getId()->willReturn($pioneerBId);
        $publishers = [$overseer->reveal(), $pioneer->reveal(), $pioneerB->reveal()];
        $this->booking->setPublishers($publishers);
        static::assertSame($publishers, $this->booking->getPublishers());
        static::assertTrue($this->booking->isConfirmed());
    }

    public function test2SingleRelativesAreAccepted()
    {
        $brotherId = 1;
        $sisterId = 2;
        $brother = $this->prophesize(Publisher::class);
        $sister = $this->prophesize(Publisher::class);
        $brother->isRelativeTo($sister->reveal())->willReturn(true);
        $brother->getId()->willReturn($brotherId);
        $brother->isMale()->willreturn(true);
        $sister->getId()->willReturn($sisterId);
        $sister->isMale()->willReturn(false);
        $publishers = [$brother->reveal(), $sister->reveal()];
        $this->booking->setPublishers($publishers);
        static::assertTrue($this->booking->isConfirmed());
    }

    public function testRecorded()
    {
        $booking = new Booking(new BookingId(), 1, new \DateTimeImmutable('2000-01-01'));
        $booking->setRecorded(true);
        static::assertTrue($booking->isRecorded());
    }

    public function testRecordedInFuture()
    {
        $booking = new Booking(new BookingId(), 1, (new \DateTimeImmutable('now'))->add(new \DateInterval('P1D')));
        $booking->setRecorded(true);
        static::assertFalse($booking->isRecorded());
    }
}
