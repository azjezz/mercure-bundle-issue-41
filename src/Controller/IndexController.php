<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function json_encode;

final class IndexController
{
    public function __construct(
        private Publisher $publisher,
        private ParameterBagInterface $parameters,
        private UrlGeneratorInterface $generator,
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $env_hub_url = $_ENV['MERCURE_PUBLISH_URL'] ?? null;
        $getenv_hub_url = getenv('MERCURE_PUBLISH_URL');

        $hub_url = $this->parameters->get('mercure.default_hub');
        $publish_url = $this->generator->generate('publish');

        return new Response(<<<HTML
<html lang="en">
    <body>
        <div>
            <p><code>mercure.default_hub={$hub_url}</code></p>
            <p><code>getenv(MERCURE_PUBLISH_URL)={$getenv_hub_url}</code></p>
            <p><code>_ENV[MERCURE_PUBLISH_URL]={$env_hub_url}</code></p>
            <p>book updates received: <span id="count">0</span></p>
            <button id="receive">receive an update</button>
        </div>
        <script>
        let count = 0;
        const eventSource = new EventSource('{$hub_url}?topic=' + encodeURIComponent('https://example.com/books/1'));
        eventSource.onmessage = event => {
            count++;
            // Will be called every time an update is published by the server
            document.getElementById('count').textContent = count + ' ( latest status: ' + JSON.parse(event.data).status + ' )';
        }
        
        document.getElementById('receive').addEventListener('click', async () => {
            await fetch('{$publish_url}');
        })
        
        </script>
    </body>
</html>
HTML);

    }

    #[Route('/publish/{status}', name: 'publish')]
    public function publish(string $status = 'in-stock'): Response
    {
        ($this->publisher)(new Update(
            'https://example.com/books/1',
            json_encode(['status' => $status], JSON_THROW_ON_ERROR),
        ));

        return new Response('published!');
    }
}
