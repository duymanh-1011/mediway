<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SessionSearch
{
    public const PARAMETER = 'search_ref';

    public function __construct(private Request $request, private string $scope) {}

    public function remember(?string $keyword): array
    {
        if (! filled($keyword)) {
            return [];
        }

        $token = Str::random(24);
        $searches = $this->request->session()->get($this->key(), []);
        $searches[$token] = $keyword;
        $this->request->session()->put($this->key(), array_slice($searches, -10, preserve_keys: true));

        return [self::PARAMETER => $token];
    }

    public function keyword(): ?string
    {
        $token = $this->request->query(self::PARAMETER);

        return is_string($token) ? ($this->request->session()->get($this->key(), [])[$token] ?? null) : null;
    }

    public function navigation(): array
    {
        return $this->keyword() === null ? [] : [self::PARAMETER => $this->request->query(self::PARAMETER)];
    }

    private function key(): string
    {
        return 'session_search.'.$this->scope.'.'.$this->request->user()->getAuthIdentifier();
    }
}
