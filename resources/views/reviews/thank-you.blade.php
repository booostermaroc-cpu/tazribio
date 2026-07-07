<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('codflow.review.thank_you_title') }} — {{ config('app.name', 'Tazri Bio') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #ecfdf5 0%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #1e293b;
        }
        .card {
            max-width: 480px;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0.75rem; }
        p { color: #64748b; line-height: 1.6; }
        .ratings {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            text-align: left;
            font-size: 0.95rem;
        }
        .ratings div { margin-bottom: 0.5rem; }
        .stars { color: #f59e0b; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✓</div>
        <h1>{{ __('codflow.review.thank_you_title') }}</h1>
        <p>
            @if ($alreadySubmitted ?? false)
                {{ __('codflow.review.already_submitted') }}
            @else
                {{ __('codflow.review.thank_you_body') }}
            @endif
        </p>

        @if ($review->isSubmitted())
            <div class="ratings">
                <div>
                    <strong>{{ __('codflow.review.product_rating') }} :</strong>
                    <span class="stars">{{ str_repeat('★', $review->product_rating) }}{{ str_repeat('☆', 5 - $review->product_rating) }}</span>
                </div>
                <div>
                    <strong>{{ __('codflow.review.service_rating') }} :</strong>
                    <span class="stars">{{ str_repeat('★', $review->service_rating) }}{{ str_repeat('☆', 5 - $review->service_rating) }}</span>
                </div>
                @if ($review->comment)
                    <div style="margin-top: 0.75rem;">
                        <strong>{{ __('codflow.review.comment') }} :</strong>
                        <p style="margin-top: 0.25rem;">{{ $review->comment }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
