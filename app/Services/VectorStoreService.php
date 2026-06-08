<?php

namespace App\Services;

use App\Models\VectorEmbedding;
use Illuminate\Support\Facades\Http;

class VectorStoreService
{
    public function storeEmbedding(string $text, string $source = 'user_input')
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/embeddings', [
                'input' => $text,
                'model' => 'text-embedding-3-small'
            ]);

        if ($response->successful()) {
            $embedding = $response->json('data.0.embedding');

            return VectorEmbedding::create([
                'content' => $text,
                'embedding' => $embedding,
                'source' => $source
            ]);
        }

        throw new \Exception('Failed to generate embedding: ' . $response->body());
    }

    public function searchSimilar(string $text, int $limit = 5)
    {
        $queryEmbedding = $this->getQueryEmbedding($text);
        
        return VectorEmbedding::all()->map(function ($item) use ($queryEmbedding) {
            $item->similarity = $this->cosineSimilarity($queryEmbedding, $item->embedding);
            return $item;
        })->sortByDesc('similarity')->take($limit);
    }

    private function getQueryEmbedding(string $text)
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/embeddings', [
                'input' => $text,
                'model' => 'text-embedding-3-small'
            ]);

        return $response->json('data.0.embedding');
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += ($a[$i] * $b[$i]);
            $normA += ($a[$i] ** 2);
            $normB += ($b[$i] ** 2);
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}