<x-filament-panels::page>
    <div class="codflow-ai">
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ __('codflow.ai.help') }}</p>

        <div class="codflow-ai__topics mb-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                {{ __('codflow.ai.topics_title') }}
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->getTopics() as $topicId => $topic)
                    <button
                        type="button"
                        wire:click="askTopic('{{ $topicId }}')"
                        class="codflow-ai__topic-btn"
                    >
                        {{ $topic['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="codflow-ai__chat" wire:poll.none>
            @foreach ($messages as $message)
                <div @class([
                    'codflow-ai__bubble',
                    'codflow-ai__bubble--user' => $message['role'] === 'user',
                    'codflow-ai__bubble--bot' => $message['role'] === 'bot',
                ])>
                    <div class="codflow-ai__bubble-label">
                        {{ $message['role'] === 'user' ? __('codflow.ai.you') : __('codflow.ai.assistant') }}
                    </div>
                    <div class="codflow-ai__bubble-text">{!! nl2br(e($message['content'])) !!}</div>
                </div>
            @endforeach
        </div>

        <form wire:submit="askQuestion" class="codflow-ai__input-row mt-4">
            <x-filament::input.wrapper class="flex-1">
                <x-filament::input
                    type="text"
                    wire:model="question"
                    placeholder="{{ __('codflow.ai.input_placeholder') }}"
                />
            </x-filament::input.wrapper>
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                {{ __('codflow.ai.send') }}
            </x-filament::button>
        </form>
    </div>

    <style>
        .codflow-ai__topics .codflow-ai__topic-btn {
            border-radius: 9999px;
            border: 1px solid rgb(var(--primary-200));
            background: rgb(var(--primary-50));
            color: rgb(var(--primary-700));
            padding: 0.35rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .dark .codflow-ai__topics .codflow-ai__topic-btn {
            border-color: rgb(var(--primary-800));
            background: rgba(var(--primary-500), 0.15);
            color: rgb(var(--primary-300));
        }
        .codflow-ai__topics .codflow-ai__topic-btn:hover {
            background: rgb(var(--primary-100));
        }
        .codflow-ai__chat {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 28rem;
            overflow-y: auto;
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgb(var(--gray-200));
            background: rgb(var(--gray-50));
        }
        .dark .codflow-ai__chat {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-900));
        }
        .codflow-ai__bubble {
            max-width: 90%;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
        }
        .codflow-ai__bubble--user {
            align-self: flex-end;
            background: rgb(var(--primary-600));
            color: white;
        }
        .codflow-ai__bubble--bot {
            align-self: flex-start;
            background: white;
            border: 1px solid rgb(var(--gray-200));
            color: rgb(var(--gray-800));
        }
        .dark .codflow-ai__bubble--bot {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-600));
            color: rgb(var(--gray-100));
        }
        .codflow-ai__bubble-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            opacity: 0.75;
            margin-bottom: 0.35rem;
        }
        .codflow-ai__bubble-text {
            font-size: 0.92rem;
            line-height: 1.55;
            direction: rtl;
            text-align: right;
        }
        .codflow-ai__bubble--user .codflow-ai__bubble-text {
            direction: ltr;
            text-align: left;
        }
        .codflow-ai__input-row {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }
    </style>
</x-filament-panels::page>
