<?php

namespace Base\Gallery\Tests\Entity;

use Base\Gallery\Entity\Photo;
use Base\Gallery\Tests\Fixtures\TestAlbum;
use Base\Gallery\Tests\Fixtures\TestPhoto;
use PHPUnit\Framework\TestCase;

final class PhotoTest extends TestCase
{
    public function testTheAltIsTheOneTypedElseTheCaptionElseTheAlbumsTitle(): void
    {
        $album = new TestAlbum('Terrain de basket du CJS Geispolsheim');
        $album->addPhoto($photo = new TestPhoto('/a.jpg'));

        self::assertSame('Terrain de basket du CJS Geispolsheim', $photo->getAlt());
        self::assertNull($photo->getOwnAlt());

        $photo->setCaption('Le marquage au sol terminé');
        self::assertSame('Le marquage au sol terminé', $photo->getAlt());

        $photo->setAlt('Lignes blanches sur un parquet de basket');
        self::assertSame('Lignes blanches sur un parquet de basket', $photo->getAlt());
        self::assertSame('Lignes blanches sur un parquet de basket', $photo->getOwnAlt());
    }

    public function testAPhotoWithoutAnAlbumHasAnEmptyAlt(): void
    {
        self::assertSame('', (new TestPhoto('/a.jpg'))->getAlt());
    }

    public function testTheTextsAreTrimmedAndEmptyOnesAreNull(): void
    {
        $photo = (new TestPhoto())->setCaption("  Avant la pose \n")->setLabel('   ')->setCredit('')->setAlt(null);

        self::assertSame('Avant la pose', $photo->getCaption());
        self::assertNull($photo->getLabel());
        self::assertNull($photo->getCredit());
        self::assertNull($photo->getOwnAlt());
    }

    public function testTheCreditLineCarriesItsSignOnce(): void
    {
        $photo = new TestPhoto();
        self::assertNull($photo->getCreditLine());

        self::assertSame('© Anna Meyer', $photo->setCredit('Anna Meyer')->getCreditLine());
        self::assertSame('© Anna Meyer', $photo->setCredit('© Anna Meyer')->getCreditLine());
        self::assertSame('Photo : Anna Meyer', $photo->setCredit('Photo : Anna Meyer')->getCreditLine());
    }

    public function testThePublicAddressOfAStoredFile(): void
    {
        self::assertSame('/uploads/gallery/a.jpg', Photo::publicUrl('/srv/app/public/uploads/gallery/a.jpg'));
        self::assertSame('/uploads/a.jpg', Photo::publicUrl('/uploads/a.jpg'));
        self::assertSame('https://cdn.example.org/a.jpg', Photo::publicUrl('https://cdn.example.org/a.jpg'));
        self::assertNull(Photo::publicUrl(null));
        self::assertNull(Photo::publicUrl(''));

        self::assertSame('/uploads/a.jpg', (new TestPhoto('/srv/app/public/uploads/a.jpg'))->getFileUrl());
        self::assertNull((new TestPhoto())->getFileUrl());
    }

    public function testCoverPositionAndStringForm(): void
    {
        $photo = new TestPhoto('/a.jpg');
        self::assertFalse($photo->isCover());
        self::assertNull($photo->getPosition());

        $photo->setCover(true)->setPosition(4)->setCaption('De nuit');
        self::assertTrue($photo->isCover());
        self::assertSame(4, $photo->getPosition());
        self::assertSame('De nuit', (string) $photo);
    }
}
