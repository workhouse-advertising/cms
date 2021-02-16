<?php

namespace Tests\Data\Structures;

use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Structures\CollectionStructureTree;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;
use Tests\UnlinksPaths;

class CollectionStructureTreeTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;
    use UnlinksPaths;

    public function setUp(): void
    {
        parent::setUp();

        $stache = $this->app->make('stache');
        $stache->store('collection-trees')->directory($this->directory = '/path/to/structures/collections');
    }

    /** @test */
    public function it_can_get_and_set_the_handle()
    {
        $tree = new CollectionStructureTree;
        $this->assertNull($tree->handle());

        $return = $tree->handle('test');

        $this->assertSame($tree, $return);
        $this->assertEquals('test', $tree->handle());
    }

    /** @test */
    public function it_gets_the_structure()
    {
        $collection = Collection::make('test')->structureContents(['root' => true]);
        $structure = $collection->structure();
        Collection::shouldReceive('findByHandle')->with('test')->once()->andReturn($collection);

        $this->assertNull(Blink::get($blinkKey = 'collection-tree-structure-test'));

        $tree = (new CollectionStructureTree)->handle('test');

        // Do it twice combined with the once() in the mock to show blink works.
        $this->assertSame($structure, $tree->structure());
        $this->assertSame($structure, $tree->structure());
        $this->assertSame($structure, Blink::get($blinkKey));
    }

    /** @test */
    public function it_gets_the_path()
    {
        $collection = Collection::make('pages')->structureContents(['root' => true]);
        Collection::shouldReceive('findByHandle')->with('pages')->andReturn($collection);
        $tree = $collection->structure()->makeTree('en');
        $this->assertEquals('/path/to/structures/collections/pages.yaml', $tree->path());
    }

    /** @test */
    public function it_gets_the_path_when_using_multisite()
    {
        Site::setConfig(['sites' => [
            'one' => ['locale' => 'en_US', 'url' => '/one'],
            'two' => ['locale' => 'fr_Fr', 'url' => '/two'],
        ]]);

        $collection = Collection::make('pages')->structureContents(['root' => true]);
        Collection::shouldReceive('findByHandle')->with('pages')->andReturn($collection);
        $tree = $collection->structure()->makeTree('en');
        $this->assertEquals('/path/to/structures/collections/en/pages.yaml', $tree->path());
    }
}
