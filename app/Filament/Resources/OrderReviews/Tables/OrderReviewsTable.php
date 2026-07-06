<?php

namespace App\Filament\Resources\OrderReviews\Tables;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Labels;
use App\Models\OrderReview;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('submitted_at')
                    ->label(__('codflow.review.submitted_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('order.order_number')
                    ->label(Labels::field('order'))
                    ->searchable()
                    ->url(fn (OrderReview $record) => OrderResource::getUrl('view', ['record' => $record->order_id])),
                TextColumn::make('order.client.full_name')
                    ->label(Labels::field('client'))
                    ->searchable(),
                TextColumn::make('order.client.phone')
                    ->label(Labels::field('phone'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product_rating')
                    ->label(__('codflow.review.product_rating'))
                    ->formatStateUsing(fn (?int $state) => self::stars($state))
                    ->html()
                    ->alignCenter(),
                TextColumn::make('service_rating')
                    ->label(__('codflow.review.service_rating'))
                    ->formatStateUsing(fn (?int $state) => self::stars($state))
                    ->html()
                    ->alignCenter(),
                TextColumn::make('comment')
                    ->label(__('codflow.review.comment'))
                    ->limit(50)
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('linkSender.name')
                    ->label(__('codflow.review.link_sent_by'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_rating')
                    ->label(__('codflow.review.product_rating'))
                    ->options(self::ratingOptions()),
                SelectFilter::make('service_rating')
                    ->label(__('codflow.review.service_rating'))
                    ->options(self::ratingOptions()),
                Filter::make('submitted_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('codflow.confirmation_tracking.from_date')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('codflow.confirmation_tracking.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('submitted_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('submitted_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('codflow.review.view_detail'))
                    ->schema(fn (Schema $schema): Schema => $schema->components([
                        Section::make(__('codflow.review.section'))
                            ->schema([
                                TextEntry::make('order.order_number')
                                    ->label(Labels::field('order')),
                                TextEntry::make('order.client.full_name')
                                    ->label(Labels::field('client')),
                                TextEntry::make('submitted_at')
                                    ->label(__('codflow.review.submitted_at'))
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('product_rating')
                                    ->label(__('codflow.review.product_rating'))
                                    ->formatStateUsing(fn (?int $state) => self::stars($state))
                                    ->html(),
                                TextEntry::make('service_rating')
                                    ->label(__('codflow.review.service_rating'))
                                    ->formatStateUsing(fn (?int $state) => self::stars($state))
                                    ->html(),
                                TextEntry::make('comment')
                                    ->label(__('codflow.review.comment'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])),
            ])
            ->paginated([10, 25, 50]);
    }

    /** @return array<int, string> */
    protected static function ratingOptions(): array
    {
        return collect(range(5, 1))
            ->mapWithKeys(fn (int $i) => [$i => self::stars($i)])
            ->all();
    }

    protected static function stars(?int $rating): string
    {
        if ($rating === null || $rating < 1) {
            return '<span class="text-gray-400">—</span>';
        }

        $filled = str_repeat('★', $rating);
        $empty = str_repeat('☆', 5 - $rating);

        return '<span class="text-amber-500 tracking-wide">'.$filled.$empty.'</span>';
    }
}
