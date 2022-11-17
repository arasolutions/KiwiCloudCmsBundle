<?php

namespace AraSolutions\KiwiCloudCmsBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ImageExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('kiwi_image', array($this, 'image')) ,
            new TwigFilter('kiwi_fichier', array($this, 'fichier')) ,
        );
    }

    public function image(int $imageId): string
    {
        return 'https://www.kiwicloudcms.com/content/picture/id/' . $imageId;
    }

    public function fichier(string $fichierId): string
    {
        return 'https://www.kiwicloudcms.com/' . $fichierId;
    }
}
