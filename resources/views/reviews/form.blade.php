<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('codflow.review.form_title') }} — {{ config('app.name', 'CODFlow') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #f8fafc 100%);
            min-height: 100vh;
            padding: 1.5rem;
            color: #1e293b;
        }
        .card {
            max-width: 520px;
            margin: 0 auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
            padding: 2rem;
        }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .subtitle { color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem; }
        .order-ref {
            background: #f1f5f9;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .field { margin-bottom: 1.5rem; }
        .stars { display: flex; gap: 0.5rem; flex-direction: row-reverse; justify-content: flex-end; }
        .stars input { display: none; }
        .stars label {
            cursor: pointer;
            font-size: 2rem;
            color: #cbd5e1;
            transition: color 0.15s;
            margin: 0;
        }
        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label { color: #f59e0b; }
        textarea {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font: inherit;
            resize: vertical;
            min-height: 100px;
        }
        textarea:focus { outline: 2px solid #3b82f6; border-color: transparent; }
        .errors { background: #fef2f2; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
        button {
            width: 100%;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
        .brand { text-align: center; margin-top: 1.5rem; color: #94a3b8; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('codflow.review.form_title') }}</h1>
        <p class="subtitle">{{ __('codflow.review.form_subtitle') }}</p>

        <div class="order-ref">
            <strong>{{ __('codflow.fields.order_number') }} :</strong> {{ $order->order_number }}
            @if ($order->client?->full_name)
                <br><strong>{{ __('codflow.fields.client') }} :</strong> {{ $order->client->full_name }}
            @endif
        </div>

        @if ($errors->any())
            <div class="errors">
                <ul style="margin-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reviews.store', $review->token) }}">
            @csrf

            <div class="field">
                <label>{{ __('codflow.review.product_rating') }}</label>
                <div class="stars">
                    @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" name="product_rating" id="product_{{ $i }}" value="{{ $i }}" @checked(old('product_rating') == $i) required>
                        <label for="product_{{ $i }}" title="{{ $i }}/5">★</label>
                    @endfor
                </div>
            </div>

            <div class="field">
                <label>{{ __('codflow.review.service_rating') }}</label>
                <div class="stars">
                    @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" name="service_rating" id="service_{{ $i }}" value="{{ $i }}" @checked(old('service_rating') == $i) required>
                        <label for="service_{{ $i }}" title="{{ $i }}/5">★</label>
                    @endfor
                </div>
            </div>

            <div class="field">
                <label for="comment">{{ __('codflow.review.comment') }}</label>
                <textarea name="comment" id="comment" placeholder="{{ __('codflow.review.comment_placeholder') }}">{{ old('comment') }}</textarea>
            </div>

            <button type="submit">{{ __('codflow.review.submit') }}</button>
        </form>

        <p class="brand">{{ config('app.name', 'CODFlow') }}</p>
    </div>
</body>
</html>
