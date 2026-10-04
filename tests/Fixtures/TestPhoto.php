<?php

namespace Base\Gallery\Tests\Fixtures;

use Base\Gallery\Entity\Photo;

/** A Photo whose file is given as an address: Uploader asks the kernel's storage where a real one is. */
class TestPhoto extends Photo
{
    public function __construct(private ?string $testFile = null, ?int $position = null)
    {
        $this->position = $position;
    }

    public function getFile(): ?string { return $this->testFile; }
    public function hasFile(): bool { return null !== $this->testFile; }
}
