<?php

namespace App\Tests\Unit;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Twig\ArticleExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class ArticleExtensionTest extends TestCase
{
    public function testRegistersTwigFunction(): void
    {
        $repo = $this->createStub(ArticleRepository::class);
        $router = $this->createStub(RouterInterface::class);
        $extension = new ArticleExtension($repo, $router);

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('featured_articles', $functions[0]->getName());
    }

    public function testReturnsDefaultArticlesWhenRepositoryIsEmpty(): void
    {
        $repo = $this->createMock(ArticleRepository::class);
        $repo->expects($this->once())
            ->method('findActiveOrderByPosition')
            ->willReturn([]);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_article_assaut_dragons')
            ->willReturn('/nouvelles/compte-rendu-assaut-des-dragons');

        $extension = new ArticleExtension($repo, $router);
        $articles = $extension->getFeaturedArticles();

        $this->assertCount(4, $articles);
        $this->assertSame("COMPTE-RENDU : L'ASSAUT DES DRAGONS", $articles[0]['title']);
        $this->assertSame('/nouvelles/compte-rendu-assaut-des-dragons', $articles[0]['url']);
        $this->assertNull($articles[0]['id']);
        $this->assertFalse($articles[0]['hasContent']);

        $this->assertSame('REPORTAGE PHOTO : GN DU CRÉPUSCULE', $articles[1]['title']);
        $this->assertNull($articles[1]['url']);
        $this->assertNull($articles[1]['id']);
        $this->assertFalse($articles[1]['hasContent']);
    }

    public function testReturnsMappedArticlesFromEntities(): void
    {
        $article = new Article();
        // Force an ID via reflection since getId returns null on new entity
        $ref = new \ReflectionProperty(Article::class, 'id');
        $ref->setValue($article, 42);

        $article->setTitle('Test Article');
        $article->setImage('test.webp');
        $article->setTag('NEW');
        $article->setUrl('/custom-link');
        $article->setContent('');

        $repo = $this->createMock(ArticleRepository::class);
        $repo->expects($this->once())
            ->method('findActiveOrderByPosition')
            ->willReturn([$article]);

        $router = $this->createStub(RouterInterface::class);

        $extension = new ArticleExtension($repo, $router);
        $articles = $extension->getFeaturedArticles();

        $this->assertCount(1, $articles);
        $this->assertSame(42, $articles[0]['id']);
        $this->assertSame('Test Article', $articles[0]['title']);
        $this->assertSame('test.webp', $articles[0]['image']);
        $this->assertSame('NEW', $articles[0]['tag']);
        $this->assertSame('/custom-link', $articles[0]['url']);
        $this->assertFalse($articles[0]['hasContent']);
    }

    public function testReturnsMappedArticlesWithContent(): void
    {
        $article = new Article();
        $ref = new \ReflectionProperty(Article::class, 'id');
        $ref->setValue($article, 100);

        $article->setTitle('Article with Content');
        $article->setImage('content.webp');
        $article->setTag('0/3');
        $article->setContent('<p>Formatted content</p>');

        $repo = $this->createMock(ArticleRepository::class);
        $repo->expects($this->once())
            ->method('findActiveOrderByPosition')
            ->willReturn([$article]);

        $router = $this->createStub(RouterInterface::class);

        $extension = new ArticleExtension($repo, $router);
        $articles = $extension->getFeaturedArticles();

        $this->assertCount(1, $articles);
        $this->assertSame(100, $articles[0]['id']);
        $this->assertSame('Article with Content', $articles[0]['title']);
        $this->assertTrue($articles[0]['hasContent']);
        $this->assertNull($articles[0]['url']);
    }
}
