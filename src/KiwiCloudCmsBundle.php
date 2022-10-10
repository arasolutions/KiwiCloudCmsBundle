<?php

namespace AraSolutions\KiwiCloudCmsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class KiwiCloudCmsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}