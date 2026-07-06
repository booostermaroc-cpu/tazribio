<?php

namespace App\Filament\Widgets;

use App\Enums\OrderConfirmationAction;
use App\Models\OrderConfirmationLog;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ConfirmationAgentStatsWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?string $heading = null;

    public function getTableHeading(): ?string
    {
        return __('codflow.confirmation_tracking.agent_stats');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->statsQuery())
            ->columns([
                TextColumn::make('name')
                    ->label(__('codflow.confirmation_tracking.agent')),
                TextColumn::make('whatsapp_count')
                    ->label(OrderConfirmationAction::WhatsappContact->label())
                    ->alignCenter(),
                TextColumn::make('call_count')
                    ->label(OrderConfirmationAction::ConfirmedViaCall->label())
                    ->alignCenter(),
                TextColumn::make('sms_count')
                    ->label(OrderConfirmationAction::SmsContact->label())
                    ->alignCenter(),
                TextColumn::make('confirmed_count')
                    ->label(OrderConfirmationAction::ConfirmedViaWhatsapp->label())
                    ->alignCenter(),
                TextColumn::make('prepared_count')
                    ->label(OrderConfirmationAction::OrderPrepared->label())
                    ->alignCenter(),
                TextColumn::make('shipped_count')
                    ->label(OrderConfirmationAction::OrderShipped->label())
                    ->alignCenter(),
                TextColumn::make('refusal_count')
                    ->label(__('codflow.confirmation_tracking.refusals'))
                    ->alignCenter(),
                TextColumn::make('total_clicks')
                    ->label(__('codflow.confirmation_tracking.total_clicks'))
                    ->alignCenter()
                    ->weight('bold'),
            ])
            ->paginated(false);
    }

    protected function statsQuery(): Builder
    {
        $whatsapp = OrderConfirmationAction::WhatsappContact->value;
        $call = OrderConfirmationAction::PhoneCall->value;
        $sms = OrderConfirmationAction::SmsContact->value;
        $confirmedViaWhatsapp = OrderConfirmationAction::ConfirmedViaWhatsapp->value;
        $confirmedViaCall = OrderConfirmationAction::ConfirmedViaCall->value;
        $orderConfirmed = OrderConfirmationAction::OrderConfirmed->value;
        $prepared = OrderConfirmationAction::OrderPrepared->value;
        $shipped = OrderConfirmationAction::OrderShipped->value;

        $refusalActions = array_map(
            fn (OrderConfirmationAction $action): string => $action->value,
            OrderConfirmationAction::refusalActions(),
        );

        return User::query()
            ->select('users.id', 'users.name')
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id')
                    ->where('action', $whatsapp),
                'whatsapp_count'
            )
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id')
                    ->whereIn('action', [$call, $confirmedViaCall]),
                'call_count'
            )
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id')
                    ->where('action', $sms),
                'sms_count'
            )
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id')
                    ->whereIn('action', [$confirmedViaWhatsapp, $orderConfirmed]),
                'confirmed_count'
            )
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id')
                    ->where('action', $prepared),
                'prepared_count'
            )
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id')
                    ->where('action', $shipped),
                'shipped_count'
            )
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id')
                    ->whereIn('action', $refusalActions),
                'refusal_count'
            )
            ->selectSub(
                OrderConfirmationLog::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('order_confirmation_logs.user_id', 'users.id'),
                'total_clicks'
            )
            ->whereHas('confirmationLogs')
            ->orderByDesc('total_clicks');
    }
}
