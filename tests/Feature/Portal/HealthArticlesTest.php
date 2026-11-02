<?php

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthArticlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_articles_appear_and_link_to_working_illustrated_pages(): void
    {
        $this->withoutVite();
        $listing = $this->get(route('public.news'))->assertOk();

        foreach (require config_path('health-articles.php') as $article) {
            $listing->assertSee($article['title']);
            $this->assertFileExists(public_path($article['image']));
            $this->get(route('public.news.show', $article['slug']))
                ->assertOk()
                ->assertSee($article['title'])
                ->assertSee($article['body'][0]['title'])
                ->assertSee($article['image'])
                ->assertSee($article['sources'][0]['url']);
        }
    }

    public function test_news_filter_only_shows_the_requested_category(): void
    {
        $this->withoutVite();
        $this->get(route('public.news', ['danh-muc' => 'Dinh dưỡng']))
            ->assertOk()->assertSee('Xây dựng bữa ăn cân đối mỗi ngày')
            ->assertDontSee('Vận động đều đặn từ những bước nhỏ');
        $this->get(route('public.news', ['danh-muc' => 'không hợp lệ']))
            ->assertOk()->assertSee('Xây dựng bữa ăn cân đối mỗi ngày')
            ->assertSee('Vận động đều đặn từ những bước nhỏ');
    }
}
