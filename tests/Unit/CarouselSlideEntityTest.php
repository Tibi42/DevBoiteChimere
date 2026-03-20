<?php

namespace App\Tests\Unit;

use App\Entity\CarouselSlide;
use PHPUnit\Framework\TestCase;

class CarouselSlideEntityTest extends TestCase
{
    public function testDefaultPositionIsZero(): void
    {
        $slide = new CarouselSlide();

        $this->assertSame(0, $slide->getPosition());
    }

    public function testSettersAndGetters(): void
    {
        $slide = new CarouselSlide();
        $slide->setPosition(2);
        $slide->setTag('GN');
        $slide->setTagColor('text-custom-orange');
        $slide->setTitle('La Grande Nuit');
        $slide->setDate('20/03');
        $slide->setBtnText('S\'inscrire');
        $slide->setBtnClass('bg-custom-orange group-hover:bg-orange-600');
        $slide->setBtnUrl('/inscription/gn');

        $this->assertSame(2, $slide->getPosition());
        $this->assertSame('GN', $slide->getTag());
        $this->assertSame('text-custom-orange', $slide->getTagColor());
        $this->assertSame('La Grande Nuit', $slide->getTitle());
        $this->assertSame('20/03', $slide->getDate());
        $this->assertSame('S\'inscrire', $slide->getBtnText());
        $this->assertSame('bg-custom-orange group-hover:bg-orange-600', $slide->getBtnClass());
        $this->assertSame('/inscription/gn', $slide->getBtnUrl());
    }
}

