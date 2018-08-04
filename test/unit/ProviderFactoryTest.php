<?php

namespace eiriksm\CosyComposerTest\unit;

use eiriksm\CosyComposer\ProviderFactory;
use eiriksm\CosyComposer\Providers\Github;
use Violinist\Slug\Slug;

class ProviderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromHost()
    {
        $pf = new ProviderFactory();
        $slug = Slug::createFromUrl('https://github.com/eiriksm/cosy-composer');
        $provider = $pf->createFromHost($slug, []);
        $this->assertEquals(get_class($provider), Github::class);
    }
}
