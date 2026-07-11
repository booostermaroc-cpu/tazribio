<?php

namespace App\Filament\Pages;

use App\Filament\Support\Nav;
use App\Support\AiAssistantKnowledge;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AiPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.ai';

    public string $question = '';

    /** @var list<array{role: string, content: string}> */
    public array $messages = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && RolePermission::canAccessResource($user, 'ai');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('ai');
    }

    public function mount(): void
    {
        $this->messages[] = [
            'role' => 'bot',
            'content' => AiAssistantKnowledge::welcome(),
        ];
    }

    public function askTopic(string $topicId): void
    {
        $topic = AiAssistantKnowledge::find($topicId);

        if ($topic === null) {
            return;
        }

        $this->pushExchange($topic['label'], $topic['response']);
    }

    public function askQuestion(): void
    {
        $question = trim($this->question);

        if ($question === '') {
            return;
        }

        $match = AiAssistantKnowledge::matchQuestion($question);

        if ($match !== null) {
            $this->pushExchange($question, $match['response']);
        } else {
            $this->pushExchange($question, AiAssistantKnowledge::fallback());
        }

        $this->question = '';
    }

    protected function pushExchange(string $userMessage, string $botResponse): void
    {
        $this->messages[] = ['role' => 'user', 'content' => $userMessage];
        $this->messages[] = ['role' => 'bot', 'content' => trim($botResponse)];
    }

    /** @return array<string, array{label: string, keywords: list<string>, response: string}> */
    public function getTopics(): array
    {
        return AiAssistantKnowledge::topics();
    }
}
