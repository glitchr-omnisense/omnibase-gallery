<?php

namespace Base\Gallery\Service;

use Base\Gallery\Entity\Album;
use Base\Gallery\Entity\Photo;

/**
 * An album as schema.org reads it: an ImageGallery whose images are
 * ImageObjects, each with its address, its caption and its credit.
 * Plain arrays: the template encodes them.
 */
final class JsonLd
{
    /**
     * @param string        $url      the album's absolute address
     * @param callable|null $absolute turns a picture's site path ("/uploads/a.jpg") into an absolute address;
     *                                null leaves the paths as they are
     *
     * @return array<string, mixed>
     */
    public function album(Album $album, string $url, ?callable $absolute = null): array
    {
        $absolute ??= static fn (string $path): string => $path;

        $images = [];
        foreach ($album->getOrderedPhotos() as $photo) {
            $image = $this->photo($photo, $absolute);
            if (null !== $image) {
                $images[] = $image;
            }
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ImageGallery',
            'name' => (string) $album->getTitle(),
            'url' => $url,
        ];
        if ($album->getExcerpt()) {
            $data['description'] = trim(strip_tags((string) $album->getExcerpt()));
        }
        if ($album->getMadeAt()) {
            $data['dateCreated'] = $album->getMadeAt()->format('Y-m-d');
        }
        if ($album->getPublishedAt()) {
            $data['datePublished'] = $album->getPublishedAt()->format('c');
        }
        if ($album->getUpdatedAt()) {
            $data['dateModified'] = $album->getUpdatedAt()->format('c');
        }
        $keywords = [];
        foreach ($album->getTags() as $tag) {
            $keywords[] = (string) $tag;
        }
        if ($keywords) {
            $data['keywords'] = implode(', ', $keywords);
        }
        if ($images) {
            $cover = $album->getCover();
            $coverUrl = $cover?->getFileUrl();
            if ($coverUrl) {
                $data['thumbnailUrl'] = $absolute($coverUrl);
            }
            $data['image'] = $images;
        }

        return $data;
    }

    /** @return array<string, mixed>|null null for a photograph without a file */
    public function photo(Photo $photo, ?callable $absolute = null): ?array
    {
        $url = $photo->getFileUrl();
        if (null === $url) {
            return null;
        }
        $image = ['@type' => 'ImageObject', 'contentUrl' => $absolute ? $absolute($url) : $url];
        if ('' !== $photo->getAlt()) {
            $image['name'] = $photo->getAlt();
        }
        if ($photo->getCaption()) {
            $image['caption'] = $photo->getCaption();
        }
        if ($photo->getCredit()) {
            $image['creditText'] = $photo->getCredit();
            $image['creator'] = ['@type' => 'Person', 'name' => preg_replace('/^(©|\(c\))\s*/iu', '', $photo->getCredit())];
        }

        return $image;
    }
}
