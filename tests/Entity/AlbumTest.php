<?php

namespace Base\Gallery\Tests\Entity;

use Base\Enum\ThreadState;
use Base\Gallery\Tests\Fixtures\TestAlbum;
use Base\Gallery\Tests\Fixtures\TestPhoto;
use PHPUnit\Framework\TestCase;

final class AlbumTest extends TestCase
{
    public function testAPhotoJoiningAnAlbumTakesTheNextPlace(): void
    {
        $album = new TestAlbum('Enseigne');
        $album->addPhoto($first = new TestPhoto('/uploads/a.jpg'));
        $album->addPhoto($second = new TestPhoto('/uploads/b.jpg'));
        $album->addPhoto($placed = new TestPhoto('/uploads/c.jpg', 7));
        $album->addPhoto($last = new TestPhoto('/uploads/d.jpg'));

        self::assertSame([1, 2, 7, 8], [$first->getPosition(), $second->getPosition(), $placed->getPosition(), $last->getPosition()]);
        self::assertSame($album, $first->getAlbum());
        self::assertCount(4, $album->getPhotos());
    }

    public function testAddingTheSamePhotoTwiceKeepsOne(): void
    {
        $album = new TestAlbum('Enseigne');
        $photo = new TestPhoto('/uploads/a.jpg');
        $album->addPhoto($photo)->addPhoto($photo);

        self::assertCount(1, $album->getPhotos());
    }

    public function testThePhotosAreShownByPlaceThenInTheOrderAdded(): void
    {
        $album = new TestAlbum('Covering');
        $album->addPhoto($c = new TestPhoto('/c.jpg', 3));
        $album->addPhoto($a = new TestPhoto('/a.jpg', 1));
        $album->addPhoto($b1 = new TestPhoto('/b1.jpg', 2));
        $album->addPhoto($b2 = new TestPhoto('/b2.jpg', 2));

        self::assertSame([$a, $b1, $b2, $c], $album->getOrderedPhotos());
    }

    public function testTheCoverIsTheOneMarkedElseTheFirst(): void
    {
        $album = new TestAlbum('Vitrine');
        self::assertNull($album->getCover());

        $album->addPhoto($first = new TestPhoto('/a.jpg'));
        $album->addPhoto($second = new TestPhoto('/b.jpg'));
        self::assertSame($first, $album->getCover());

        $second->setCover(true);
        self::assertSame($second, $album->getCover());
    }

    public function testMarkingACoverUnmarksTheOthers(): void
    {
        $album = new TestAlbum('Vitrine');
        $album->addPhoto($first = (new TestPhoto('/a.jpg'))->setCover(true));
        $album->addPhoto($second = new TestPhoto('/b.jpg'));

        $album->setCover($second);

        self::assertFalse($first->isCover());
        self::assertTrue($second->isCover());

        $album->setCover(null);
        self::assertFalse($second->isCover());
        self::assertSame($first, $album->getCover());
    }

    public function testRemovingAPhotoAndRenumbering(): void
    {
        $album = new TestAlbum('Totem');
        $album->addPhoto($a = new TestPhoto('/a.jpg'));
        $album->addPhoto($b = new TestPhoto('/b.jpg'));
        $album->addPhoto($c = new TestPhoto('/c.jpg'));

        $album->removePhoto($b);
        self::assertNull($b->getAlbum());
        self::assertSame([1, 3], [$a->getPosition(), $c->getPosition()]);

        $album->renumberPhotos();
        self::assertSame([1, 2], [$a->getPosition(), $c->getPosition()]);
    }

    public function testThePhotosByLabelInOrder(): void
    {
        $album = new TestAlbum('Rénovation');
        $album->addPhoto($after = (new TestPhoto('/after.jpg', 2))->setLabel('Après'));
        $album->addPhoto($before = (new TestPhoto('/before.jpg', 1))->setLabel('Avant'));
        $album->addPhoto(new TestPhoto('/plain.jpg', 3));
        $album->addPhoto($after2 = (new TestPhoto('/after2.jpg', 4))->setLabel('Après'));

        self::assertSame(['Avant' => [$before], 'Après' => [$after, $after2]], $album->getPhotosByLabel());
    }

    public function testPublishingSetsTheStateAndTheDateOnce(): void
    {
        $album = new TestAlbum('Palissade');
        $album->publish(true, new \DateTimeImmutable('2025-03-01 10:00'));

        self::assertSame(ThreadState::PUBLISH, $album->getState());
        self::assertSame('2025-03-01', $album->getPublishedAt()->format('Y-m-d'));

        $album->publish();
        self::assertSame('2025-03-01', $album->getPublishedAt()->format('Y-m-d'), 'published again, it keeps its date');

        $album->publish(false);
        self::assertSame(ThreadState::DRAFT, $album->getState());
    }

    public function testFeaturedAndMadeAt(): void
    {
        $album = new TestAlbum('Flotte');
        self::assertFalse($album->isFeatured());
        self::assertNull($album->getMadeAt());

        $album->setFeatured(true)->setMadeAt(new \DateTimeImmutable('2024-06-12'));
        self::assertTrue($album->isFeatured());
        self::assertSame('2024-06-12', $album->getMadeAt()->format('Y-m-d'));
    }
}
