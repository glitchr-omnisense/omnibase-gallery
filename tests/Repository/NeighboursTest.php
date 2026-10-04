<?php

namespace Base\Gallery\Tests\Repository;

use Base\Gallery\Repository\AlbumRepository;
use PHPUnit\Framework\TestCase;

/** The album before and the one after in the list's order (AlbumRepository::neighbours(), with no database). */
final class NeighboursTest extends TestCase
{
    public function testInTheMiddleOfTheList(): void
    {
        self::assertSame(['#8', '#3'], AlbumRepository::neighbours([8, 5, 3], 5, fn (int $id) => '#'.$id));
    }

    public function testTheFirstHasNoPreviousAndTheLastNoNext(): void
    {
        self::assertSame([null, '#5'], AlbumRepository::neighbours([8, 5, 3], 8, fn (int $id) => '#'.$id));
        self::assertSame(['#5', null], AlbumRepository::neighbours([8, 5, 3], 3, fn (int $id) => '#'.$id));
    }

    public function testAnAlbumThatIsNotListedHasNone(): void
    {
        self::assertSame([null, null], AlbumRepository::neighbours([8, 5, 3], 4, fn (int $id) => '#'.$id));
        self::assertSame([null, null], AlbumRepository::neighbours([], 4, fn (int $id) => '#'.$id));
    }
}
