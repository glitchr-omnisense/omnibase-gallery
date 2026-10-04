<?php

namespace Base\Gallery\Tests\Service;

use Base\Gallery\Service\JsonLd;
use Base\Gallery\Tests\Fixtures\TestAlbum;
use Base\Gallery\Tests\Fixtures\TestPhoto;
use PHPUnit\Framework\TestCase;

final class JsonLdTest extends TestCase
{
    public function testAnAlbumIsAnImageGalleryOfItsPhotosInOrder(): void
    {
        $album = (new TestAlbum('Palissade Five Guys', 'palissade-five-guys', 12))->withTags('Palissade', 'Restauration');
        $album->setExcerpt('<p>Un habillage de <strong>chantier</strong>.</p>');
        $album->setMadeAt(new \DateTimeImmutable('2024-05-20'));
        $album->testPublishedAt = new \DateTimeImmutable('2024-06-01 09:00:00', new \DateTimeZone('UTC'));
        $album->addPhoto((new TestPhoto('/srv/app/public/uploads/b.jpg', 2))->setCaption('De nuit')->setCredit('© Anna Meyer'));
        $album->addPhoto((new TestPhoto('/srv/app/public/uploads/a.jpg', 1))->setAlt('La palissade posée'));
        $album->addPhoto(new TestPhoto(null, 3));

        $data = (new JsonLd())->album($album, 'https://example.org/galerie/palissade-five-guys', fn (string $path) => 'https://example.org'.$path);

        self::assertSame('https://schema.org', $data['@context']);
        self::assertSame('ImageGallery', $data['@type']);
        self::assertSame('Palissade Five Guys', $data['name']);
        self::assertSame('https://example.org/galerie/palissade-five-guys', $data['url']);
        self::assertSame('Un habillage de chantier.', $data['description']);
        self::assertSame('2024-05-20', $data['dateCreated']);
        self::assertSame('2024-06-01T09:00:00+00:00', $data['datePublished']);
        self::assertSame('Palissade, Restauration', $data['keywords']);
        self::assertSame('https://example.org/uploads/a.jpg', $data['thumbnailUrl']);

        self::assertCount(2, $data['image'], 'a photograph without a file is left out');
        self::assertSame(['@type' => 'ImageObject', 'contentUrl' => 'https://example.org/uploads/a.jpg', 'name' => 'La palissade posée'], $data['image'][0]);
        self::assertSame([
            '@type' => 'ImageObject',
            'contentUrl' => 'https://example.org/uploads/b.jpg',
            'name' => 'De nuit',
            'caption' => 'De nuit',
            'creditText' => '© Anna Meyer',
            'creator' => ['@type' => 'Person', 'name' => 'Anna Meyer'],
        ], $data['image'][1]);
    }

    public function testAnAlbumWithoutPhotosNorDatesKeepsToWhatItKnows(): void
    {
        $data = (new JsonLd())->album(new TestAlbum('Vide', 'vide'), 'https://example.org/galerie/vide');

        self::assertSame(['@context', '@type', 'name', 'url'], array_keys($data));
    }

    public function testThePathsAreLeftAsTheyAreWithoutAHost(): void
    {
        $album = new TestAlbum('Totem', 'totem');
        $album->addPhoto(new TestPhoto('/uploads/a.jpg'));

        $data = (new JsonLd())->album($album, '/galerie/totem');

        self::assertSame('/uploads/a.jpg', $data['image'][0]['contentUrl']);
        self::assertSame('Totem', $data['image'][0]['name'], 'the alt falls back to the album\'s title');
    }
}
